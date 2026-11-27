<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Setting;
use Illuminate\Http\Request;

/** Mağazanın herkese açık ana sayfası. Girişli kullanıcı da açabilir; üst çubukta kendi sayfasına dönüş düğmesi görür. */
class LandingController extends Controller
{
    public function __invoke(Request $request)
    {
        return view('landing', [
            'platforms' => collect(config('guard.platforms'))->except('other')->pluck('label'),
            // Oyun kataloğu açıksa ana sayfada öne çıkan oyunlardan bir şerit.
            'games' => Setting::flag('games_catalog')
                ? Game::visible()->orderByDesc('featured')->latest()->limit(10)->get()
                : collect(),
        ]);
    }
}
