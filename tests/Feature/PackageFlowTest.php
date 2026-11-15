<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Package;
use App\Models\ProductKey;
use App\Models\User;
use App\Support\KeyCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackageFlowTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $title): Account
    {
        return Account::create(['platform' => 'chatgpt', 'title' => $title, 'totp_secret' => 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ']);
    }

    /** @return array{0: ProductKey, 1: User, 2: Account[]} */
    private function redeemedPackage(int $pick = 2, array $rules = []): array
    {
        $accounts = [$this->account('A'), $this->account('B'), $this->account('C')];
        $package = Package::create(['name' => 'Paket', 'pick_count' => $pick]);
        $package->accounts()->sync(collect($accounts)->pluck('id'));

        $body = KeyCode::generate();
        ProductKey::create(['package_id' => $package->id, 'code' => KeyCode::format($body), 'code_hash' => KeyCode::hash($body)] + $rules);

        $customer = User::factory()->create();
        $this->actingAs($customer)->post('/anahtar', ['key' => KeyCode::format($body)])->assertRedirect('/kodlarim');

        return [ProductKey::where('package_id', $package->id)->first(), $customer, $accounts];
    }

    public function test_customer_picks_accounts_up_to_the_limit(): void
    {
        [$key, $customer, [$a, $b, $c]] = $this->redeemedPackage(2);

        $this->actingAs($customer)->post("/paket/{$key->id}/sec", ['account_id' => $a->id])->assertSessionHas('status');
        $this->actingAs($customer)->post("/paket/{$key->id}/sec", ['account_id' => $a->id]); // aynı hesap tekrar: hak düşmez
        $this->actingAs($customer)->post("/paket/{$key->id}/sec", ['account_id' => $b->id])->assertSessionHas('status');
        $this->actingAs($customer)->post("/paket/{$key->id}/sec", ['account_id' => $c->id])->assertSessionHas('error');

        $this->assertEqualsCanonicalizing([$a->id, $b->id], $key->selections()->pluck('accounts.id')->all());
    }

    public function test_codes_only_for_picked_accounts(): void
    {
        [$key, $customer, [$a, $b]] = $this->redeemedPackage(1);
        $this->actingAs($customer)->post("/paket/{$key->id}/sec", ['account_id' => $a->id]);

        $this->actingAs($customer)->postJson("/kod/{$key->id}/{$a->id}/2fa")->assertOk();
        $this->actingAs($customer)->postJson("/kod/{$key->id}/{$b->id}/2fa")->assertNotFound();
    }

    public function test_cannot_pick_account_outside_package(): void
    {
        [$key, $customer] = $this->redeemedPackage();
        $outside = $this->account('Dışarıda');

        $this->actingAs($customer)->post("/paket/{$key->id}/sec", ['account_id' => $outside->id])->assertSessionHas('error');
        $this->assertSame(0, $key->selections()->count());
    }

    public function test_other_customer_cannot_pick(): void
    {
        [$key, , [$a]] = $this->redeemedPackage();

        $this->actingAs(User::factory()->create())->post("/paket/{$key->id}/sec", ['account_id' => $a->id])->assertNotFound();
    }

    public function test_revoked_package_key_cannot_pick(): void
    {
        [$key, $customer, [$a]] = $this->redeemedPackage();
        $key->forceFill(['revoked_at' => now()])->save();

        $this->actingAs($customer)->post("/paket/{$key->id}/sec", ['account_id' => $a->id])->assertSessionHas('error');
        $this->assertSame(0, $key->selections()->count());
    }

    public function test_admin_creates_package_and_generates_keys(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => User::ROLE_ADMIN])->save();
        $a = $this->account('A');
        $b = $this->account('B');

        // seçim sayısı hesap sayısından büyük olamaz
        $this->actingAs($admin)->post('/yonetim/paketler', ['name' => 'P', 'pick_count' => 3, 'accounts' => [$a->id, $b->id], 'is_active' => 1])
            ->assertSessionHasErrors('pick_count');

        $this->actingAs($admin)->post('/yonetim/paketler', ['name' => 'P', 'pick_count' => 1, 'accounts' => [$a->id, $b->id], 'is_active' => 1])
            ->assertRedirect();
        $package = Package::first();
        $this->assertSame(2, $package->accounts()->count());

        $this->actingAs($admin)->post('/yonetim/anahtarlar', ['package_id' => $package->id, 'count' => 4])->assertRedirect();
        $this->assertSame(4, ProductKey::where('package_id', $package->id)->whereNull('account_id')->count());

        // hem hesap hem paket verilemez
        $this->actingAs($admin)->post('/yonetim/anahtarlar', ['package_id' => $package->id, 'account_id' => $a->id, 'count' => 1])
            ->assertSessionHasErrors('account_id');

        foreach (['/yonetim/paketler', "/yonetim/paketler?paket={$package->id}", '/yonetim/paketler/yeni', "/yonetim/paketler/{$package->id}/duzenle", '/yonetim/anahtarlar'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_customer_home_renders_package(): void
    {
        [$key, $customer, [$a]] = $this->redeemedPackage();
        $this->actingAs($customer)->post("/paket/{$key->id}/sec", ['account_id' => $a->id]);

        $this->actingAs($customer)->get('/kodlarim')->assertOk()->assertSee('Paket')->assertSee('1 / 2 hesap seçildi');
    }
}
