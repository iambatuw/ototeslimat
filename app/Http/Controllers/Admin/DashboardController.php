<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\ProductKey;
use App\Models\User;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $days = 14;
        $start = now()->subDays($days - 1)->startOfDay();

        $issued = ActivityLog::where('event', 'code.issued')->where('created_at', '>=', $start)
            ->pluck('created_at')
            ->countBy(fn (Carbon $at) => $at->format('Y-m-d'));

        $series = collect(range($days - 1, 0))->map(function ($ago) use ($issued) {
            $day = now()->subDays($ago);

            return ['label' => $day->format('d.m'), 'value' => $issued->get($day->format('Y-m-d'), 0)];
        });

        return view('admin.dashboard', [
            'stats' => [
                'unused' => ProductKey::inStatus('unused')->count(),
                'active' => ProductKey::inStatus('active')->count(),
                'codes_month' => ActivityLog::where('event', 'code.issued')->where('created_at', '>=', now()->startOfMonth())->count(),
                'accounts' => Account::where('is_active', true)->count(),
                'customers' => User::where('role', User::ROLE_CUSTOMER)->count(),
            ],
            'series' => $series,
            'failing' => ActivityLog::where('event', 'code.failed')->where('created_at', '>=', now()->subDay())->count(),
            'recent' => ActivityLog::with(['user:id,email', 'account:id,title,platform,deleted_at'])
                ->whereIn('event', ['code.issued', 'code.failed', 'key.redeemed', 'key.invalid'])
                ->latest('id')->limit(8)->get(),
        ]);
    }
}
