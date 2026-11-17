<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\ProductKey;
use App\Models\User;
use Illuminate\Http\Request;

/** Tek kutudan arama: anahtar, müşteri ya da hesap. */
class SearchController extends Controller
{
    public function __invoke(Request $request)
    {
        $q = trim((string) $request->validate(['q' => ['nullable', 'string', 'max:120']])['q'] ?? '');

        if (mb_strlen($q) < 2) {
            return view('admin.search', ['q' => $q, 'keys' => collect(), 'customers' => collect(), 'accounts' => collect()]);
        }

        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $q).'%';

        // Anahtarlar şifreli saklandığı için yalnızca tam anahtarla ya da notuyla bulunur.
        $exact = ProductKey::findByCode($q);
        $keys = ProductKey::with(['account', 'package', 'customer:id,email'])
            ->where('label', 'like', $like)
            ->when($exact, fn ($query) => $query->orWhere('id', $exact->id))
            ->latest('id')->limit(8)->get();

        $customers = User::where('role', User::ROLE_CUSTOMER)
            ->where(fn ($w) => $w->where('email', 'like', $like)->orWhere('name', 'like', $like))
            ->withCount('keys')->limit(8)->get();

        $accounts = Account::where(fn ($w) => $w->where('title', 'like', $like)->orWhere('mail_username', 'like', $like))
            ->orderBy('title')->limit(8)->get();

        // Tek sonuç bir hesapsa doğrudan ona git.
        if ($accounts->count() === 1 && $keys->isEmpty() && $customers->isEmpty()) {
            return redirect()->route('admin.accounts.edit', $accounts->first());
        }

        return view('admin.search', compact('q', 'keys', 'customers', 'accounts'));
    }
}
