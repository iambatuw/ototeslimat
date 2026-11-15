<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\ProductKey;
use App\Models\Setting;
use App\Models\User;
use App\Services\Guard\Totp;
use App\Support\KeyCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GuardFlowTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => User::ROLE_ADMIN])->save();

        return $user;
    }

    private function totpAccount(): Account
    {
        return Account::create(['platform' => 'chatgpt', 'title' => 'ChatGPT', 'totp_secret' => 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ']);
    }

    private function key(Account $account, array $rules = []): array
    {
        $body = KeyCode::generate();
        $key = ProductKey::create(['account_id' => $account->id, 'code' => KeyCode::format($body), 'code_hash' => KeyCode::hash($body)] + $rules);

        return [$key, KeyCode::format($body)];
    }

    private function redeemed(array $rules = []): array
    {
        [$key, $code] = $this->key($this->totpAccount(), $rules);
        $customer = User::factory()->create();
        $this->actingAs($customer)->post('/anahtar', ['key' => $code])->assertRedirect('/');

        return [$key->fresh(), $customer];
    }

    public function test_totp_matches_rfc_6238_vectors(): void
    {
        $totp = new Totp;
        $this->assertSame('287082', $totp->code('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', 59));
        $this->assertSame('081804', $totp->code('otpauth://totp/x?secret=GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', 1111111109));
    }

    public function test_key_normalization_accepts_messy_input(): void
    {
        $body = KeyCode::generate();
        $this->assertSame($body, KeyCode::normalize(strtolower(' gk '.substr($body, 0, 8).' '.substr($body, 8))));
        $this->assertNull(KeyCode::normalize('GK-1234'));
    }

    public function test_customer_redeems_key_and_gets_2fa_code(): void
    {
        [$key, $customer] = $this->redeemed();
        $this->assertSame($customer->id, $key->redeemed_by);

        $response = $this->actingAs($customer)->postJson("/kod/{$key->id}/{$key->account_id}/2fa")->assertOk();
        $this->assertMatchesRegularExpression('/^\d{6}$/', $response->json('code'));
        $this->assertSame(1, $key->fresh()->uses_count);
        $this->assertTrue(ActivityLog::where('event', 'code.issued')->exists());
    }

    public function test_same_code_does_not_use_a_second_right(): void
    {
        [$key, $customer] = $this->redeemed(['max_uses' => 1]);

        $this->actingAs($customer)->postJson("/kod/{$key->id}/{$key->account_id}/2fa")->assertOk();
        $this->actingAs($customer)->postJson("/kod/{$key->id}/{$key->account_id}/2fa")->assertOk();
        $this->assertSame(1, $key->fresh()->uses_count);

        // Yeni bir kod penceresinde hak bitti
        $key->forceFill(['last_code_hash' => 'eski'])->save();
        $this->actingAs($customer)->postJson("/kod/{$key->id}/{$key->account_id}/2fa")->assertStatus(422)->assertJsonFragment(['message' => 'Bu anahtarın kullanım hakkı doldu (1 kod).']);
    }

    public function test_cooldown_blocks_new_code(): void
    {
        [$key, $customer] = $this->redeemed(['cooldown_minutes' => 30]);
        $key->forceFill(['last_used_at' => now()->subMinutes(5), 'last_code_hash' => 'eski', 'uses_count' => 1])->save();

        $this->actingAs($customer)->postJson("/kod/{$key->id}/{$key->account_id}/2fa")->assertStatus(422);
    }

    public function test_validity_starts_at_redeem_and_expires(): void
    {
        [$key, $customer] = $this->redeemed(['valid_days' => 30]);
        $this->assertTrue($key->expires_at->between(now()->addDays(29), now()->addDays(31)));

        $key->forceFill(['expires_at' => now()->subMinute()])->save();
        $this->actingAs($customer)->postJson("/kod/{$key->id}/{$key->account_id}/2fa")->assertStatus(422);
    }

    public function test_mail_code_requires_mailbox(): void
    {
        [$key, $customer] = $this->redeemed();
        $this->actingAs($customer)->postJson("/kod/{$key->id}/{$key->account_id}/mail")->assertStatus(422);
        $this->actingAs($customer)->postJson("/kod/{$key->id}/{$key->account_id}/baska")->assertNotFound();
    }

    public function test_key_cannot_be_redeemed_by_second_customer(): void
    {
        [, $code] = $this->key($this->totpAccount());

        $this->actingAs(User::factory()->create())->post('/anahtar', ['key' => $code]);
        $this->actingAs(User::factory()->create())->post('/anahtar', ['key' => $code])->assertSessionHasErrors('key');
    }

    public function test_invalid_key_is_logged_and_rate_limited(): void
    {
        $customer = User::factory()->create();

        for ($i = 0; $i < 8; $i++) {
            $this->actingAs($customer)->post('/anahtar', ['key' => 'GK-AAAAAAAA-BBBBBBBB'])->assertSessionHasErrors('key');
        }

        $this->actingAs($customer)->post('/anahtar', ['key' => 'GK-AAAAAAAA-BBBBBBBB'])->assertStatus(429);
        $this->assertSame(8, ActivityLog::where('event', 'key.invalid')->count());
    }

    public function test_customer_cannot_use_someone_elses_key(): void
    {
        [$key] = $this->redeemed();
        $this->actingAs(User::factory()->create())->postJson("/kod/{$key->id}/{$key->account_id}/2fa")->assertNotFound();
    }

    public function test_revoked_key_stops_giving_codes(): void
    {
        [$key, $customer] = $this->redeemed();
        $key->forceFill(['revoked_at' => now()])->save();

        $this->actingAs($customer)->postJson("/kod/{$key->id}/{$key->account_id}/2fa")->assertStatus(422);
    }

    public function test_roles_are_separated(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)->get('/yonetim')->assertForbidden();
        $this->actingAs($customer)->get('/yonetim/ayarlar')->assertForbidden();
        $this->actingAs($this->admin())->post('/anahtar', ['key' => 'x'])->assertForbidden();
    }

    public function test_admin_pages_render(): void
    {
        $admin = $this->admin();
        $account = $this->totpAccount();
        $this->key($account, ['max_uses' => 3]);

        foreach (['/yonetim', '/yonetim/hesaplar', "/yonetim/hesaplar?hesap={$account->id}", '/yonetim/hesaplar/yeni', "/yonetim/hesaplar/{$account->id}/duzenle",
            '/yonetim/anahtarlar', '/yonetim/musteriler', '/yonetim/kayitlar', '/yonetim/ayarlar', '/yonetim/ayarlar/gorunum', '/yonetim/ayarlar/ozellikler'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_admin_generates_keys_with_rules(): void
    {
        $account = $this->totpAccount();

        $this->actingAs($this->admin())->post('/yonetim/anahtarlar', [
            'account_id' => $account->id, 'count' => 3, 'max_uses' => 2, 'valid_days' => 7, 'cooldown_minutes' => 10,
        ])->assertRedirect();

        $this->assertSame(3, ProductKey::where(['max_uses' => 2, 'valid_days' => 7, 'cooldown_minutes' => 10])->count());
    }

    public function test_secrets_are_encrypted_at_rest(): void
    {
        $account = $this->totpAccount();
        [, $code] = $this->key($account);

        $this->assertStringNotContainsString('GEZDGNBV', DB::table('accounts')->value('totp_secret'));
        $this->assertStringNotContainsString(substr($code, 3, 8), DB::table('product_keys')->value('code'));
    }

    public function test_registration_can_be_closed(): void
    {
        $this->get('/register')->assertOk();
        Setting::put(['registration_open' => '0']);
        $this->get('/register')->assertNotFound();
    }

    public function test_theme_color_rejects_invalid_value(): void
    {
        $this->actingAs($this->admin())->post('/yonetim/ayarlar/gorunum', ['theme_color' => 'red;}body{display:none'])
            ->assertSessionHasErrors('theme_color');
    }
}
