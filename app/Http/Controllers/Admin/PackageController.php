<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Package;
use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PackageController extends Controller
{
    public function index()
    {
        return view('admin.packages.index', [
            'packages' => Package::with('accounts:id,title')
                ->withCount(['keys', 'keys as unused_count' => fn ($q) => $q->inStatus('unused')])
                ->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.packages.form', ['package' => new Package, 'accounts' => $this->accounts(), 'chosen' => []]);
    }

    public function store(Request $request)
    {
        $package = new Package;
        $this->save($request, $package);
        Activity::log('package.saved', ['meta' => ['name' => $package->name, 'new' => true]]);

        return redirect()->route('admin.keys.index', ['hedef' => 'package:'.$package->id])->with('status', $package->name.' oluşturuldu. Şimdi bu paketin anahtarlarını üret.');
    }

    public function edit(Package $package)
    {
        return view('admin.packages.form', [
            'package' => $package,
            'accounts' => $this->accounts(),
            'chosen' => $package->accounts()->pluck('accounts.id')->all(),
        ]);
    }

    public function update(Request $request, Package $package)
    {
        $this->save($request, $package);
        Activity::log('package.saved', ['meta' => ['name' => $package->name]]);

        return redirect()->route('admin.packages.index')->with('status', $package->name.' güncellendi.');
    }

    public function destroy(Package $package)
    {
        $package->delete();
        Activity::log('package.deleted', ['meta' => ['name' => $package->name]]);

        return redirect()->route('admin.packages.index')->with('status', $package->name.' silindi. Bağlı anahtarlar artık kod vermez.');
    }

    private function accounts()
    {
        return Account::orderBy('platform')->orderBy('title')->get(['id', 'title', 'platform', 'is_active']);
    }

    private function save(Request $request, Package $package): void
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:300'],
            'pick_count' => ['required', 'integer', 'between:1,100'],
            'is_active' => ['boolean'],
            'accounts' => ['required', 'array', 'min:1'],
            'accounts.*' => ['integer', Rule::exists('accounts', 'id')->whereNull('deleted_at')],
        ], [
            'accounts.required' => 'Pakete en az bir hesap ekle.',
        ], ['name' => 'paket adı', 'description' => 'açıklama', 'pick_count' => 'seçim sayısı', 'accounts' => 'hesaplar']);

        validator($data, [])->after(function (Validator $v) use ($data) {
            if ($data['pick_count'] > count($data['accounts'])) {
                $v->errors()->add('pick_count', 'Seçim sayısı paketteki hesap sayısından ('.count($data['accounts']).') büyük olamaz.');
            }
        })->validate();

        DB::transaction(function () use ($package, $data, $request) {
            $package->fill([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'pick_count' => $data['pick_count'],
                'is_active' => $request->boolean('is_active'),
            ])->save();

            $package->accounts()->sync(array_unique($data['accounts']));
        });
    }
}
