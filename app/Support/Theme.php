<?php

namespace App\Support;

use App\Models\Setting;

/** Satıcının seçtiği tema rengini CSS değişkenlerine çevirir. */
class Theme
{
    public static function css(): string
    {
        $accent = Setting::get('theme_color');

        if (! is_string($accent) || ! preg_match('/^#[0-9a-f]{6}$/i', $accent)) {
            $accent = Setting::DEFAULTS['theme_color'];
        }

        $ink = self::luminance($accent) > 0.42 ? 'oklch(14% 0.02 40)' : 'oklch(98% 0.01 80)';

        return ":root{--color-accent:{$accent};--color-accent-ink:{$ink};--color-focus:{$accent}}";
    }

    /** WCAG göreli parlaklık (0–1). */
    private static function luminance(string $hex): float
    {
        [$r, $g, $b] = array_map(function ($c) {
            $c = hexdec($c) / 255;

            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        }, str_split(ltrim($hex, '#'), 2));

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }
}
