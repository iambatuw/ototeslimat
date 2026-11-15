<?php

namespace App\Services\Guard;

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
    public function issue(ProductKey $key, string $type): array
    {
        $account = $key->account;
        $this->guard($key);

        if (! $account->is_active || $account->trashed()) {
            throw new GuardException('Bu hesap şu an kod vermiyor. Satıcıyla iletişime geç.', 'inactive');
        }

        $result = match ($type) {
            self::TYPE_TOTP => $this->fromTotp($key),
            self::TYPE_MAIL => $this->fromMail($key),
            default => throw new GuardException('Geçersiz istek.', 'bad_type'),
        };

        $this->consume($key, $result['code']);
        $account->forceFill(['last_code_at' => now()])->saveQuietly();
        $result['uses_left'] = $key->fresh()->usesLeft();

        return $result;
    }

    private function guard(ProductKey $key): void
    {
        if ($key->revoked_at) {
            throw new GuardException('Bu anahtar satıcı tarafından iptal edildi.', 'revoked');
        }

        if ($key->isExpired()) {
            throw new GuardException('Bu anahtarın süresi '.$key->expires_at->format('d.m.Y H:i').' tarihinde doldu.', 'expired');
        }
    }

    private function fromTotp(ProductKey $key): array
    {
        if (! $key->account->hasTotp()) {
            throw new GuardException('Bu hesapta Authenticator kodu yok.', 'no_totp');
        }

        try {
            $now = time();
            $code = $this->totp->code($key->account->totp_secret, $now);
        } catch (Throwable $e) {
            throw new GuardException('Hesabın Authenticator anahtarı okunamadı. Satıcıya haber ver.', 'totp_secret', $e);
        }

        return ['type' => self::TYPE_TOTP, 'kind' => 'code', 'code' => $code, 'expires_in' => $this->totp->secondsLeft($now), 'period' => Totp::PERIOD, 'received_at' => null];
    }

    private function fromMail(ProductKey $key): array
    {
        $account = $key->account;

        if (! $account->hasMailbox()) {
            throw new GuardException('Bu hesapta mail kodu yok.', 'no_mailbox');
        }

        $cacheKey = 'guard:mail-code:'.$account->id;
        $found = Cache::get($cacheKey);

        if (! $found) {
            $found = $this->email->fetch($account);

            if ($found) {
                $found['received_at'] = $found['received_at']->toIso8601String();
                Cache::put($cacheKey, $found, config('guard.email_cache_seconds'));
            }
        }

        if (! $found) {
            throw new GuardException(
                'Son '.config('guard.email_lookback_minutes').' dakikada '.$account->platformLabel().' kodu gelmedi. Platformda kodu iste, gelmesi için 30 saniye bekleyip tekrar dene.',
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

    /** Hak ve bekleme kuralını kilitli bir işlemde uygular; aynı anda iki istek hakkı iki kez düşemez. */
    private function consume(ProductKey $key, string $code): void
    {
        $hash = hash('sha256', $key->id.'|'.$code);

        DB::transaction(function () use ($key, $hash) {
            $fresh = ProductKey::whereKey($key->id)->lockForUpdate()->first();

            if ($fresh->last_code_hash === $hash) {
                return; // aynı kod tekrar gösteriliyor
            }

            if ($fresh->max_uses !== null && $fresh->uses_count >= $fresh->max_uses) {
                throw new GuardException('Bu anahtarın kullanım hakkı doldu ('.$fresh->max_uses.' kod).', 'used_up');
            }

            if ($fresh->cooldown_minutes > 0 && $fresh->last_used_at) {
                $next = $fresh->last_used_at->copy()->addMinutes($fresh->cooldown_minutes);

                if ($next->isFuture()) {
                    throw new GuardException('Yeni kod için '.$next->diffForHumans(['parts' => 2, 'syntax' => 1]).' beklemen gerekiyor.', 'cooldown');
                }
            }

            $fresh->forceFill([
                'uses_count' => $fresh->uses_count + 1,
                'last_used_at' => now(),
                'last_code_hash' => $hash,
            ])->save();
        });
    }
}
