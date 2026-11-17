<?php

namespace App\Support;

use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\Package;
use App\Models\ProductKey;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/** Panelin ve günlük özetin kullandığı rakamlar. Hepsi hareket kayıtlarından çıkar; ayrı sayaç tutulmaz. */
class StoreInsights
{
    private const ISSUED = "sum(case when activity_logs.event = 'code.issued' then 1 else 0 end)";

    private const FAILED = "sum(case when activity_logs.event = 'code.failed' then 1 else 0 end)";

    /**
     * Son $hours saatte kod veremeyen hesaplar, en çok hata veren başta.
     *
     * @return Collection<int, object{account: Account, failed: int, issued: int, reason: ?string}>
     */
    public static function troubledAccounts(int $hours = 24, int $limit = 5): Collection
    {
        $since = now()->subHours($hours);

        $rows = ActivityLog::query()
            ->selectRaw('account_id, '.self::FAILED.' as failed, '.self::ISSUED.' as issued')
            ->whereIn('event', ['code.issued', 'code.failed'])
            ->whereNotNull('account_id')
            ->where('created_at', '>=', $since)
            ->groupBy('account_id')
            ->havingRaw(self::FAILED.' > 0')
            ->orderByDesc('failed')
            ->limit($limit)
            ->get();

        $accounts = Account::whereIn('id', $rows->pluck('account_id'))->get()->keyBy('id');

        // Her hesabın en son hata nedeni
        $reasons = ActivityLog::where('event', 'code.failed')
            ->whereIn('account_id', $rows->pluck('account_id'))
            ->where('created_at', '>=', $since)
            ->latest('id')
            ->get(['account_id', 'meta'])
            ->unique('account_id')
            ->mapWithKeys(fn (ActivityLog $log) => [$log->account_id => $log->meta['reason'] ?? null]);

        return $rows
            ->filter(fn ($row) => $accounts->has($row->account_id))
            ->map(fn ($row) => (object) [
                'account' => $accounts[$row->account_id],
                'failed' => (int) $row->failed,
                'issued' => (int) $row->issued,
                'reason' => ActivityLog::REASONS[$reasons[$row->account_id] ?? ''] ?? null,
            ])
            ->values();
    }

    /** Satılmamış anahtarı eşiğin altına düşen hesap ve paketler. Hiç anahtarı üretilmemiş olanlar sayılmaz. */
    public static function lowStock(): Collection
    {
        $threshold = self::lowStockThreshold();
        $counts = ['keys', 'keys as unused_count' => fn ($q) => $q->inStatus('unused')];

        $pick = fn (Collection $items, string $type) => $items
            ->filter(fn ($item) => $item->keys_count > 0 && $item->unused_count <= $threshold)
            ->map(fn ($item) => (object) [
                'title' => $type === 'account' ? $item->title : $item->name,
                'target' => $type.':'.$item->id,
                'platform' => $type === 'account' ? $item->platform : null,
                'left' => (int) $item->unused_count,
            ]);

        return $pick(Account::where('is_active', true)->withCount($counts)->get(), 'account')
            ->concat($pick(Package::where('is_active', true)->withCount($counts)->get(), 'package'))
            ->sortBy('left')
            ->values();
    }

    public static function lowStockThreshold(): int
    {
        return max(0, (int) Setting::get('low_stock_threshold', 3));
    }

    /** Platform başına verilen ve verilemeyen kod sayısı. */
    public static function platforms(int $days = 30): Collection
    {
        $labels = collect(config('guard.platforms'))->map(fn ($p) => $p['label']);

        return ActivityLog::query()
            ->join('accounts', 'accounts.id', '=', 'activity_logs.account_id')
            ->selectRaw('accounts.platform as platform, '.self::ISSUED.' as issued, '.self::FAILED.' as failed')
            ->whereIn('activity_logs.event', ['code.issued', 'code.failed'])
            ->where('activity_logs.created_at', '>=', now()->subDays($days))
            ->groupBy('accounts.platform')
            ->get()
            ->map(fn ($row) => (object) [
                'key' => $row->platform,
                'label' => $labels[$row->platform] ?? $row->platform,
                'issued' => (int) $row->issued,
                'failed' => (int) $row->failed,
            ])
            ->sortByDesc('issued')
            ->values();
    }

    /** En çok kod alan müşteriler. Aynı anahtarla aşırı kod çekenleri görmek için. */
    public static function topCustomers(int $days = 30, int $limit = 5): Collection
    {
        $rows = ActivityLog::query()
            ->selectRaw('user_id, count(*) as codes')
            ->where('event', 'code.issued')
            ->whereNotNull('user_id')
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('user_id')
            ->orderByDesc('codes')
            ->limit($limit)
            ->get();

        $users = User::whereIn('id', $rows->pluck('user_id'))->get()->keyBy('id');

        return $rows
            ->filter(fn ($row) => $users->has($row->user_id))
            ->map(fn ($row) => (object) ['user' => $users[$row->user_id], 'codes' => (int) $row->codes])
            ->values();
    }

    /** Bir günün özeti (günlük e-posta için). */
    public static function day(Carbon $day): array
    {
        $from = $day->copy()->startOfDay();
        $to = $day->copy()->endOfDay();
        $count = fn (string $event) => ActivityLog::where('event', $event)->whereBetween('created_at', [$from, $to])->count();

        return [
            'date' => $from,
            'issued' => $count('code.issued'),
            'failed' => $count('code.failed'),
            'redeemed' => $count('key.redeemed'),
            'registered' => $count('auth.registered'),
            'unused' => ProductKey::inStatus('unused')->count(),
        ];
    }
}
