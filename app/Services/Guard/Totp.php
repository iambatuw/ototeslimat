<?php

namespace App\Services\Guard;

use InvalidArgumentException;

/**
 * Standart Authenticator kodu (RFC 6238): Google Authenticator, Authy ve
 * ChatGPT/OpenAI gibi servislerin 2FA kurulumunda verdiği base32 anahtarla çalışır.
 */
class Totp
{
    public const PERIOD = 30;

    public const DIGITS = 6;

    public function code(string $secret, ?int $time = null): string
    {
        $key = self::base32Decode(self::cleanSecret($secret));
        $counter = intdiv($time ?? time(), self::PERIOD);
        $hmac = hash_hmac('sha1', pack('J', $counter), $key, true);
        $offset = ord($hmac[19]) & 0x0F;
        $value = unpack('N', substr($hmac, $offset, 4))[1] & 0x7FFFFFFF;

        return str_pad((string) ($value % (10 ** self::DIGITS)), self::DIGITS, '0', STR_PAD_LEFT);
    }

    public function secondsLeft(?int $time = null): int
    {
        return self::PERIOD - (($time ?? time()) % self::PERIOD);
    }

    /**
     * Yapıştırılan değeri temizler: boşlukları atar, büyük harfe çevirir,
     * otpauth://totp/...?secret=XXXX biçimindeki QR bağlantısını da kabul eder.
     */
    public static function cleanSecret(string $input): string
    {
        $input = trim($input);

        if (str_starts_with(strtolower($input), 'otpauth://')) {
            parse_str((string) parse_url($input, PHP_URL_QUERY), $query);
            $input = (string) ($query['secret'] ?? '');
        }

        $secret = strtoupper(preg_replace('/[\s\-=]/', '', $input));

        if ($secret === '' || ! preg_match('/^[A-Z2-7]+$/', $secret) || strlen($secret) < 16 || strlen($secret) > 128) {
            throw new InvalidArgumentException('Bu bir Authenticator anahtarı değil. 2FA kurulumunda "kodu elle gir" seçeneğindeki anahtarı yapıştır.');
        }

        return $secret;
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
