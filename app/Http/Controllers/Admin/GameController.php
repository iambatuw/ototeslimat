<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\Setting;
use App\Support\Activity;
use App\Support\SteamCatalog;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** Oyun kataloğu yönetimi: ekle (tek tek ya da liste hâlinde), kapak, öne çıkar, gizle. */
class GameController extends Controller
{
    public function index(Request $request)
    {
        $query = Game::withCount('accounts')->orderByDesc('featured')->orderBy('name');

        if ($q = trim((string) $request->input('q'))) {
            $query->where('name', 'like', '%'.str_replace(['%', '_'], ['\%', '\_'], $q).'%');
        }

        if ($platform = $request->input('platform')) {
            $query->where('platform', $platform);
        }

        return view('admin.games.index', [
            'games' => $query->paginate(60)->withQueryString(),
            'platforms' => Game::platforms(),
            'filters' => $request->only('q', 'platform'),
            'enabled' => Setting::flag('games_catalog'),
            'total' => Game::count(),
            'missing' => Game::whereNull('cover')->orWhere('cover', '')->count(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $game = new Game($data);
        $game->cover = $this->cover($request, null);
        $this->complete($game);
        $game->save();

        Activity::log('game.saved', ['meta' => ['name' => $game->name]]);

        return redirect()->route('admin.games.index')->with('status', $game->name.' kataloğa eklendi.');
    }

    /**
     * Liste hâlinde ekleme: her satır bir oyun adı. Aynı platformda aynı adlı oyun atlanır.
     * İstenirse her oyunun kapağı ve Türkçe kısa açıklaması Steam'den kendiliğinden bulunur.
     */
    public function bulk(Request $request)
    {
        $data = $request->validateWithBag('bulk', [
            'platform' => ['required', Rule::in(array_keys(Game::platforms()))],
            'names' => ['required', 'string', 'max:20000'],
        ], [], ['platform' => 'platform', 'names' => 'oyun listesi']);

        $names = collect(preg_split('/\R/u', $data['names']))
            ->map(fn ($line) => Str::limit(trim(preg_replace('/\s+/u', ' ', $line)), 120, ''))
            ->filter()->unique(fn ($name) => mb_strtolower($name))->take(300);

        $existing = Game::where('platform', $data['platform'])->pluck('name')->map(fn ($n) => mb_strtolower($n))->all();
        $new = $names->reject(fn ($name) => in_array(mb_strtolower($name), $existing, true))->values();

        $autoCover = $request->boolean('auto_cover');
        $autoNote = $request->boolean('auto_note');
        set_time_limit(180);
        $matches = ($autoCover || $autoNote) ? SteamCatalog::bestMatches($new->all()) : [];
        $withCover = 0;

        foreach ($new as $name) {
            $match = $matches[$name] ?? null;
            $cover = $autoCover ? ($match['cover'] ?? $match['wide'] ?? null) : null;
            Game::create([
                'name' => $name,
                'platform' => $data['platform'],
                'cover' => $cover,
                'note' => $autoNote && filled($match['about'] ?? null) ? $match['about'] : null,
            ]);
            $withCover += $cover ? 1 : 0;
        }

        Activity::log('game.saved', ['meta' => ['name' => $new->count().' oyun, liste']]);
        $skipped = $names->count() - $new->count();

        $message = $new->count().' oyun eklendi.'
            .($autoCover ? ' '.$withCover.' tanesinin kapağı bulundu.' : '')
            .($skipped ? ' '.$skipped.' tanesi zaten vardı.' : '')
            .($autoCover && $withCover < $new->count() ? ' Kapağı bulunamayanlara "Düzenle"den görsel ekleyebilirsin.' : '');

        return redirect()->route('admin.games.index')->with('status', $message);
    }

    /** Tek oyun için kapak arar (kapaksız kartın üstündeki "Kapak bul"). */
    public function findCover(Game $game)
    {
        $this->complete($game);

        if (! $game->isDirty('cover')) {
            return back()->with('error', $game->name.' için Steam\'de kapak bulunamadı. "Düzenle"den kendi görselini ekleyebilirsin.');
        }

        $game->save();

        return back()->with('status', $game->name.' için kapak bulundu.');
    }

    /** Katalogda kapağı (ve istenirse açıklaması) olmayan oyunları Steam'den tamamlar. */
    public function fill(Request $request)
    {
        $autoNote = $request->boolean('auto_note', true);
        $games = Game::whereNull('cover')->orWhere('cover', '')->orderBy('id')->limit(300)->get();

        if ($games->isEmpty()) {
            return back()->with('status', 'Kapağı eksik oyun yok.');
        }

        set_time_limit(180);
        $matches = SteamCatalog::bestMatches($games->pluck('name')->all());
        $filled = 0;

        foreach ($games as $game) {
            $match = $matches[$game->name] ?? null;

            if (! $match) {
                continue;
            }

            $game->cover = $match['cover'] ?? $match['wide'];
            if ($autoNote && blank($game->note) && filled($match['about'])) {
                $game->note = $match['about'];
            }
            $game->save();
            $filled++;
        }

        Activity::log('game.saved', ['meta' => ['name' => $filled.' oyunun kapağı tamamlandı']]);

        return back()->with($filled ? 'status' : 'error', $filled
            ? $filled.' oyunun kapağı bulundu.'.($games->count() > $filled ? ' '.($games->count() - $filled).' tanesi Steam\'de bulunamadı; onlara kendi görselini ekle.' : '')
            : 'Steam\'de eşleşen oyun bulunamadı ya da Steam\'e ulaşılamadı.');
    }

    /**
     * Oyun adı yazılırken öneri: Steam'den ad, kapak ve Türkçe kısa açıklama. 25 sonuç döner;
     * satıcı yazmaya devam ederken tarayıcı bunları süzer. Steam'e ulaşılamazsa boş liste.
     */
    public function suggest(Request $request)
    {
        return response()->json(['items' => SteamCatalog::search((string) $request->query('q'))]);
    }

    public function edit(Game $game)
    {
        return view('admin.games.edit', ['game' => $game->loadCount('accounts'), 'platforms' => Game::platforms()]);
    }

    public function update(Request $request, Game $game)
    {
        $game->fill($this->validated($request));
        $game->cover = $request->boolean('remove_cover') ? null : $this->cover($request, $game->cover);
        if (! $request->boolean('remove_cover')) {
            $this->complete($game);
        }
        $game->save();

        Activity::log('game.saved', ['meta' => ['name' => $game->name]]);

        return redirect()->route('admin.games.index')->with('status', $game->name.' kaydedildi.');
    }

    /** Listeden tek tıkla öne çıkar ya da gizle. */
    public function toggle(Request $request, Game $game)
    {
        $field = $request->input('field') === 'featured' ? 'featured' : 'is_visible';
        $game->forceFill([$field => ! $game->{$field}])->save();

        return back()->with('status', $game->name.': '.match (true) {
            $field === 'featured' => $game->featured ? 'öne çıkarıldı.' : 'öne çıkanlardan kaldırıldı.',
            default => $game->is_visible ? 'katalogda görünüyor.' : 'katalogdan gizlendi.',
        });
    }

    public function destroy(Game $game)
    {
        $name = $game->name;
        $game->delete(); // bağlı hesaplar kalır, yalnızca oyun bağlantısı boşalır

        Activity::log('game.deleted', ['meta' => ['name' => $name]]);

        return redirect()->route('admin.games.index')->with('status', $name.' katalogdan silindi.');
    }

    /** Kapağı boş kalan oyuna Steam'de aynı adlı oyunun kapağını (ve boşsa açıklamasını) koyar. */
    private function complete(Game $game): void
    {
        if (filled($game->cover)) {
            return;
        }

        $match = SteamCatalog::bestMatches([$game->name])[$game->name] ?? null;

        if ($match) {
            $game->cover = $match['cover'] ?? $match['wide'];
            $game->note = filled($game->note) ? $game->note : ($match['about'] ?: null);
        }
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'platform' => ['required', Rule::in(array_keys(Game::platforms()))],
            'note' => ['nullable', 'string', 'max:300'],
            'cover_url' => ['nullable', 'url:https', 'max:500', 'regex:/\.(jpe?g|png|webp|avif)(\?.*)?$/i'],
            // SVG kabul edilmez: içine script gömülebilir.
            'cover_file' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:2048', 'dimensions:max_width=3000,max_height=3000'],
        ], [
            'cover_url.regex' => 'Bağlantı bir görsele (jpg, png, webp) gitmeli.',
        ], ['name' => 'oyun adı', 'note' => 'açıklama', 'cover_url' => 'kapak bağlantısı', 'cover_file' => 'kapak görseli']);

        return [
            'name' => trim($data['name']),
            'platform' => $data['platform'],
            'note' => $data['note'] ?? null,
            'featured' => $request->boolean('featured'),
            'is_visible' => $request->boolean('is_visible', true),
        ];
    }

    /** Yüklenen dosya bağlantıdan önce gelir; ikisi de yoksa eski kapak kalır. */
    private function cover(Request $request, ?string $current): ?string
    {
        if ($request->hasFile('cover_file')) {
            return $this->storeUpload($request->file('cover_file'));
        }

        return $request->filled('cover_url') ? $request->input('cover_url') : $current;
    }

    private function storeUpload(UploadedFile $file): string
    {
        $dir = public_path('uploads/games');
        File::ensureDirectoryExists($dir);
        $name = Str::random(20).'.'.$file->guessExtension();
        $file->move($dir, $name);

        return 'uploads/games/'.$name;
    }
}
