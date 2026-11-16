<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SettingsController extends Controller
{
    public const TABS = ['genel' => 'Genel', 'gorunum' => 'Görünüm', 'ozellikler' => 'Özellikler'];

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
            'ozellikler' => [
                'registration_open' => $request->boolean('registration_open') ? '1' : '0',
                'respect_reduced_motion' => $request->boolean('respect_reduced_motion') ? '1' : '0',
            ],
        };

        Setting::put($values);
        Activity::log('settings.updated', ['meta' => ['tab' => $tab, 'fields' => array_keys($values)]]);

        return redirect()->route('admin.settings', $tab)->with('status', 'Ayarlar kaydedildi.');
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
