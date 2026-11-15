<?php

namespace App\Support;

/**
 * Ürün anahtarı biçimi: ÖNEK-XXXXXXXX-XXXXXXXX
 * 32 karakterlik alfabe (0, 1, I, O yok) ve 16 karakter: 80 bit rastgelelik.
 */
class KeyCode
{
    public const ALPHABET = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

    public const LENGTH = 16;

    public static function generate(): string
    {
        $body = '';
        $max = strlen(self::ALPHABET) - 1;

        for ($i = 0; $i < self::LENGTH; $i++) {
            $body .= self::ALPHABET[random_int(0, $max)];
        }

        return $body;
    }

    public static function format(string $body): string
    {
        return config('guard.key_prefix').'-'.substr($body, 0, 8).'-'.substr($body, 8, 8);
    }

    /**
     * Kullanıcının yazdığı anahtarı gövdeye indirir. Boşluk, tire ve öneki
     * yok sayar; karıştırılan harfleri (O→0 değil, alfabede olmadıkları için) eler.
     */
    public static function normalize(string $input): ?string
    {
        $clean = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $input));

        if (strlen($clean) > self::LENGTH) {
            $clean = substr($clean, -self::LENGTH);
        }

        if (strlen($clean) !== self::LENGTH) {
            return null;
        }

        return strspn($clean, self::ALPHABET) === self::LENGTH ? $clean : null;
    }

    public static function hash(string $body): string
    {
        return hash_hmac('sha256', $body, (string) config('app.key'));
    }
}
