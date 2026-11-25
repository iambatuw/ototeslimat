<?php

namespace App\Http\Controllers;

use App\Models\ProductKey;
use App\Models\Setting;
use App\Services\Guard\CodeService;
use App\Services\Guard\GuardException;
use App\Support\Activity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Hızlı onay: müşteri hesap açmadan yalnızca anahtarla kodunu alır (/onay).
 *
 * Anahtar oturuma bağlanır, adres çubuğunda görünmez. Hızlı onayda ilk
 * kullanılan anahtar artık bir müşteri hesabına eklenemez (ve tersi);
 * böylece aynı anahtar iki yoldan iki kişiye kod veremez. Paket anahtarları
 * listeden seçim gerektirdiği için yalnızca hesap üzerinden çalışır.
 */
class QuickCodeController extends Controller
{
    private const SESSION = 'quick_key';

    public function show(Request $request)
    {
        abort_unless(Setting::flag('quick_code'), 404);

        $key = $this->current($request);

        return view('quick', ['key' => $key, 'account' => $key?->account]);
    }

    public function open(Request $request)
    {
        abort_unless(Setting::flag('quick_code'), 404);

        $data = $request->validate(['key' => ['required', 'string', 'max:64']], [], ['key' => 'anahtar']);
        $key = ProductKey::findByCode($data['key'])?->load('account');

        $fail = function (string $reason, string $message) use ($key, $data) {
            Activity::log('key.invalid', ['key' => $key, 'meta' => ['reason' => $reason, 'input' => mb_substr($data['key'], 0, 40), 'quick' => true]]);

            throw ValidationException::withMessages(['key' => $message]);
        };

        if (! $key || $key->revoked_at || $key->targetUnavailable()) {
            $fail($key ? 'unavailable' : 'not_found', 'Bu anahtar geçerli değil. Harfleri kontrol edip tekrar dene.');
        }

        if ($key->redeemed_by) {
            $fail('taken', 'Bu anahtar bir müşteri hesabına eklenmiş. Giriş yapıp "Kodlarım" sayfasından kodunu al.');
        }

        if ($key->isPackage()) {
            $fail('package', 'Paket anahtarında hesap seçmen gerekiyor. Ücretsiz hesap açıp anahtarı oraya ekle.');
        }

        // İlk kullanım: geçerlilik süresi şimdi başlar. Aynı anda iki istekte süre bir kez yazılır.
        if (! $key->redeemed_at) {
            $now = now();
            ProductKey::whereKey($key->id)->whereNull('redeemed_at')->update([
                'redeemed_at' => $now,
                'expires_at' => $key->valid_days ? $now->copy()->addDays($key->valid_days) : null,
                'updated_at' => $now,
            ]);
            Activity::log('key.redeemed', ['key' => $key, 'meta' => ['quick' => true]]);
        }

        $request->session()->put(self::SESSION, $key->id);
        $request->session()->regenerate();

        return redirect()->route('quick');
    }

    public function close(Request $request)
    {
        $request->session()->forget(self::SESSION);

        return redirect()->route('quick');
    }

    public function code(Request $request, string $type, CodeService $codes): JsonResponse
    {
        abort_unless(Setting::flag('quick_code'), 404);
        abort_unless(in_array($type, [CodeService::TYPE_MAIL, CodeService::TYPE_TOTP], true), 404);

        $key = $this->current($request);

        if (! $key) {
            return response()->json(['message' => 'Oturumun süresi doldu. Sayfayı yenileyip anahtarını yeniden gir.'], 422);
        }

        try {
            $result = $codes->issue($key, $key->account, $type);
        } catch (GuardException $e) {
            if (! ($request->boolean('poll') && $e->reason === 'no_mail')) {
                Activity::log('code.failed', ['key' => $key, 'meta' => ['type' => $type, 'reason' => $e->reason, 'quick' => true]]);
            }

            if ($e->getPrevious()) {
                Log::warning('Kod alınamadı', ['account' => $key->account_id, 'error' => $e->getPrevious()->getMessage()]);
            }

            return response()->json(['message' => $e->getMessage(), 'reason' => $e->reason, 'retry' => $e->reason === 'no_mail'], 422);
        }

        Activity::log('code.issued', ['key' => $key, 'meta' => ['type' => $type, 'code' => mb_substr($result['code'], 0, 300), 'quick' => true]]);

        return response()->json($result);
    }

    /** Oturumdaki anahtar hâlâ hızlı onayda kullanılabiliyorsa onu verir. */
    private function current(Request $request): ?ProductKey
    {
        $id = $request->session()->get(self::SESSION);
        $key = $id ? ProductKey::with('account')->find($id) : null;

        if (! $key || $key->redeemed_by || $key->isPackage() || ! $key->account) {
            $request->session()->forget(self::SESSION);

            return null;
        }

        return $key;
    }
}
