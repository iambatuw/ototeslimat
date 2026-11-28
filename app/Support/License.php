<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Carbon;

/**
 * Aylık lisans. Her mağaza sitesi, satıcının (yazılımı kiralayanın) özel anahtarıyla imzalanmış bir
 * lisans anahtarıyla çalışır. Anahtar alan adını ve bitiş tarihini taşır; imza Ed25519 olduğundan
 * yalnızca özel anahtarın sahibi yeni anahtar üretebilir. Doğrulamak için koddaki açık anahtar yeter.
 *
 * Biçim: base64url(json) . "." . base64url(imza). json: d = alan adı, e = bitiş (unix), i = üretim, n = not.
 *
 * Durumlar: active, warning (son WARN_DAYS gün), grace (bitti ama GRACE_DAYS içinde), expired,
 * missing (anahtar yok), invalid (bozuk ya da imza tutmuyor), domain (başka alan adının anahtarı).
 */
class License
{
    public const WARN_DAYS = 7;

    public const GRACE_DAYS = 3;

    /** Bu durumlarda site kilitlenir: yönetici yalnızca lisans sayfasını, diğerleri durdurma sayfasını görür. */
    public const LOCKED = ['expired', 'missing', 'invalid', 'domain'];

    public static function enforced(): bool
    {
        return (bool) config('guard.license.enforce');
    }

    /** Yeni lisans anahtarı üretir (yalnızca satıcının bilgisayarında, özel anahtarla). */
    public static function issue(string $domain, Carbon $until, string $secretKey, ?string $note = null): string
    {
        $payload = json_encode(array_filter([
            'd' => self::host($domain),
            'e' => $until->getTimestamp(),
            'i' => now()->getTimestamp(),
            'n' => $note,
        ], fn ($v) => $v !== null), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $signature = sodium_crypto_sign_detached($payload, base64_decode($secretKey));

        return self::b64($payload).'.'.self::b64($signature);
    }

    /**
     * @return array{state: string, domain: ?string, expires: ?Carbon, days_left: ?int, note: ?string}
     */
    public static function verify(?string $token, string $host, ?string $publicKey = null): array
    {
        $result = ['state' => 'missing', 'domain' => null, 'expires' => null, 'days_left' => null, 'note' => null];
        $token = trim((string) $token);
        if ($token === '') {
            return $result;
        }

        $result['state'] = 'invalid';
        $parts = explode('.', $token);
        $publicKey = base64_decode((string) ($publicKey ?? config('guard.license.public_key')), true);
        if (count($parts) !== 2 || ! $publicKey || strlen($publicKey) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
            return $result;
        }

        $payload = self::unb64($parts[0]);
        $signature = self::unb64($parts[1]);
        if ($payload === null || $signature === null || strlen($signature) !== SODIUM_CRYPTO_SIGN_BYTES
            || ! sodium_crypto_sign_verify_detached($signature, $payload, $publicKey)) {
            return $result;
        }

        $data = json_decode($payload, true);
        if (! is_array($data) || ! isset($data['d'], $data['e']) || ! is_int($data['e'])) {
            return $result;
        }

        $expires = Carbon::createFromTimestamp($data['e'], config('app.timezone'));
        $result = [
            'state' => 'active',
            'domain' => $data['d'],
            'expires' => $expires,
            'days_left' => (int) floor(now()->diffInDays($expires, false)),
            'note' => $data['n'] ?? null,
        ];

        if (self::host($data['d']) !== self::host($host)) {
            $result['state'] = 'domain';
        } elseif ($expires->isPast()) {
            $result['state'] = $expires->copy()->addDays(self::GRACE_DAYS)->isFuture() ? 'grace' : 'expired';
        } elseif ($expires->lte(now()->addDays(self::WARN_DAYS))) {
            $result['state'] = 'warning';
        }

        return $result;
    }

    /** Bu sitenin kayıtlı lisansının durumu. */
    public static function status(?string $host = null): array
    {
        return self::verify(Setting::get('license_token'), $host ?? request()->getHost());
    }

    public static function locked(?array $status = null): bool
    {
        return self::enforced() && in_array(($status ?? self::status())['state'], self::LOCKED, true);
    }

    /** "WWW.Ornek.com:443" -> "ornek.com" */
    public static function host(string $value): string
    {
        $value = strtolower(trim($value));
        $value = parse_url(str_contains($value, '://') ? $value : 'http://'.$value, PHP_URL_HOST) ?: $value;

        return preg_replace('/^www\./', '', $value);
    }

    private static function b64(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }

    private static function unb64(string $text): ?string
    {
        $bytes = base64_decode(strtr($text, '-_', '+/'), true);

        return $bytes === false ? null : $bytes;
    }
}
