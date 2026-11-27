<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\ProductKey;
use App\Models\User;
use App\Support\KeyCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Dönemlik hak ("24 saatte 1 kod"), satıcının verdiği ek hak, giriş sonrası yönlendirme. */
class KeyRulesTest extends TestCase
{
    use RefreshDatabase;

    private function redeemed(array $rules): array
    {
        $account = Account::create(['platform' => 'steam', 'title' => 'Steam', 'totp_secret' => 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ']);
        $body = KeyCode::generate();
        $key = ProductKey::create(['account_id' => $account->id, 'code' => KeyCode::format($body), 'code_hash' => KeyCode::hash($body)] + $rules);
        $customer = User::factory()->create();
        $key->forceFill(['redeemed_by' => $customer->id, 'redeemed_at' => now()])->save();

        return [$key->fresh(), $customer];
    }

    private function newWindow(ProductKey $key): void
    {
        // Testte TOTP aynı 30 saniyede aynı kodu verir; yeni kod istenmiş gibi davran.
        ProductKey::whereKey($key->id)->update(['last_code_hash' => 'eski']);
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => User::ROLE_ADMIN])->save();

        return $admin;
    }

    public function test_one_code_per_24_hours(): void
    {
        [$key, $customer] = $this->redeemed(['period_limit' => 1, 'period_hours' => 24]);
        $url = "/kod/{$key->id}/{$key->account_id}/2fa";

        $this->actingAs($customer)->postJson($url)->assertOk();
        $this->assertSame(1, $key->fresh()->period_count);

        $this->newWindow($key);
        $this->actingAs($customer)->postJson($url)->assertStatus(422)->assertJsonPath('reason', 'period');
        $this->assertSame(0, $key->fresh()->periodLeft());

        // 24 saat sonra hak kendiliğinden yenilenir.
        $this->travel(25)->hours();
        $this->newWindow($key);
        $this->actingAs($customer)->postJson($url)->assertOk();
        $this->assertSame(1, $key->fresh()->period_count);
        $this->assertSame(2, $key->fresh()->uses_count);
    }

    public function test_seller_bonus_lets_customer_get_code_before_period_ends(): void
    {
        [$key, $customer] = $this->redeemed(['period_limit' => 1, 'period_hours' => 24]);
        $url = "/kod/{$key->id}/{$key->account_id}/2fa";
        $this->actingAs($customer)->postJson($url)->assertOk();
        $this->newWindow($key);
        $this->actingAs($customer)->postJson($url)->assertStatus(422);

        $this->actingAs($this->admin())->patch(route('admin.keys.bonus', $key), ['amount' => 1])->assertRedirect();
        $this->assertSame(1, $key->fresh()->bonus_uses);
        $this->assertTrue(ActivityLog::where('event', 'key.bonus')->where('user_id', $customer->id)->exists());

        $this->actingAs($customer)->postJson($url)->assertOk();
        $this->assertSame(0, $key->fresh()->bonus_uses);

        $this->newWindow($key);
        $this->actingAs($customer)->postJson($url)->assertStatus(422)->assertJsonPath('reason', 'period');
    }

    public function test_bonus_reopens_a_used_up_key(): void
    {
        [$key, $customer] = $this->redeemed(['max_uses' => 1]);
        $key->forceFill(['uses_count' => 1, 'last_code_hash' => 'eski'])->save();
        $this->assertSame('used_up', $key->fresh()->status());

        $this->actingAs($this->admin())->patch(route('admin.keys.bonus', $key), ['amount' => 2]);
        $this->assertSame('active', $key->fresh()->status());
        $this->assertSame(2, $key->fresh()->usesLeft());

        $this->actingAs($customer)->postJson("/kod/{$key->id}/{$key->account_id}/2fa")->assertOk()->assertJsonPath('uses_left', 1);
    }

    public function test_customer_cannot_grant_bonus(): void
    {
        [$key, $customer] = $this->redeemed(['max_uses' => 1]);
        $this->actingAs($customer)->patch(route('admin.keys.bonus', $key), ['amount' => 5])->assertForbidden();
        $this->assertSame(0, $key->fresh()->bonus_uses);
    }

    public function test_admin_generates_daily_keys(): void
    {
        $account = Account::create(['platform' => 'steam', 'title' => 'Steam', 'totp_secret' => 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ']);
        $this->actingAs($this->admin())->post('/yonetim/anahtarlar', ['account_id' => $account->id, 'count' => 2, 'period_limit' => 1, 'period_hours' => 24])->assertRedirect();
        $this->assertSame(2, ProductKey::where(['period_limit' => 1, 'period_hours' => 24])->count());
        $this->assertSame('24 saatte 1 kod', ProductKey::first()->periodLabel());
    }

    public function test_login_goes_to_own_page_not_landing(): void
    {
        $customer = User::factory()->create(['password' => 'password']);
        $this->post('/login', ['email' => $customer->email, 'password' => 'password'])->assertRedirect('/kodlarim');

        auth()->logout();
        $admin = $this->admin();
        $admin->forceFill(['password' => bcrypt('password')])->save();
        $this->post('/login', ['email' => $admin->email, 'password' => 'password'])->assertRedirect('/kodlarim');
        $this->get('/kodlarim')->assertRedirect(route('admin.dashboard'));
    }

    public function test_dashboard_counts_people(): void
    {
        [$key, $customer] = $this->redeemed([]);
        $this->actingAs($customer)->postJson("/kod/{$key->id}/{$key->account_id}/2fa")->assertOk();
        $this->actingAs($this->admin())->get('/yonetim')->assertOk()->assertSee('1 kişi');
    }
}
