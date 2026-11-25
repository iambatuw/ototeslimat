<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Package;
use App\Models\ProductKey;
use App\Models\Setting;
use App\Models\User;
use App\Support\KeyCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** /onay: hesap açmadan anahtarla kod alma. */
class QuickCodeTest extends TestCase
{
    use RefreshDatabase;

    private function key(array $rules = []): array
    {
        $account = Account::create(['platform' => 'chatgpt', 'title' => 'ChatGPT Plus', 'totp_secret' => 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ']);
        $body = KeyCode::generate();
        $key = ProductKey::create(['account_id' => $account->id, 'code' => KeyCode::format($body), 'code_hash' => KeyCode::hash($body)] + $rules);

        return [$key, KeyCode::format($body)];
    }

    public function test_guest_gets_code_with_key_only(): void
    {
        [$key, $code] = $this->key(['valid_days' => 7]);

        $this->get('/onay')->assertOk()->assertSee('Kodunu al');
        $this->post('/onay', ['key' => strtolower($code)])->assertRedirect('/onay');
        $this->get('/onay')->assertOk()->assertSee('ChatGPT Plus')->assertSee('2FA kodunu al')->assertDontSee($code);

        $this->postJson('/onay/kod/2fa')->assertOk()->assertJsonStructure(['code', 'expires_in']);

        $key->refresh();
        $this->assertNotNull($key->redeemed_at);
        $this->assertNull($key->redeemed_by);
        $this->assertSame('active', $key->status());
        $this->assertTrue($key->expires_at->between(now()->addDays(6), now()->addDays(8)));
    }

    public function test_quick_key_cannot_be_added_to_an_account_and_vice_versa(): void
    {
        [, $code] = $this->key();
        $this->post('/onay', ['key' => $code]);

        $this->actingAs(User::factory()->create())->post('/anahtar', ['key' => $code])->assertSessionHasErrors('key');

        [, $other] = $this->key();
        $this->actingAs(User::factory()->create())->post('/anahtar', ['key' => $other]);
        auth()->logout();
        $this->post('/onay', ['key' => $other])->assertSessionHasErrors('key');
    }

    public function test_code_requires_an_opened_key(): void
    {
        $this->postJson('/onay/kod/2fa')->assertStatus(422);
        $this->postJson('/onay/kod/baska')->assertNotFound();
    }

    public function test_revoked_key_is_refused_and_page_can_be_turned_off(): void
    {
        [$key, $code] = $this->key();
        $this->post('/onay', ['key' => $code]);
        $key->forceFill(['revoked_at' => now()])->save();
        $this->postJson('/onay/kod/2fa')->assertStatus(422);

        Setting::put(['quick_code' => '0']);
        $this->get('/onay')->assertNotFound();
        $this->post('/onay', ['key' => $code])->assertNotFound();
    }

    public function test_package_keys_need_an_account(): void
    {
        $account = Account::create(['platform' => 'steam', 'title' => 'Steam', 'totp_secret' => 'steam:'.base64_encode('12345678901234567890')]);
        $package = Package::create(['name' => 'Oyun paketi', 'pick_count' => 1, 'is_active' => true]);
        $package->accounts()->attach($account);
        $body = KeyCode::generate();
        ProductKey::create(['package_id' => $package->id, 'code' => KeyCode::format($body), 'code_hash' => KeyCode::hash($body)]);

        $this->post('/onay', ['key' => KeyCode::format($body)])->assertSessionHasErrors('key');
    }

    public function test_guests_are_rate_limited_per_ip(): void
    {
        for ($i = 0; $i < 8; $i++) {
            $this->post('/onay', ['key' => 'GK-AAAAAAAA-BBBBBBBB']);
        }

        $this->post('/onay', ['key' => 'GK-AAAAAAAA-BBBBBBBB'])->assertStatus(429);
    }
}
