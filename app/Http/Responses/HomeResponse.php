<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Girişten sonra nereye: giriş öncesi istenen sayfa kişinin girebileceği bir yerse oraya,
 * değilse (müşteri yönetim sayfası istemişse ya da tersi) kendi sayfasına. Böylece kimse
 * girişten hemen sonra "yasak" sayfasına düşmez.
 */
class HomeResponse implements LoginResponse, TwoFactorLoginResponse
{
    public function toResponse($request): Response
    {
        $user = $request->user();

        if ($request->wantsJson()) {
            return response()->json(['two_factor' => false]);
        }

        $intended = (string) $request->session()->pull('url.intended', '');
        $path = '/'.ltrim((string) parse_url($intended, PHP_URL_PATH), '/');
        $adminArea = str_starts_with($path, '/yonetim');
        $sameSite = $intended !== '' && parse_url($intended, PHP_URL_HOST) === $request->getHost();

        if ($sameSite && $path !== '/' && $adminArea === $user->isAdmin()) {
            return redirect()->to($intended);
        }

        return redirect()->to($user->homePath());
    }
}
