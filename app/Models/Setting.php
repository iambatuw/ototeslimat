<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Throwable;

/** Mağaza ayarları (marka, renk, logo, özellik anahtarları). Basit anahtar-değer. */
class Setting extends Model
{
    public const CREATED_AT = null;

    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    public const DEFAULTS = [
        'brand_name' => null,               // boşsa APP_NAME
        'tab_title' => null,
        'tagline' => 'Anahtarını gir, hesabının giriş kodunu buradan al.',
        'theme_color' => '#ff7a1a',
        'logo' => null,
        'favicon' => null,
        'announcement' => null,
        'contact_url' => null,
        'registration_open' => '1',
    ];

    public static function values(): array
    {
        try {
            $stored = Cache::rememberForever('settings', fn () => static::query()->pluck('value', 'key')->all());
        } catch (Throwable) {
            $stored = []; // kurulum öncesi tablo yoksa
        }

        return array_merge(self::DEFAULTS, $stored);
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return static::values()[$key] ?? $default;
    }

    public static function put(array $values): void
    {
        foreach ($values as $key => $value) {
            static::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        Cache::forget('settings');
    }

    public static function brand(): string
    {
        return static::get('brand_name') ?: config('app.name');
    }

    public static function flag(string $key): bool
    {
        return (bool) static::get($key);
    }
}
