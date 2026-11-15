<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/** Mağazanın herkese açık ana sayfası. Girişli kullanıcıyı kendi sayfasına gönderir. */
class LandingController extends Controller
{
    public function __invoke(Request $request)
    {
        if ($user = $request->user()) {
            return redirect($user->homePath());
        }

        return view('landing', [
            'platforms' => collect(config('guard.platforms'))->except('other')->pluck('label'),
        ]);
    }
}
