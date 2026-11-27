<?php

namespace App\Support;

use App\Models\ProductKey;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Müşterinin Kodlarım sayfası için anahtarlarını hesap satırlarına çevirir.
 *
 * Tek hesaplı anahtar = bir satır; paket anahtarı = seçilmiş her hesap için bir satır
 * + seçim hakkı kaldıysa bir "seç" kutusu. Aynı platformdan birkaç hesabı olan müşteri
 * için satırlar müşterinin verdiği adı ve son kod zamanını taşır, platforma göre raflara
 * bölünür ve en son kullanılandan başlayarak sıralanır.
 */
class Wallet
{
    /**
     * @param  Collection<int, ProductKey>  $keys
     * @param  array<int, string>  $labels  account_id => müşterinin verdiği ad
     * @param  array<int, Carbon>  $lastCodes  account_id => son kod zamanı
     * @return array{rows: Collection, picks: Collection, hero: ?array, shelves: Collection, platforms: Collection}
     */
    public static function build(Collection $keys, array $labels, array $lastCodes, ?int $opened = null, ?string $selected = null): array
    {
        $rows = collect();
        $picks = collect();

        foreach ($keys as $key) {
            $blocked = in_array($key->status(), ['revoked', 'expired', 'used_up'], true) ? $key->statusLabel() : null;
            $rules = self::rules($key);

            if (! $key->isPackage()) {
                $rows->push(self::row($key, $key->account, $rules, $blocked, $labels, $lastCodes, null));

                continue;
            }

            $package = $key->package;
            $picked = $key->selections;
            $packageDown = $package->trashed() || ! $package->is_active;

            foreach ($picked as $account) {
                $rows->push(self::row($key, $account, $rules, $blocked ?? ($packageDown ? 'Paket kapatıldı' : null), $labels, $lastCodes, $package->name));
            }

            $left = max(0, $package->pick_count - $picked->count());
            if ($left > 0 && ! $blocked && ! $packageDown) {
                $picks->push([
                    'key' => $key, 'package' => $package, 'left' => $left, 'picked' => $picked->count(),
                    'choices' => $package->accounts->reject(fn ($a) => $picked->contains('id', $a->id))->values(),
                ]);
            }
        }

        // En son kullanılan önce; hiç kod alınmamışlar sonra; kod veremeyenler en sonda.
        $rows = $rows->sortBy([
            fn ($a, $b) => (int) (bool) $a['blocked'] <=> (int) (bool) $b['blocked'],
            fn ($a, $b) => ($b['last']?->getTimestamp() ?? 0) <=> ($a['last']?->getTimestamp() ?? 0),
            fn ($a, $b) => strcmp($a['title'], $b['title']),
        ])->values();

        // Üstteki büyük kart: müşterinin seçtiği (?hesap=), az önce eklenen/seçilen anahtar,
        // yoksa en son kullanılan, yoksa kod verebilen ilk hesap.
        $hero = $rows->first(fn ($r) => $selected !== null && $r['id'] === $selected && ! $r['blocked'])
            ?? $rows->first(fn ($r) => $opened !== null && $r['key']->id === $opened && ! $r['blocked'])
            ?? $rows->first(fn ($r) => ! $r['blocked']);

        // Aynı platformdan iki ya da daha fazla hesap varsa o platformun kendi rafı olur
        // ("Steam · 3 hesap"); tek hesaplı platformlar tek bir rafta toplanır.
        $byPlatform = $rows->groupBy('platform');
        $shelves = $byPlatform->filter(fn ($group) => $group->count() > 1)->map(fn ($group, $platform) => [
            'platform' => $platform,
            'label' => $group->first()['platformLabel'],
            'rows' => $group->values(),
        ])->values();
        $singles = $byPlatform->filter(fn ($group) => $group->count() === 1)->flatten(1)->values();
        if ($singles->isNotEmpty()) {
            $shelves->push(['platform' => null, 'label' => $shelves->isEmpty() ? 'Hesapların' : 'Diğer hesapların', 'rows' => $singles]);
        }

        return [
            'rows' => $rows,
            'picks' => $picks,
            'hero' => $hero,
            'shelves' => $shelves,
            'platforms' => $byPlatform->map(fn ($group, $platform) => ['platform' => $platform, 'label' => $group->first()['platformLabel'], 'count' => $group->count()])
                ->sortByDesc('count')->values(),
        ];
    }

    private static function row(ProductKey $key, $account, array $rules, ?string $blocked, array $labels, array $lastCodes, ?string $package): array
    {
        $down = $account->trashed() || ! $account->is_active;

        return [
            'id' => 'k'.$key->id.'a'.$account->id,
            'key' => $key,
            'account' => $account,
            'platform' => $account->platform,
            'platformLabel' => $account->platformLabel(),
            'title' => $account->title,
            'label' => $labels[$account->id] ?? null,
            'last' => $lastCodes[$account->id] ?? null,
            'package' => $package,
            'status' => $rules['status'],
            'meta' => $rules['meta'],
            'blocked' => $blocked ?? ($down ? 'Şu an kod verilmiyor' : null),
        ];
    }

    /** Kalan hak, geçerlilik, bekleme: kısa durum ve uzun meta. */
    private static function rules(ProductKey $key): array
    {
        $daysLeft = $key->expires_at ? (int) ceil(now()->diffInDays($key->expires_at, false)) : null;

        $periodLeft = $key->periodLeft();
        $resets = $key->periodResetsAt();

        return [
            'status' => match (true) {
                $resets !== null && $key->bonus_uses === 0 => 'Yeni hak '.$resets->diffForHumans(['parts' => 1, 'syntax' => 1]),
                $daysLeft !== null && $daysLeft <= 7 => $daysLeft <= 0 ? 'Bugün bitiyor' : $daysLeft.' gün kaldı',
                $periodLeft !== null => $periodLeft.' kod hakkın var',
                $key->max_uses !== null => $key->usesLeft().' kod hakkı',
                $daysLeft !== null => $daysLeft.' gün kaldı',
                default => 'Sınırsız',
            },
            'meta' => array_values(array_filter([
                $key->max_uses ? $key->usesLeft().' kod hakkı' : ($key->period_limit ? null : 'Sınırsız kod'),
                $key->periodLabel() ? $key->periodLabel().($resets ? ' · yeni hak '.$resets->format('d.m H:i') : ' · bu dönem '.$periodLeft.' hakkın var') : null,
                $key->bonus_uses ? 'satıcıdan '.$key->bonus_uses.' ek hak' : null,
                $key->expires_at ? $key->expires_at->format('d.m.Y').' tarihine kadar' : null,
                $key->cooldown_minutes ? 'kodlar arası '.$key->cooldown_minutes.' dk' : null,
            ])),
        ];
    }
}
