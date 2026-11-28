<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\ProductKey;
use App\Models\Setting;
use App\Models\User;
use App\Support\KeyCode;
use App\Support\License;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/** Aylık lisans: imza, alan adı, süre, ek süre, kilit ve yenileme. */
class LicenseTest extends TestCase
{
    use RefreshDatabase;

    private string $secret;

    protected function setUp(): void
    {
        parent::setUp();
        $pair = sodium_crypto_sign_keypair();
        $this->secret = base64_encode(sodium_crypto_sign_secretkey($pair));
        config([
            'guard.license.enforce' => true,
            'guard.license.public_key' => base64_encode(sodium_crypto_sign_publickey($pair)),
        ]);
    }

    private function token(Carbon $until, string $domain = 'localhost'): string
    {
        return License::issue($domain, $until, $this->secret, 'test');
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => User::ROLE_ADMIN])->save();

        return $admin;
    }

    public function test_token_states(): void
    {
        $this->assertSame('missing', License::verify(null, 'localhost')['state']);
        $this->assertSame('active', License::verify($this->token(now()->addDays(30)), 'localhost')['state']);
        $this->assertSame('active', License::verify($this->token(now()->addDays(30), 'https://www.LocalHost/'), 'localhost')['state']);
        $this->assertSame('warning', License::verify($this->token(now()->addDays(3)), 'localhost')['state']);
        $this->assertSame('grace', License::verify($this->token(now()->subDay()), 'localhost')['state']);
        $this->assertSame('expired', License::verify($this->token(now()->subDays(License::GRACE_DAYS + 1)), 'localhost')['state']);
        $this->assertSame('domain', License::verify($this->token(now()->addDays(30), 'baska.com'), 'localhost')['state']);

        // Başka bir özel anahtarla üretilen ya da değiştirilmiş anahtar geçmez.
        $other = base64_encode(sodium_crypto_sign_secretkey(sodium_crypto_sign_keypair()));
        $this->assertSame('invalid', License::verify(License::issue('localhost', now()->addDays(30), $other), 'localhost')['state']);
        [$payload, $sig] = explode('.', $this->token(now()->addDays(30)));
        $tampered = rtrim(strtr(base64_encode(str_replace('"e":', '"e":9', base64_decode(strtr($payload, '-_', '+/')))), '+/', '-_'), '=');
        $this->assertSame('invalid', License::verify($tampered.'.'.$sig, 'localhost')['state']);
        $this->assertSame('invalid', License::verify('bozuk', 'localhost')['state']);
    }

    public function test_site_is_locked_without_license(): void
    {
        $this->get('/')->assertStatus(503)->assertSee('Hizmet geçici olarak durduruldu');
        $this->get('/login')->assertOk();

        $customer = User::factory()->create();
        $this->actingAs($customer)->get('/kodlarim')->assertStatus(503);

        $account = Account::create(['platform' => 'steam', 'title' => 'Steam A', 'totp_secret' => 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ']);
        $body = KeyCode::generate();
        $key = ProductKey::create(['account_id' => $account->id, 'code' => KeyCode::format($body), 'code_hash' => KeyCode::hash($body)]);
        $key->forceFill(['redeemed_by' => $customer->id, 'redeemed_at' => now()])->save();
        $this->actingAs($customer)->postJson("/kod/{$key->id}/{$account->id}/2fa")->assertStatus(503);

        $admin = $this->admin();
        // Kilitliyken de yönetici gerçekten giriş yapabilir (giriş formunun gönderimi açık).
        auth()->logout();
        $this->post('/login', ['email' => $admin->email, 'password' => 'password'])->assertRedirect();
        $this->assertAuthenticatedAs($admin);
        $this->get('/yonetim')->assertRedirect(route('admin.settings', 'lisans'));
        $this->get(route('admin.settings', 'lisans'))->assertOk()->assertSee('Lisans yok');
    }

    public function test_admin_activates_license_and_site_opens(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.settings.update', 'lisans'), ['license_token' => $this->token(now()->addDays(30), 'baska.com')])
            ->assertSessionHasErrors('license_token');
        $this->actingAs($admin)->post(route('admin.settings.update', 'lisans'), ['license_token' => $this->token(now()->subDays(10))])
            ->assertSessionHasErrors('license_token');
        $this->actingAs($admin)->post(route('admin.settings.update', 'lisans'), ['license_token' => 'bozuk'])
            ->assertSessionHasErrors('license_token');

        $this->actingAs($admin)->post(route('admin.settings.update', 'lisans'), ['license_token' => $this->token(now()->addDays(30))])
            ->assertSessionHasNoErrors()->assertSessionHas('status');

        $this->actingAs($admin)->get('/yonetim')->assertOk()->assertDontSee('license-bar', false);
        $this->get('/')->assertOk();
    }

    public function test_warning_and_grace_keep_site_open_with_banner(): void
    {
        $admin = $this->admin();

        Setting::put(['license_token' => $this->token(now()->addDays(3))]);
        $this->actingAs($admin)->get('/yonetim')->assertOk()->assertSee('bitiyor')->assertSee('Lisansı yenile');

        Setting::put(['license_token' => $this->token(now()->subDay())]);
        $this->get('/')->assertOk();
        $this->actingAs($admin)->get('/yonetim')->assertOk()->assertSee('site kapanacak');

        Setting::put(['license_token' => $this->token(now()->subDays(License::GRACE_DAYS + 1))]);
        $this->actingAs($admin)->get('/yonetim')->assertRedirect(route('admin.settings', 'lisans'));
        auth()->logout();
        $this->get('/')->assertStatus(503);
    }

    public function test_license_is_not_enforced_outside_production(): void
    {
        config(['guard.license.enforce' => false]);
        $this->get('/')->assertOk();
    }
}
