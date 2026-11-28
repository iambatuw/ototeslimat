<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use App\Support\License;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lisans yoksa, bitmişse (ek süre dahil) ya da başka alan adınınsa siteyi kilitler.
 * Yönetici giriş yapıp yalnızca Ayarlar > Lisans sayfasını görür; müşteriler ve ziyaretçiler
 * "hizmet durduruldu" sayfasını, kod isteyen ekranlar 503 alır. Durum her sayfaya $license olarak verilir.
 */
class EnsureLicense
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! License::enforced()) {
            return $next($request);
        }

        $status = License::status($request->getHost());
        view()->share('license', $status);

        if (! in_array($status['state'], License::LOCKED, true) || $this->alwaysOpen($request)) {
            return $next($request);
        }

        if ($request->user()?->isAdmin()) {
            return redirect()->route('admin.settings', 'lisans');
        }

        $message = 'Bu mağazada kod teslimi geçici olarak durduruldu.';
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 503);
        }

        return response()->view('paused', ['contact' => Setting::get('contact_url')], 503);
    }

    /** Kilitliyken de açık kalanlar: giriş/çıkış ve yöneticinin lisans sayfası. */
    private function alwaysOpen(Request $request): bool
    {
        if ($request->routeIs('login', 'login.*', 'logout', 'two-factor.*', 'password.*')) {
            return true;
        }

        return $request->user()?->isAdmin()
            && $request->routeIs('admin.settings', 'admin.settings.update')
            && $request->route('tab') === 'lisans';
    }
}
