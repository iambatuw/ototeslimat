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
        'tagline' => 'Giriş kodunu beklemeden kendin al.',
        'theme_color' => '#0a84ff',
        'logo' => null,
        'favicon' => null,
        'announcement' => null,
        'contact_url' => null,
        'registration_open' => '1',
        'quick_code' => '1',               // '1': müşteri hesap açmadan /onay sayfasında anahtarla kod alabilir
        'low_stock_threshold' => '3',      // satılmamış anahtar bu sayıya düşünce panelde uyarı
        'daily_summary' => '0',            // '1': her sabah yöneticilere özet e-postası
        'respect_reduced_motion' => '0',   // '1': ziyaretçinin sistemi hareketi azalt diyorsa animasyonlar kapalı başlar
        'games_catalog' => '1',            // '1': herkese açık /oyunlar sayfası ve yönetimde Oyunlar menüsü (Ayarlar'dan kapatılır)
        'right_requests' => '1',           // '1': hakkı dolan müşteri satıcıdan ek hak isteyebilir
        'shared_ip_threshold' => '3',      // bir anahtar 24 saatte bu kadar farklı IP'den kullanılırsa uyarı
        // Bildirimler (Ayarlar > Bildirimler). Telegram anahtarı şifreli saklanır.
        'notify_telegram_token' => null,
        'notify_telegram_chat' => null,
        'notify_mail' => '0',
        'notify_broken' => '1',
        'notify_stock' => '1',
        'notify_shared' => '1',
        'notify_request' => '1',
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
