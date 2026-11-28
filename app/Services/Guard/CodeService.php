<?php

namespace App\Services\Guard;

use App\Models\Account;
use App\Models\ProductKey;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Bir anahtar için kod verir ve anahtarın kurallarını uygular.
 *
 * Kullanım hakkı "farklı kod" başına düşer: müşteri aynı kodu sayfayı
 * yenileyip tekrar görürse hakkı azalmaz, bekleme süresine de takılmaz.
 */
class CodeService
{
    public const TYPE_MAIL = 'mail';

    public const TYPE_TOTP = '2fa';

    public function __construct(
        private readonly Totp $totp,
        private readonly EmailCodeFetcher $email,
    ) {}

    /**
     * @return array{type: string, kind: string, code: string, expires_in: ?int, period: ?int, received_at: ?string, uses_left: ?int}
     *
     * @throws GuardException
     */
    public function issue(ProductKey $key, Account $account, string $type): array
    {
        $this->guard($key);

        if (! $account->is_active || $account->trashed()) {
            throw new GuardException('Bu hesap şu an kod vermiyor. Satıcıyla iletişime geç.', 'inactive');
        }

        // Hak, dönem limiti veya bekleme kuralı dolduysa posta kutusuna bakmadan önce durdur.
        $this->ensureCanIssue($key);

        $result = match ($type) {
            self::TYPE_TOTP => $this->fromTotp($account),
            self::TYPE_MAIL => $this->fromMail($account, $key),
            default => throw new GuardException('Geçersiz istek.', 'bad_type'),
        };

        $this->consume($key, $result['code']);
        $account->forceFill(['last_code_at' => now()])->saveQuietly();
        $result['uses_left'] = $key->fresh()->usesLeft();

        return $result;
    }

    public function guard(ProductKey $key): void
    {
        if ($key->revoked_at) {
            throw new GuardException('Bu anahtar satıcı tarafından iptal edildi.', 'revoked');
        }

        if ($key->isExpired()) {
            throw new GuardException('Bu anahtarın süresi '.$key->expires_at->format('d.m.Y H:i').' tarihinde doldu.', 'expired');
        }
    }

    private function fromTotp(Account $account): array
    {
        if (! $account->hasTotp()) {
            throw new GuardException('Bu hesapta Authenticator kodu yok.', 'no_totp');
        }

        try {
            $now = time();
            $code = $this->totp->code($account->totp_secret, $now);
            // Bir sonraki 30 saniyenin kodu da verilir: süre bitince ekran yeni kodu kendiliğinden
            // gösterir, müşteri dönemin son saniyelerinde bastıysa ikinci bir hak harcamaz.
            $next = $this->totp->code($account->totp_secret, $now + Totp::PERIOD);
        } catch (Throwable $e) {
            throw new GuardException('Hesabın Authenticator anahtarı okunamadı. Satıcıya haber ver.', 'totp_secret', $e);
        }

        return ['type' => self::TYPE_TOTP, 'kind' => 'code', 'code' => $code, 'next_code' => $next, 'expires_in' => $this->totp->secondsLeft($now), 'period' => Totp::PERIOD, 'received_at' => null, 'steam' => $account->isSteamGuard()];
    }

    private function fromMail(Account $account, ?ProductKey $key = null): array
    {
        if (! $account->hasMailbox()) {
            throw new GuardException('Bu hesapta mail kodu yok.', 'no_mailbox');
        }

        // Aynı hesabı bekleyen birkaç müşteri posta kutusunu aynı anda yormasın:
        // bulunan kod biraz, "yok" sonucu çok kısa süre tutulur.
        $cacheKey = 'guard:mail-code:'.$account->id.':'.md5($account->updated_at?->timestamp.'|'.$account->codeFormat());
        $found = Cache::get($cacheKey);

        if ($found === null) {
            $found = $this->email->fetch($account);

            if ($found) {
                $found['received_at'] = $found['received_at']->toIso8601String();
                Cache::put($cacheKey, $found, config('guard.email_cache_seconds'));
            } elseif (config('guard.email_miss_cache_seconds') > 0) {
                Cache::put($cacheKey, false, config('guard.email_miss_cache_seconds'));
            }
        }

        if ($found && ($found['blocked'] ?? false)) {
            throw new GuardException(
                'Son gelen '.$account->platformLabel().' maili e-posta ya da şifre değişikliği için. Güvenlik gereği bu kod gösterilmez. Hesaba normal giriş yapıp yeni kod iste ya da satıcıyla iletişime geç.',
                'blocked_purpose'
            );
        }

        if (! $found) {
            throw new GuardException(
                'Son '.config('guard.email_lookback_minutes').' dakikada '.$account->platformLabel().' kodu gelmedi. Platformda kodu iste, gelmesi için 30 saniye bekleyip tekrar dene.',
                'no_mail'
            );
        }

        // Bu kullanıcıya daha önce gösterilmiş olan mail kodunu bir daha gösterme;
        // yeni kod maili düşene kadar beklemeye devam etsin.
        if ($key && $key->last_code_hash === hash('sha256', $key->id.'|'.$found['code'])) {
            Cache::forget($cacheKey);
            throw new GuardException(
                'Henüz yeni bir '.$account->platformLabel().' kodu gelmedi. Platformda kodu istediysen gelmesi için birkaç saniye bekle.',
                'no_mail'
            );
        }

        return [
            'type' => self::TYPE_MAIL,
            'kind' => ($account->codeFormatConfig()['type'] ?? 'code') === 'link' ? 'link' : 'code',
            'code' => $found['code'],
            'expires_in' => null,
            'period' => null,
            'received_at' => $found['received_at'],
        ];
    }

    /** Kodu getirmeden önce hak ve dönem kuralını denetler. */
    private function ensureCanIssue(ProductKey $key): void
    {
        $block = $this->ruleBlock($key);
        if ($block && $key->bonus_uses < 1) {
            throw $block;
        }
    }

    private function ruleBlock(ProductKey $fresh): ?GuardException
    {
        $running = $fresh->periodRunning();

        if ($fresh->max_uses !== null && $fresh->uses_count >= $fresh->max_uses) {
            return new GuardException('Bu anahtarın kullanım hakkı doldu ('.$fresh->max_uses.' kod). Satıcından ek hak isteyebilirsin.', 'used_up');
        }

        if ($fresh->period_limit !== null && $running && $fresh->period_count >= $fresh->period_limit) {
            $next = $fresh->period_started_at->copy()->addHours($fresh->period_hours);

            return new GuardException(
                'Bu anahtarla '.$fresh->periodLabel().' alabilirsin ve bu dönemin hakkı doldu. Yeni hakkın '.$next->diffForHumans(['parts' => 2]).' ('.$next->format('d.m H:i').') gelir.',
                'period'
            );
        }

        if ($fresh->cooldown_minutes > 0 && $fresh->last_used_at) {
            $next = $fresh->last_used_at->copy()->addMinutes($fresh->cooldown_minutes);

            if ($next->isFuture()) {
                return new GuardException('Yeni kod için '.$next->diffForHumans(['parts' => 2, 'syntax' => 1]).' beklemen gerekiyor.', 'cooldown');
            }
        }

        return null;
    }

    /** Hak ve bekleme kuralını kilitli bir işlemde uygular; aynı anda iki istek hakkı iki kez düşemez. */
    private function consume(ProductKey $key, string $code): void
    {
        $hash = hash('sha256', $key->id.'|'.$code);

        DB::transaction(function () use ($key, $hash) {
            $fresh = ProductKey::whereKey($key->id)->lockForUpdate()->first();
            $running = $fresh->periodRunning();

            $block = $this->ruleBlock($fresh);

            if ($block && $fresh->bonus_uses < 1) {
                throw $block;
            }

            $changes = ['last_used_at' => now(), 'last_code_hash' => $hash];

            if ($block) {
                $changes['bonus_uses'] = $fresh->bonus_uses - 1;
            } else {
                $changes['uses_count'] = $fresh->uses_count + 1;

                if ($fresh->period_limit !== null) {
                    $changes['period_started_at'] = $running ? $fresh->period_started_at : now();
                    $changes['period_count'] = $running ? $fresh->period_count + 1 : 1;
                }
            }

            $fresh->forceFill($changes)->save();
        });
    }
}
