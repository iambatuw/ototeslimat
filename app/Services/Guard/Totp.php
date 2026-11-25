<?php

namespace App\Services\Guard;

use InvalidArgumentException;

/**
 * Authenticator kodu.
 *
 * - Standart (RFC 6238): Google Authenticator, Authy, ChatGPT/OpenAI, Epic,
 *   EA, Ubisoft gibi servislerin 2FA kurulumunda verdiği base32 anahtar. 6 rakam.
 * - Steam Guard mobil: maFile içindeki base64 "shared_secret". Aynı HMAC-SHA1,
 *   ama sonuç Steam'in 26 karakterlik alfabesinde 5 karaktere çevrilir.
 *   Saklarken "steam:" önekiyle ayrılır.
 */
class Totp
{
    public const PERIOD = 30;

    public const DIGITS = 6;

    public const STEAM_PREFIX = 'steam:';

    public const STEAM_ALPHABET = '23456789BCDFGHJKMNPQRTVWXY';

    public const STEAM_LENGTH = 5;

    public function code(string $secret, ?int $time = null): string
    {
        $counter = intdiv($time ?? time(), self::PERIOD);

        if (self::isSteam($secret)) {
            $value = self::truncate(hash_hmac('sha1', pack('J', $counter), self::steamKey($secret), true));
            $code = '';

            for ($i = 0; $i < self::STEAM_LENGTH; $i++) {
                $code .= self::STEAM_ALPHABET[$value % strlen(self::STEAM_ALPHABET)];
                $value = intdiv($value, strlen(self::STEAM_ALPHABET));
            }

            return $code;
        }

        $key = self::base32Decode(self::cleanSecret($secret));
        $value = self::truncate(hash_hmac('sha1', pack('J', $counter), $key, true));

        return str_pad((string) ($value % (10 ** self::DIGITS)), self::DIGITS, '0', STR_PAD_LEFT);
    }

    public function secondsLeft(?int $time = null): int
    {
        return self::PERIOD - (($time ?? time()) % self::PERIOD);
    }

    public static function isSteam(?string $secret): bool
    {
        return $secret !== null && str_starts_with($secret, self::STEAM_PREFIX);
    }

    public static function length(?string $secret): int
    {
        return self::isSteam($secret) ? self::STEAM_LENGTH : self::DIGITS;
    }

    /**
     * Yapıştırılan değeri saklanacak biçime getirir:
     * - otpauth://totp/...?secret=XXXX bağlantısı ya da boşluklu base32 → base32
     * - Steam maFile içeriği (JSON) ya da shared_secret → "steam:<base64>"
     *
     * @param  bool  $steam  hesap Steam ise base64 shared_secret da kabul edilir
     */
    public static function cleanSecret(string $input, bool $steam = false): string
    {
        $input = trim($input);

        // Steam Desktop Authenticator / SDA maFile
        if (str_starts_with($input, '{')) {
            $json = json_decode($input, true);
            if (is_array($json) && isset($json['shared_secret']) && is_string($json['shared_secret'])) {
                return self::STEAM_PREFIX.self::validSteamSecret($json['shared_secret']);
            }

            throw new InvalidArgumentException('Bu dosyada "shared_secret" yok. Steam için maFile içeriğinin tamamını yapıştır.');
        }

        if (self::isSteam($input)) {
            return self::STEAM_PREFIX.self::validSteamSecret(substr($input, strlen(self::STEAM_PREFIX)));
        }

        // Steam shared_secret: 20 bayt → 28 karakter base64, "=" ile biter.
        if ($steam && preg_match('#^[A-Za-z0-9+/]{27}=$#', $input)) {
            return self::STEAM_PREFIX.self::validSteamSecret($input);
        }

        if (str_starts_with(strtolower($input), 'otpauth://')) {
            parse_str((string) parse_url($input, PHP_URL_QUERY), $query);
            $input = (string) ($query['secret'] ?? '');
        }

        $secret = strtoupper(preg_replace('/[\s\-=]/', '', $input));

        if ($secret === '' || ! preg_match('/^[A-Z2-7]+$/', $secret) || strlen($secret) < 16 || strlen($secret) > 128) {
            throw new InvalidArgumentException($steam
                ? 'Bu bir Steam Guard anahtarı değil. maFile içeriğini ya da içindeki shared_secret değerini yapıştır.'
                : 'Bu bir Authenticator anahtarı değil. 2FA kurulumunda "kodu elle gir" seçeneğindeki anahtarı yapıştır.');
        }

        return $secret;
    }

    private static function validSteamSecret(string $secret): string
    {
        $secret = trim($secret);
        $raw = base64_decode($secret, true);

        if ($raw === false || strlen($raw) < 10) {
            throw new InvalidArgumentException('Steam shared_secret okunamadı. maFile içeriğini olduğu gibi yapıştır.');
        }

        return base64_encode($raw);
    }

    private static function steamKey(string $secret): string
    {
        return base64_decode(substr($secret, strlen(self::STEAM_PREFIX)), true) ?: throw new InvalidArgumentException('Steam anahtarı bozuk.');
    }

    private static function truncate(string $hmac): int
    {
        $offset = ord($hmac[19]) & 0x0F;

        return unpack('N', substr($hmac, $offset, 4))[1] & 0x7FFFFFFF;
    }

    private static function base32Decode(string $secret): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $bits = '';

        foreach (str_split($secret) as $char) {
            $bits .= str_pad(decbin(strpos($alphabet, $char)), 5, '0', STR_PAD_LEFT);
        }

        $bytes = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $bytes .= chr(bindec($byte));
            }
        }

        return $bytes;
    }
}
