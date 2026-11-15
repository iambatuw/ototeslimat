<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\ProductKey;
use App\Services\Guard\CodeService;
use App\Services\Guard\GuardException;
use App\Support\Activity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        if ($request->user()->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        $keys = $request->user()->keys()->with('account')->latest('redeemed_at')->get();

        return view('customer.home', ['keys' => $keys]);
    }

    public function redeem(Request $request)
    {
        $data = $request->validate(['key' => ['required', 'string', 'max:64']]);
        $user = $request->user();
        $key = ProductKey::findByCode($data['key'])?->load('account');

        $fail = function (string $reason, string $message) use ($key, $data) {
            Activity::log('key.invalid', ['key' => $key, 'meta' => ['reason' => $reason, 'input' => mb_substr($data['key'], 0, 40)]]);

            throw ValidationException::withMessages(['key' => $message]);
        };

        if (! $key || $key->revoked_at || $key->account->trashed()) {
            $fail($key ? 'unavailable' : 'not_found', 'Bu anahtar geçerli değil. Harfleri kontrol edip tekrar dene.');
        }

        if ($key->redeemed_by === $user->id) {
            return redirect()->route('home')->with('status', $key->account->title.' zaten hesabında.')->with('opened', $key->id);
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

        return redirect()->route('home')
            ->with('status', $key->account->title.' hesabına eklendi. Kodunu aşağıdan alabilirsin.')
            ->with('opened', $key->id);
    }

    public function code(Request $request, ProductKey $key, string $type, CodeService $codes): JsonResponse
    {
        abort_unless($key->redeemed_by === $request->user()->id, 404);
        abort_unless(in_array($type, [CodeService::TYPE_MAIL, CodeService::TYPE_TOTP], true), 404);

        try {
            $result = $codes->issue($key, $type);
        } catch (GuardException $e) {
            Activity::log('code.failed', ['key' => $key, 'meta' => ['type' => $type, 'reason' => $e->reason]]);

            if ($e->getPrevious()) {
                Log::warning('Kod alınamadı', ['account' => $key->account_id, 'error' => $e->getPrevious()->getMessage()]);
            }

            return response()->json(['message' => $e->getMessage()], 422);
        }

        Activity::log('code.issued', ['key' => $key, 'meta' => ['type' => $type, 'code' => mb_substr($result['code'], 0, 300)]]);

        return response()->json($result);
    }
}
