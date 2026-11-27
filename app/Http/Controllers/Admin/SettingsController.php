<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\Activity;
use App\Support\Notifier;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SettingsController extends Controller
{
    public const TABS = ['genel' => 'Genel', 'gorunum' => 'Görünüm', 'ozellikler' => 'Özellikler', 'bildirimler' => 'Bildirimler'];

    public function edit(string $tab = 'genel')
    {
        abort_unless(array_key_exists($tab, self::TABS), 404);

        return view('admin.settings', ['tab' => $tab, 'settings' => Setting::values()]);
    }

    public function update(Request $request, string $tab)
    {
        abort_unless(array_key_exists($tab, self::TABS), 404);

        $values = match ($tab) {
            'genel' => $request->validate([
                'brand_name' => ['nullable', 'string', 'max:60'],
                'tab_title' => ['nullable', 'string', 'max:80'],
                'tagline' => ['nullable', 'string', 'max:160'],
                'announcement' => ['nullable', 'string', 'max:300'],
                'contact_url' => ['nullable', 'url:https', 'max:300'],
            ], [], ['brand_name' => 'marka adı', 'tab_title' => 'sekme başlığı', 'tagline' => 'karşılama yazısı', 'announcement' => 'duyuru', 'contact_url' => 'iletişim bağlantısı']),
            'gorunum' => $this->appearance($request),
            'ozellikler' => $this->features($request),
            'bildirimler' => $this->notifications($request),
        };

        Setting::put($values);
        Activity::log('settings.updated', ['meta' => ['tab' => $tab, 'fields' => array_keys($values)]]);

        return redirect()->route('admin.settings', $tab)->with('status', 'Ayarlar kaydedildi.');
    }

    public function testNotify()
    {
        $error = Notifier::test();

        return back()->with($error ? 'error' : 'status', $error ?? 'Deneme bildirimi gönderildi. Telegram\'a ya da e-postana bak.');
    }

    private function notifications(Request $request): array
    {
        $data = $request->validate([
            'notify_telegram_token' => ['nullable', 'string', 'max:120', 'regex:/^\d+:[A-Za-z0-9_-]+$/'],
            'notify_telegram_chat' => ['nullable', 'string', 'max:40', 'regex:/^-?\d+$|^@[A-Za-z0-9_]{4,}$/'],
        ], [
            'notify_telegram_token.regex' => 'Bot anahtarı 123456:ABC... biçiminde olmalı (BotFather verir).',
            'notify_telegram_chat.regex' => 'Sohbet kimliği bir sayı (örn. 123456789) ya da @kanaladi olmalı.',
        ], ['notify_telegram_token' => 'bot anahtarı', 'notify_telegram_chat' => 'sohbet kimliği']);

        $values = [
            'notify_telegram_chat' => $data['notify_telegram_chat'] ?? null,
            'notify_mail' => $request->boolean('notify_mail') ? '1' : '0',
        ];

        foreach (array_keys(Notifier::TOPICS) as $topic) {
            $values['notify_'.$topic] = $request->boolean('notify_'.$topic) ? '1' : '0';
        }

        // Anahtar alanı boş bırakılırsa eskisi kalır; "kaldır" işaretlenirse silinir.
        if ($request->boolean('remove_telegram')) {
            $values['notify_telegram_token'] = null;
            $values['notify_telegram_chat'] = null;
        } elseif (! empty($data['notify_telegram_token'])) {
            $values['notify_telegram_token'] = Crypt::encryptString($data['notify_telegram_token']);
        }

        return $values;
    }

    private function features(Request $request): array
    {
        $data = $request->validate([
            'low_stock_threshold' => ['required', 'integer', 'min:0', 'max:1000'],
            'shared_ip_threshold' => ['nullable', 'integer', 'min:2', 'max:50'],
        ], [], ['low_stock_threshold' => 'stok uyarı eşiği', 'shared_ip_threshold' => 'paylaşım uyarı eşiği']);

        return [
            'right_requests' => $request->boolean('right_requests') ? '1' : '0',
            'shared_ip_threshold' => (string) ($data['shared_ip_threshold'] ?? 3),
            'registration_open' => $request->boolean('registration_open') ? '1' : '0',
            'quick_code' => $request->boolean('quick_code') ? '1' : '0',
            'respect_reduced_motion' => $request->boolean('respect_reduced_motion') ? '1' : '0',
            'daily_summary' => $request->boolean('daily_summary') ? '1' : '0',
            'low_stock_threshold' => (string) $data['low_stock_threshold'],
        ];
    }

    private function appearance(Request $request): array
    {
        $data = $request->validate([
            'theme_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            // SVG kabul edilmez: içine script gömülebilir.
            'logo' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:1024', 'dimensions:max_width=2000,max_height=2000'],
            'favicon' => ['nullable', 'file', 'mimes:png,ico', 'max:256'],
            'remove_logo' => ['boolean'],
        ], [], ['theme_color' => 'tema rengi', 'logo' => 'logo', 'favicon' => 'favicon']);

        $values = ['theme_color' => strtolower($data['theme_color'])];

        foreach (['logo', 'favicon'] as $field) {
            if ($request->hasFile($field)) {
                $values[$field] = $this->storeUpload($request->file($field), $field);
            }
        }

        if ($request->boolean('remove_logo')) {
            $values['logo'] = null;
        }

        return $values;
    }

    /** Yüklenen görseli public/uploads içine rastgele adla kaydeder. */
    private function storeUpload(UploadedFile $file, string $prefix): string
    {
        $dir = public_path('uploads');
        File::ensureDirectoryExists($dir);

        $name = $prefix.'-'.Str::random(16).'.'.$file->guessExtension();
        $file->move($dir, $name);

        return 'uploads/'.$name;
    }
}
