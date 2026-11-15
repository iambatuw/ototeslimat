<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Activity;
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

    public function toggle(User $user)
    {
        abort_if($user->isAdmin(), 404);

        $user->forceFill(['is_active' => ! $user->is_active])->save();
        Activity::log('customer.toggled', ['meta' => ['email' => $user->email, 'active' => $user->is_active]]);

        return back()->with('status', $user->email.($user->is_active ? ' yeniden açıldı.' : ' engellendi. Giriş yapamaz ve kod alamaz.'));
    }
}
