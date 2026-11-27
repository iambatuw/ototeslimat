<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/** Mağazanın herkese açık ana sayfası. Girişli kullanıcı da açabilir; üst çubukta kendi sayfasına dönüş düğmesi görür. */
class LandingController extends Controller
{
    public function __invoke(Request $request)
    {
        return view('landing', [
            'platforms' => collect(config('guard.platforms'))->except('other')->pluck('label'),
        ]);
    }
}
