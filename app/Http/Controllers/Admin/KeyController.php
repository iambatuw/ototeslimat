<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\ProductKey;
use App\Support\Activity;
use App\Support\KeyCode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KeyController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.keys', [
            'keys' => $this->filtered($request)->with(['account:id,title,platform,deleted_at', 'package:id,name,deleted_at', 'customer:id,email'])->latest()->paginate(50)->withQueryString(),
            'accounts' => Account::orderBy('title')->get(['id', 'title']),
            'filters' => $request->only('q', 'account', 'status'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'account_id' => ['required_without:package_id', 'prohibits:package_id', 'nullable', Rule::exists('accounts', 'id')->whereNull('deleted_at')],
            'package_id' => ['required_without:account_id', 'nullable', Rule::exists('packages', 'id')->whereNull('deleted_at')],
            'count' => ['required', 'integer', 'between:1,500'],
            'max_uses' => ['nullable', 'integer', 'between:1,100000'],
            'valid_days' => ['nullable', 'integer', 'between:1,3650'],
            'cooldown_minutes' => ['nullable', 'integer', 'between:0,10080'],
            'label' => ['nullable', 'string', 'max:120'],
        ], [], [
            'account_id' => 'hesap', 'package_id' => 'paket', 'count' => 'adet', 'max_uses' => 'kullanım hakkı',
            'valid_days' => 'süre', 'cooldown_minutes' => 'bekleme süresi', 'label' => 'not',
        ]);

        $codes = DB::transaction(function () use ($data) {
            $codes = [];

            for ($i = 0; $i < $data['count']; $i++) {
                do {
                    $body = KeyCode::generate();
                    $hash = KeyCode::hash($body);
                } while (ProductKey::where('code_hash', $hash)->exists());

                $key = ProductKey::create([
                    'account_id' => $data['account_id'] ?? null,
                    'package_id' => $data['package_id'] ?? null,
                    'code' => KeyCode::format($body),
                    'code_hash' => $hash,
                    'label' => $data['label'] ?? null,
                    'max_uses' => $data['max_uses'] ?? null,
                    'valid_days' => $data['valid_days'] ?? null,
                    'cooldown_minutes' => $data['cooldown_minutes'] ?? 0,
                ]);
                $codes[] = $key->code;
            }

            return $codes;
        });

        $forPackage = ! empty($data['package_id']);

        Activity::log('key.created', [
            'account' => $forPackage ? null : Account::find($data['account_id']),
            'meta' => ['count' => count($codes), 'label' => $data['label'] ?? null, 'package_id' => $data['package_id'] ?? null],
        ]);

        $back = $forPackage
            ? route('admin.packages.index', ['paket' => $data['package_id']])
            : route('admin.accounts.index', ['hesap' => $data['account_id']]);

        return redirect($back)
            ->with('fresh_keys', $codes)
            ->with('status', count($codes).' anahtar üretildi.');
    }

    public function revoke(ProductKey $key)
    {
        $key->forceFill(['revoked_at' => now()])->save();
        Activity::log('key.revoked', ['key' => $key]);

        return back()->with('status', 'Anahtar iptal edildi. Müşteri artık bu anahtarla kod alamaz.');
    }

    public function restore(ProductKey $key)
    {
        $key->forceFill(['revoked_at' => null])->save();
        Activity::log('key.restored', ['key' => $key]);

        return back()->with('status', 'Anahtar yeniden açıldı.');
    }

    /** İptal edilmiş, süresi dolmuş ve hakkı bitmiş anahtarları siler. Kayıtlar kalır. */
    public function purge()
    {
        $ids = collect(['revoked', 'expired', 'used_up'])
            ->flatMap(fn ($status) => ProductKey::inStatus($status)->pluck('id'))
            ->unique();

        ProductKey::whereIn('id', $ids)->delete();
        Activity::log('key.purged', ['meta' => ['count' => $ids->count()]]);

        return back()->with('status', $ids->count().' eski anahtar silindi.');
    }

    public function export(Request $request): StreamedResponse
    {
        $query = $this->filtered($request)->with(['account:id,title,platform,deleted_at', 'package:id,name,deleted_at'])->latest();

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // Excel Türkçe karakterleri doğru okusun
            fputcsv($out, ['Anahtar', 'Hesap', 'Platform', 'Not', 'Kural', 'Durum', 'Eklenme', 'Bitiş', 'Kod alma'], ';');

            $query->chunk(500, function ($keys) use ($out) {
                foreach ($keys as $key) {
                    fputcsv($out, [
                        $key->code, $key->targetTitle(), $key->account?->platformLabel() ?? "Paket", $key->label,
                        $key->rulesSummary(), $key->statusLabel(),
                        $key->redeemed_at?->format('d.m.Y H:i'), $key->expires_at?->format('d.m.Y H:i'), $key->uses_count,
                    ], ';');
                }
            });

            fclose($out);
        }, 'anahtarlar-'.now()->format('Y-m-d-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filtered(Request $request): Builder
    {
        $query = ProductKey::query()->inStatus($request->input('status'));

        if ($account = $request->integer('account')) {
            $query->where('account_id', $account);
        }

        if ($package = $request->integer('package')) {
            $query->where('package_id', $package);
        }

        if ($q = trim((string) $request->input('q'))) {
            // Anahtar şifreli saklandığından tam anahtar özetle aranır; değilse nota bakılır.
            $body = KeyCode::normalize($q);
            $query->where(fn ($w) => $body
                ? $w->where('code_hash', KeyCode::hash($body))
                : $w->where('label', 'like', '%'.str_replace(['%', '_'], ['\%', '\_'], $q).'%'));
        }

        return $query;
    }
}
