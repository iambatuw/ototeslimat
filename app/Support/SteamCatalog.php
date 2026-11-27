<?php

namespace App\Support;

use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Steam mağazasının arama kutusu servisi (IStoreQueryService/SearchSuggestions): oyun adı,
 * görsellerin gerçek adresleri ve Türkçe kısa açıklama tek istekte gelir. Anahtar gerekmez.
 * Steam'e ulaşılamazsa boş sonuç döner; satıcı kendi görselini ve açıklamasını yazar.
 */
class SteamCatalog
{
    private const URL = 'https://api.steampowered.com/IStoreQueryService/SearchSuggestions/v1/';

    /** Demo, film müziği gibi eklentiler önerilmez. */
    private const EXTRA = '/\b(dlc|soundtrack|ost|season pass|bundle|pack|paket|kit|kiti|expansion pass|upgrade|artbook|demo)\b/iu';

    /** Yazılan kelimeye uyan oyunlar (en fazla $limit). Sonuç bir gün saklanır. */
    public static function search(string $term, int $limit = 25): array
    {
        $term = trim($term);

        if (mb_strlen($term) < 2 || mb_strlen($term) > 80) {
            return [];
        }

        $key = 'steam-search:v4:'.$limit.':'.md5(mb_strtolower($term));
        $cached = Cache::get($key);

        if ($cached !== null) {
            return $cached;
        }

        try {
            $response = Http::timeout(6)->acceptJson()->get(self::URL, self::query($term, $limit));
        } catch (Throwable) {
            return [];
        }

        $items = self::parse($response);

        if ($response->successful()) {
            Cache::put($key, $items, now()->addDay());
        }

        return $items;
    }

    /**
     * Liste hâlinde eklemede her ad için en iyi eşleşme; istekler 8'erli paralel gider.
     *
     * @param  array<int, string>  $names
     * @return array<string, array|null>  ad => oyun bilgisi ya da null
     */
    public static function bestMatches(array $names): array
    {
        $found = [];

        foreach (array_chunk(array_values(array_unique($names)), 8) as $chunk) {
            try {
                $responses = Http::pool(fn (Pool $pool) => array_map(
                    fn ($name) => $pool->as($name)->timeout(8)->acceptJson()->get(self::URL, self::query($name, 5)),
                    $chunk,
                ));
            } catch (Throwable) {
                $responses = [];
            }

            foreach ($chunk as $name) {
                $response = $responses[$name] ?? null;
                $found[$name] = $response instanceof Response ? self::pick($name, self::parse($response)) : null;
            }
        }

        return $found;
    }

    /** Aynı adı taşıyan sonucu tercih eder; yoksa Steam'in ilk sonucu. */
    private static function pick(string $name, array $items): ?array
    {
        $wanted = self::fold($name);

        foreach ($items as $item) {
            if (self::fold($item['name']) === $wanted) {
                return $item;
            }
        }

        return $items[0] ?? null;
    }

    private static function query(string $term, int $limit): array
    {
        return ['input_json' => json_encode([
            'search_term' => $term,
            'max_results' => $limit,
            'context' => ['language' => 'turkish', 'country_code' => 'TR'],
            'data_request' => ['include_assets' => true, 'include_basic_info' => true],
            'filters' => ['type_filters' => ['include_games' => true]],
        ], JSON_UNESCAPED_UNICODE)];
    }

    private static function parse(Response $response): array
    {
        if (! $response->successful()) {
            return [];
        }

        return Collection::make($response->json('response.store_items') ?? [])
            ->filter(fn ($item) => ($item['success'] ?? 1) === 1 && filled($item['name'] ?? null))
            ->reject(fn ($item) => preg_match(self::EXTRA, (string) $item['name']))
            ->map(function ($item) {
                $assets = $item['assets'] ?? [];
                $url = fn (?string $file) => $file && isset($assets['asset_url_format'])
                    ? 'https://shared.akamai.steamstatic.com/store_item_assets/'.str_replace('${FILENAME}', $file, $assets['asset_url_format'])
                    : null;

                return [
                    'name' => trim(preg_replace('/\s+/u', ' ', str_replace(['™', '®', '©'], '', (string) $item['name']))),
                    'cover' => $url($assets['library_capsule'] ?? null),
                    'wide' => $url($assets['header'] ?? null) ?? $url($assets['main_capsule'] ?? null) ?? $url($assets['small_capsule'] ?? null),
                    'about' => self::about($item['basic_info']['short_description'] ?? ''),
                ];
            })
            ->filter(fn ($item) => $item['cover'] || $item['wide'])
            ->values()->all();
    }

    /** Kısa açıklama: etiketsiz, tek satır, en fazla 300 karakter (kelime ortasında kesmez). */
    private static function about(string $text): string
    {
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim(preg_replace('/\s+/u', ' ', $text));

        // Tamamı büyük harfli açılış cümlesi ("YENİ FANTASTİK AKSİYON...") cümle düzenine çevrilir.
        $text = preg_replace_callback('/^([^.!?]{8,}[.!?])\s/u', function ($m) {
            $sentence = $m[1];

            return mb_strtoupper($sentence, 'UTF-8') === $sentence
                ? mb_substr($sentence, 0, 1, 'UTF-8').mb_strtolower(str_replace(['İ', 'I'], ['i', 'ı'], mb_substr($sentence, 1, null, 'UTF-8')), 'UTF-8').' '
                : $m[0];
        }, $text);

        return Str::limit($text, 297, '...', preserveWords: true);
    }

    private static function fold(string $text): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', ' ', Str::lower(Str::ascii($text))));
    }
}
