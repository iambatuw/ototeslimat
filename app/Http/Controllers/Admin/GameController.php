<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\Setting;
use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

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
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $game = new Game($data);
        $game->cover = $this->cover($request, null);
        $game->save();

        Activity::log('game.saved', ['meta' => ['name' => $game->name]]);

        return redirect()->route('admin.games.index')->with('status', $game->name.' kataloğa eklendi.');
    }

    /** Liste hâlinde ekleme: her satır bir oyun adı. Aynı platformda aynı adlı oyun atlanır. */
    public function bulk(Request $request)
    {
        $data = $request->validateWithBag('bulk', [
            'platform' => ['required', Rule::in(array_keys(Game::platforms()))],
            'names' => ['required', 'string', 'max:20000'],
        ], [], ['platform' => 'platform', 'names' => 'oyun listesi']);

        $names = collect(preg_split('/\R/u', $data['names']))
            ->map(fn ($line) => Str::limit(trim(preg_replace('/\s+/u', ' ', $line)), 120, ''))
            ->filter()->unique(fn ($name) => mb_strtolower($name));

        $existing = Game::where('platform', $data['platform'])->pluck('name')->map(fn ($n) => mb_strtolower($n))->all();
        $created = 0;

        foreach ($names as $name) {
            if (in_array(mb_strtolower($name), $existing, true)) {
                continue;
            }
            Game::create(['name' => $name, 'platform' => $data['platform']]);
            $created++;
        }

        Activity::log('game.saved', ['meta' => ['name' => $created.' oyun, liste']]);
        $skipped = $names->count() - $created;

        return redirect()->route('admin.games.index')->with('status', $created.' oyun eklendi.'.($skipped ? ' '.$skipped.' tanesi zaten vardı.' : '').' Kapakları oyunun "Düzenle" ekranından ekleyebilirsin.');
    }

    /**
     * Oyun adı yazılırken kapak önerisi: Steam mağaza aramasından ad ve dikey kapak.
     * Sonuç bir gün saklanır; Steam'e ulaşılamazsa boş liste döner, satıcı kendi görselini ekler.
     */
    public function suggest(Request $request)
    {
        $term = trim((string) $request->query('q'));

        if (mb_strlen($term) < 2 || mb_strlen($term) > 80) {
            return response()->json(['items' => []]);
        }

        $items = Cache::remember('game-suggest:v3:'.md5(mb_strtolower($term)), now()->addDay(), function () use ($term) {
            // Steam mağazasının kendi arama kutusunun kullandığı servis: tek istekte yalnızca oyunlar
            // ve görsellerin gerçek adresleri gelir (yeni oyunlarda dosya adı karma kodla saklanır).
            $query = [
                'search_term' => $term,
                'max_results' => 25, // geniş liste: satıcı yazmaya devam ederken tarayıcıda süzülür
                'context' => ['language' => 'turkish', 'country_code' => 'TR'],
                'data_request' => ['include_assets' => true],
                'filters' => ['type_filters' => ['include_games' => true]],
            ];

            try {
                $response = Http::timeout(5)->acceptJson()->get('https://api.steampowered.com/IStoreQueryService/SearchSuggestions/v1/', [
                    'input_json' => json_encode($query, JSON_UNESCAPED_UNICODE),
                ]);
            } catch (Throwable) {
                return null;
            }

            if (! $response->successful()) {
                return null;
            }

            // Demo, film müziği gibi eklentiler öneriyi kalabalıklaştırmasın.
            $extra = '/\b(dlc|soundtrack|ost|season pass|bundle|pack|paket|kit|kiti|expansion pass|upgrade|artbook|demo)\b/iu';

            return collect($response->json('response.store_items') ?? [])
                ->filter(fn ($item) => ($item['success'] ?? 1) === 1 && filled($item['name'] ?? null))
                ->reject(fn ($item) => preg_match($extra, (string) $item['name']))
                ->take(25)
                ->map(function ($item) {
                    $assets = $item['assets'] ?? [];
                    $url = fn (?string $file) => $file && isset($assets['asset_url_format'])
                        ? 'https://shared.akamai.steamstatic.com/store_item_assets/'.str_replace('${FILENAME}', $file, $assets['asset_url_format'])
                        : null;
                    $wide = $url($assets['header'] ?? null) ?? $url($assets['main_capsule'] ?? null) ?? $url($assets['small_capsule'] ?? null);

                    return [
                        'name' => trim(preg_replace('/\s+/u', ' ', str_replace(['™', '®', '©'], '', (string) $item['name']))),
                        'cover' => $url($assets['library_capsule'] ?? null),  // dikey 600×900, yoksa null
                        'wide' => $wide,                                    // yatay yedek
                    ];
                })
                ->filter(fn ($item) => $item['cover'] || $item['wide'])
                ->values()->all();
        });

        return response()->json(['items' => $items ?? []]);
    }

    public function edit(Game $game)
    {
        return view('admin.games.edit', ['game' => $game->loadCount('accounts'), 'platforms' => Game::platforms()]);
    }

    public function update(Request $request, Game $game)
    {
        $game->fill($this->validated($request));
        $game->cover = $request->boolean('remove_cover') ? null : $this->cover($request, $game->cover);
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
