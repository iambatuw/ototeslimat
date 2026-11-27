<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ProductKey;
use App\Models\RightRequest;
use App\Models\User;
use App\Support\Activity;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = User::where('role', User::ROLE_CUSTOMER)->withCount('keys')->latest();

        if ($q = trim((string) $request->input('q'))) {
            $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $q).'%';
            $query->where(fn ($w) => $w->where('email', 'like', $like)->orWhere('name', 'like', $like));
        }

        return view('admin.customers', [
            'customers' => $query->paginate(50)->withQueryString(),
            'filters' => $request->only('q'),
        ]);
    }

    /** Müşterinin her şeyi tek sayfada: anahtarlar, kalan haklar, istekler, son hareketler. */
    public function show(User $user)
    {
        abort_if($user->isAdmin(), 404);

        $logs = ActivityLog::with(['account', 'key', 'user'])->where('user_id', $user->id)->latest('id')->limit(30)->get();

        return view('admin.customer', [
            'customer' => $user,
            'keys' => $user->keys()->with(['account', 'package', 'selections', 'customer'])->latest('redeemed_at')->get(),
            'requests' => RightRequest::with(['key', 'account'])->where('user_id', $user->id)->latest()->limit(10)->get(),
            'logs' => $logs,
            'stats' => [
                'codes' => ActivityLog::where('user_id', $user->id)->where('event', 'code.issued')->count(),
                'failed' => ActivityLog::where('user_id', $user->id)->where('event', 'code.failed')->where('created_at', '>=', now()->subDays(7))->count(),
                'ips' => ActivityLog::where('user_id', $user->id)->where('created_at', '>=', now()->subDays(7))->whereNotNull('ip')->distinct()->count('ip'),
            ],
        ]);
    }

    /** Ek hak isteğini onaylar (+1 hak) ya da reddeder. */
    public function handleRequest(Request $request, RightRequest $rightRequest)
    {
        $data = $request->validate(['decision' => ['required', 'in:approve,reject']]);

        if ($rightRequest->status !== 'pending') {
            return back()->with('status', 'Bu istek zaten sonuçlandı.');
        }

        $approve = $data['decision'] === 'approve';

        DB::transaction(function () use ($rightRequest, $approve) {
            $rightRequest->forceFill(['status' => $approve ? 'approved' : 'rejected', 'handled_at' => now()])->save();

            if ($approve) {
                ProductKey::whereKey($rightRequest->product_key_id)->increment('bonus_uses');
            }
        });

        Activity::log($approve ? 'request.approved' : 'request.rejected', ['key' => $rightRequest->key, 'account' => $rightRequest->account, 'user_id' => $rightRequest->user_id]);

        return back()->with('status', $approve ? 'Onaylandı: müşteri hemen 1 kod daha alabilir.' : 'İstek reddedildi.');
    }

    public function toggle(User $user)
    {
        abort_if($user->isAdmin(), 404);

        $user->forceFill(['is_active' => ! $user->is_active])->save();
        Activity::log('customer.toggled', ['meta' => ['email' => $user->email, 'active' => $user->is_active]]);

        return back()->with('status', $user->email.($user->is_active ? ' yeniden açıldı.' : ' engellendi. Giriş yapamaz ve kod alamaz.'));
    }
}
