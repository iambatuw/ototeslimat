<?php

namespace App\Support;

class Mask
{
    /** ba****@gmail.com: satıcı müşteriyi tanır, adresin tamamını görmez. */
    public static function email(?string $email): string
    {
        if (! $email || ! str_contains($email, '@')) {
            return '';
        }

        [$name, $domain] = explode('@', $email, 2);

        return mb_substr($name, 0, 2).str_repeat('*', max(2, min(6, mb_strlen($name) - 2))).'@'.$domain;
    }
}
