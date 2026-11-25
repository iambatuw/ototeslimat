<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\ProductKey;
use App\Services\Guard\CodeService;
use App\Services\Guard\GuardException;
use App\Support\Activity;
use App\Support\Wallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        if ($request->user()->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        $keys = $request->user()->keys()
            ->with(['account', 'package.accounts', 'selections'])
            ->latest('redeemed_at')
            ->get();

        $user = $request->user();

        // Müşterinin hesaplara verdiği adlar ve her hesaptan en son ne zaman kod aldığı:
        // aynı platformdan birkaç hesabı olan müşteri hangisini en son kullandığını görsün.
        $lastCodes = ActivityLog::where('user_id', $user->id)->where('event', 'code.issued')
            ->selectRaw('account_id, max(created_at) as last')->groupBy('account_id')->pluck('last', 'account_id')
            ->map(fn ($at) => Carbon::parse($at))->all();

        $selected = is_string($request->query('hesap')) ? $request->query('hesap') : null;

        return view('customer.home', Wallet::build($keys, $user->accountLabels(), $lastCodes, session('opened'), $selected));
    }

    public function redeem(Request $request)
    {
        $data = $request->validate(['key' => ['required', 'string', 'max:64']]);
        $user = $request->user();
        $key = ProductKey::findByCode($data['key'])?->load('account', 'package');

        $fail = function (string $reason, string $message) use ($key, $data) {
            Activity::log('key.invalid', ['key' => $key, 'meta' => ['reason' => $reason, 'input' => mb_substr($data['key'], 0, 40)]]);

            throw ValidationException::withMessages(['key' => $message]);
        };

        if (! $key || $key->revoked_at || $key->targetUnavailable()) {
            $fail($key ? 'unavailable' : 'not_found', 'Bu anahtar geçerli değil. Harfleri kontrol edip tekrar dene.');
        }

        if (! $key->redeemed_by && $key->redeemed_at) {
            $fail('quick', 'Bu anahtar hızlı onay sayfasında kullanılıyor. Kodunu '.route('quick').' adresinden al.');
        }

        if ($key->redeemed_by === $user->id) {
            return redirect()->route('home')->with('status', $key->targetTitle().' zaten hesabında.')->with('opened', $key->id);
        }

        // Aynı anda iki kişi denese bile yalnızca biri alabilir.
        $now = now();
        $claimed = ProductKey::whereKey($key->id)->whereNull('redeemed_by')->update([
            'redeemed_by' => $user->id,
            'redeemed_at' => $now,
            'expires_at' => $key->valid_days ? $now->copy()->addDays($key->valid_days) : null,
            'updated_at' => $now,
        ]);

        if (! $claimed) {
            $fail('taken', 'Bu anahtar başka bir hesapta kullanılmış. Satıcıyla iletişime geç.');
        }

        Activity::log('key.redeemed', ['key' => $key]);

        $message = $key->isPackage()
            ? $key->package->name.' eklendi. Listeden '.$key->package->pick_count.' hesap seç.'
            : $key->account->title.' hesabına eklendi. Kodunu aşağıdan alabilirsin.';

        return redirect()->route('home')->with('status', $message)->with('opened', $key->id);
    }

    /**
     * Müşteri bir hesaba kendi adını verir ya da adı siler. Aynı platformdan birkaç
     * hesabı olan müşteri (örneğin üç Steam) kartlarını bu adla ayırır.
     */
    public function label(Request $request, Account $account)
    {
        $user = $request->user();
        abort_unless($user->canUseAccount($account), 404);

        $data = $request->validate(['label' => ['nullable', 'string', 'max:40']], [], ['label' => 'ad']);
        $label = trim(preg_replace('/\s+/u', ' ', (string) ($data['label'] ?? '')));

        if ($label === '') {
            DB::table('account_labels')->where(['user_id' => $user->id, 'account_id' => $account->id])->delete();
        } else {
            DB::table('account_labels')->updateOrInsert(
                ['user_id' => $user->id, 'account_id' => $account->id],
                ['label' => $label, 'updated_at' => now(), 'created_at' => now()],
            );
        }

        if ($request->expectsJson()) {
            return response()->json(['label' => $label]);
        }

        return back()->with('status', $label === '' ? 'Kartın adı kaldırıldı.' : 'Kartın adı kaydedildi.');
    }

    /** Paket anahtarında listeden bir hesap seçer. Seçim geri alınmaz. */
    public function pick(Request $request, ProductKey $key, CodeService $codes)
    {
        abort_unless($key->redeemed_by === $request->user()->id && $key->isPackage(), 404);

        $data = $request->validate(['account_id' => ['required', 'integer']]);
        $package = $key->package;
        $account = $package->accounts()->whereKey($data['account_id'])->first();

        try {
            $codes->guard($key);

            if (! $account || $package->trashed() || ! $package->is_active) {
                throw new GuardException('Bu hesap pakette yok ya da paket kapatıldı.', 'pick_invalid');
            }

            if ($account->trashed() || ! $account->is_active) {
                throw new GuardException('Bu hesap şu an seçilemiyor.', 'pick_inactive');
            }

            // Kilitli sayım: aynı anda iki seçim hakkı aşamaz.
            DB::transaction(function () use ($key, $account, $package) {
                ProductKey::whereKey($key->id)->lockForUpdate()->first();
                $chosen = DB::table('key_selections')->where('product_key_id', $key->id);

                if ((clone $chosen)->where('account_id', $account->id)->exists()) {
                    return;
                }

                if ($chosen->count() >= $package->pick_count) {
                    throw new GuardException('Seçim hakkın doldu ('.$package->pick_count.' hesap).', 'pick_full');
                }

                $key->selections()->attach($account->id, ['created_at' => now()]);
            });
        } catch (GuardException $e) {
            return back()->with('error', $e->getMessage())->with('opened', $key->id);
        }

        Activity::log('key.picked', ['key' => $key, 'account' => $account]);

        return back()->with('status', $account->title.' seçildi. Kodunu şimdi alabilirsin.')->with('opened', $key->id);
    }

    public function code(Request $request, ProductKey $key, Account $account, string $type, CodeService $codes): JsonResponse
    {
        abort_unless($key->redeemed_by === $request->user()->id, 404);
        abort_unless(in_array($type, [CodeService::TYPE_MAIL, CodeService::TYPE_TOTP], true), 404);
        abort_unless($key->grantsAccount($account), 404);

        try {
            $result = $codes->issue($key, $account, $type);
        } catch (GuardException $e) {
            // Ekran kodu beklerken kendiliğinden tekrar sorar; her "henüz yok" kayda yazılmaz.
            $repeat = $request->boolean('poll') && $e->reason === 'no_mail';

            if (! $repeat) {
                Activity::log('code.failed', ['key' => $key, 'account' => $account, 'meta' => ['type' => $type, 'reason' => $e->reason]]);
            }

            if ($e->getPrevious()) {
                Log::warning('Kod alınamadı', ['account' => $account->id, 'error' => $e->getPrevious()->getMessage()]);
            }

            return response()->json([
                'message' => $e->getMessage(),
                'reason' => $e->reason,
                'retry' => $e->reason === 'no_mail',
            ], 422);
        }

        Activity::log('code.issued', ['key' => $key, 'account' => $account, 'meta' => ['type' => $type, 'code' => mb_substr($result['code'], 0, 300)]]);

        return response()->json($result);
    }
}
