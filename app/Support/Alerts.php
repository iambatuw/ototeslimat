<?php

namespace App\Support;

use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\ProductKey;
use App\Models\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Kod verildiğinde, alınamadığında ya da anahtar eklendiğinde satıcıyı ilgilendiren durumları yakalar:
 * bozulan hesap, azalan stok, paylaşılan anahtar. Panel kartı ve bildirimler buradan beslenir.
 */
class Alerts
{
    /** Müşteriden ya da anahtar kuralından kaynaklanan, hesabın bozuk olduğunu göstermeyen nedenler. */
    public const NOT_BROKEN = ['no_mail', 'used_up', 'period', 'cooldown', 'expired', 'revoked', 'blocked_purpose', 'bad_type', 'pick_full', 'pick_invalid', 'pick_inactive'];

    public static function sharedThreshold(): int
    {
        return max(2, (int) Setting::get('shared_ip_threshold', 3));
    }

    /** Kod alınamadı: hesap son 30 dakikada 3 kez hesaptan kaynaklı hata verdiyse bildir. */
    public static function codeFailed(Account $account, string $reason): void
    {
        if (in_array($reason, self::NOT_BROKEN, true)) {
            return;
        }

        $recent = ActivityLog::where('event', 'code.failed')->where('account_id', $account->id)
            ->where('created_at', '>=', now()->subMinutes(30))->get(['meta'])
            ->filter(fn ($log) => ! in_array($log->meta['reason'] ?? '', self::NOT_BROKEN, true))->count();

        if ($recent >= 3) {
            Notifier::send('broken', $account->title.' ('.$account->platformLabel().") son 30 dakikada {$recent} kez kod veremedi. Neden: ".(ActivityLog::REASONS[$reason] ?? $reason).".\n".route('admin.accounts.edit', $account), 'account-'.$account->id);
        }
    }

    /** Anahtar eklendi: o hesabın/paketin satılmamış anahtarı eşiğe düştüyse bildir. */
    public static function keyRedeemed(ProductKey $key): void
    {
        $target = $key->isPackage() ? $key->package : $key->account;

        if (! $target) {
            return;
        }

        $left = $target->keys()->inStatus('unused')->count();

        if ($left <= StoreInsights::lowStockThreshold()) {
            $name = $key->isPackage() ? $target->name : $target->title;
            Notifier::send('stock', "{$name}: satılmamış {$left} anahtar kaldı.\n".route('admin.keys.index', ['hedef' => ($key->isPackage() ? 'package:' : 'account:').$target->id]), 'stock-'.$key->account_id.'-'.$key->package_id, 720);
        }
    }

    /** Kod verildi: anahtar son 24 saatte eşik kadar farklı IP'den kullanıldıysa kayda yaz ve bildir. */
    public static function codeIssued(ProductKey $key): void
    {
        $ips = self::ipsFor($key->id);

        if ($ips->count() < self::sharedThreshold() || ! Cache::add('shared-key:'.$key->id, 1, now()->addHours(12))) {
            return;
        }

        Activity::log('key.shared', ['key' => $key, 'user_id' => $key->redeemed_by, 'meta' => ['ips' => $ips->count()]]);
        $who = $key->customer?->email ?? 'Hızlı onay';
        Notifier::send('shared', "{$key->targetTitle()} anahtarı son 24 saatte {$ips->count()} farklı IP'den kullanıldı ({$who}). Paylaşılıyor olabilir.\n".($key->redeemed_by ? route('admin.customers.show', $key->redeemed_by) : route('admin.keys.index')), 'key-'.$key->id, 720);
    }

    /** Son 24 saatte eşik kadar farklı IP'den kod alınmış anahtarlar: [product_key_id => ip sayısı]. */
    public static function sharedKeys(): Collection
    {
        return ActivityLog::where('event', 'code.issued')->whereNotNull('product_key_id')->whereNotNull('ip')
            ->where('created_at', '>=', now()->subDay())
            ->get(['product_key_id', 'ip'])
            ->groupBy('product_key_id')
            ->map(fn ($logs) => $logs->pluck('ip')->unique()->count())
            ->filter(fn ($count) => $count >= self::sharedThreshold())
            ->sortDesc();
    }

    private static function ipsFor(int $keyId): Collection
    {
        return ActivityLog::where('event', 'code.issued')->where('product_key_id', $keyId)->whereNotNull('ip')
            ->where('created_at', '>=', now()->subDay())->pluck('ip')->unique();
    }
}
