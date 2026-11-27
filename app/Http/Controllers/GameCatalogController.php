<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Setting;

/** Herkese açık oyun kataloğu (/oyunlar). Ayarlardan açılmadıysa sayfa yoktur. */
class GameCatalogController extends Controller
{
    public function __invoke()
    {
        abort_unless(Setting::flag('games_catalog'), 404);

        $games = Game::visible()->orderByDesc('featured')->orderBy('name')->get();

        return view('games', [
            'games' => $games,
            'platforms' => collect(Game::platforms())->only($games->pluck('platform')->unique()),
            'banner' => $games->filter(fn (Game $game) => $game->cover)->sortByDesc('featured')->take(6)->values(),
        ]);
    }
}
