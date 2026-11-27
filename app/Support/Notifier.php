<?php

namespace App\Support;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Satıcıya anlık bildirim: Telegram ve/veya yöneticilerin e-postası. Ayarlar > Bildirimler.
 *
 * Aynı konu (örneğin aynı bozuk hesap) için tekrar tekrar mesaj atılmaz: her bildirim bir
 * anahtarla belirli süre susturulur. Gönderim sayfa yanıtı gittikten sonra yapılır; Telegram
 * yavaşsa müşteri beklemez.
 */
class Notifier
{
    public const TOPICS = [
        'broken' => 'Hesap kod veremiyor',
        'stock' => 'Stok azaldı',
        'shared' => 'Anahtar paylaşılıyor olabilir',
        'request' => 'Müşteri ek hak istedi',
    ];

    public static function enabled(): bool
    {
        return self::telegramReady() || Setting::flag('notify_mail');
    }

    /** Konu açıksa ve son $quietMinutes içinde aynı $dedupe gönderilmediyse bildirir. */
    public static function send(string $topic, string $text, ?string $dedupe = null, int $quietMinutes = 180): void
    {
        if (! Setting::flag('notify_'.$topic) || ! self::enabled()) {
            return;
        }

        if ($dedupe && ! Cache::add('notify:'.$topic.':'.$dedupe, 1, now()->addMinutes($quietMinutes))) {
            return;
        }

        $message = Setting::brand().' · '.(self::TOPICS[$topic] ?? $topic)."\n".$text;

        app()->terminating(fn () => self::deliver($message));
    }

    /** Ayarlar sayfasındaki "Deneme mesajı gönder": hemen gönderir, hata varsa döndürür. */
    public static function test(): ?string
    {
        if (! self::enabled()) {
            return 'Önce Telegram bilgilerini gir ya da e-posta bildirimini aç.';
        }

        return self::deliver(Setting::brand().' · Deneme bildirimi'."\nBildirimler çalışıyor.", true);
    }

    private static function deliver(string $message, bool $throw = false): ?string
    {
        $error = null;

        if (self::telegramReady()) {
            try {
                $response = Http::timeout(6)->asForm()->post(
                    'https://api.telegram.org/bot'.self::token().'/sendMessage',
                    ['chat_id' => Setting::get('notify_telegram_chat'), 'text' => $message, 'disable_web_page_preview' => 'true'],
                );

                if (! $response->successful()) {
                    $error = 'Telegram mesajı gönderilemedi: '.($response->json('description') ?? 'HTTP '.$response->status());
                }
            } catch (Throwable $e) {
                $error = 'Telegram\'a bağlanılamadı.';
                Log::warning('Telegram bildirimi', ['error' => $e->getMessage()]);
            }
        }

        if (Setting::flag('notify_mail')) {
            try {
                $admins = User::where('role', User::ROLE_ADMIN)->where('is_active', true)->pluck('email')->all();

                if ($admins) {
                    Mail::raw($message, fn ($mail) => $mail->to($admins)->subject(strtok($message, "\n")));
                }
            } catch (Throwable $e) {
                $error ??= 'E-posta gönderilemedi. Sunucunun e-posta (MAIL_*) ayarlarını kontrol et.';
                Log::warning('E-posta bildirimi', ['error' => $e->getMessage()]);
            }
        }

        if ($error) {
            Log::warning('Bildirim', ['error' => $error]);
        }

        return $error;
    }

    private static function telegramReady(): bool
    {
        return self::token() !== null && filled(Setting::get('notify_telegram_chat'));
    }

    private static function token(): ?string
    {
        $stored = Setting::get('notify_telegram_token');

        if (! $stored) {
            return null;
        }

        try {
            return Crypt::decryptString($stored);
        } catch (Throwable) {
            return null;
        }
    }
}
