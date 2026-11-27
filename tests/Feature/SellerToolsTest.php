<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\ProductKey;
use App\Models\RightRequest;
use App\Models\Setting;
use App\Models\User;
use App\Support\Alerts;
use App\Support\KeyCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Müşteri ayrıntısı, süre uzatma, telafi, anahtar taşıma, ek hak isteği,
 * müşteriye not, kod geçmişi, paylaşım uyarısı ve bildirimler.
 */
class SellerToolsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => User::ROLE_ADMIN])->save();

        return $admin;
    }

    private function account(string $title = 'Steam A', array $extra = []): Account
    {
        return Account::create(['platform' => 'steam', 'title' => $title, 'totp_secret' => 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ'] + $extra);
    }

    private function sold(Account $account, array $rules = [], ?User $customer = null): array
    {
        $body = KeyCode::generate();
        $key = ProductKey::create(['account_id' => $account->id, 'code' => KeyCode::format($body), 'code_hash' => KeyCode::hash($body)] + $rules);
        $customer ??= User::factory()->create();
        $key->forceFill([
            'redeemed_by' => $customer->id,
            'redeemed_at' => now(),
            'expires_at' => isset($rules['valid_days']) ? now()->addDays($rules['valid_days']) : null,
        ])->save();

        return [$key->fresh(), $customer];
    }

    public function test_customer_detail_page_shows_keys_and_requests(): void
    {
        [$key, $customer] = $this->sold($this->account(), ['max_uses' => 1]);
        RightRequest::create(['product_key_id' => $key->id, 'user_id' => $customer->id, 'account_id' => $key->account_id, 'reason' => 'used_up']);

        $this->actingAs($this->admin())->get(route('admin.customers.show', $customer))
            ->assertOk()->assertSee($customer->email)->assertSee('Steam A')->assertSee('Ek hak istekleri')->assertSee('Toplam hakkı bitti');
        $this->actingAs($customer)->get(route('admin.customers.show', $customer))->assertForbidden();
    }

    public function test_extend_moves_expiry_forward_or_from_today(): void
    {
        [$key] = $this->sold($this->account(), ['valid_days' => 30]);
        $end = $key->expires_at;
        $admin = $this->admin();

        $this->actingAs($admin)->patch(route('admin.keys.extend', $key), ['days' => 30]);
        $this->assertTrue($key->fresh()->expires_at->equalTo($end->copy()->addDays(30)));

        // Süresi bitmişse bugünden başlar.
        $key->forceFill(['expires_at' => now()->subDays(5)])->save();
        $this->actingAs($admin)->patch(route('admin.keys.extend', $key), ['days' => 7]);
        $this->assertTrue($key->fresh()->expires_at->between(now()->addDays(6), now()->addDays(8)));
        $this->assertSame('active', $key->fresh()->status());
    }

    public function test_compensation_reaches_every_customer_of_the_account(): void
    {
        $account = $this->account();
        [$a] = $this->sold($account, ['valid_days' => 10, 'max_uses' => 1]);
        [$b] = $this->sold($account);
        [$other] = $this->sold($this->account('Steam B'));

        $this->actingAs($this->admin())->post(route('admin.accounts.compensate', $account), ['rights' => 2, 'days' => 3])->assertRedirect();

        $this->assertSame(2, $a->fresh()->bonus_uses);
        $this->assertTrue($a->fresh()->expires_at->greaterThan($a->expires_at->copy()->addDays(2)));
        $this->assertSame(2, $b->fresh()->bonus_uses);
        $this->assertNull($b->fresh()->expires_at); // süresiz kalır
        $this->assertSame(0, $other->fresh()->bonus_uses);
    }

    public function test_keys_move_to_new_account_with_labels(): void
    {
        $old = $this->account('Eski');
        $new = $this->account('Yeni');
        [$key, $customer] = $this->sold($old);
        DB::table('account_labels')->insert(['user_id' => $customer->id, 'account_id' => $old->id, 'label' => 'Ana hesap', 'created_at' => now(), 'updated_at' => now()]);

        $this->actingAs($this->admin())->post(route('admin.accounts.move', $old), ['target_id' => $new->id])->assertRedirect(route('admin.accounts.edit', $new));

        $this->assertSame($new->id, $key->fresh()->account_id);
        $this->assertSame('Ana hesap', DB::table('account_labels')->where(['user_id' => $customer->id, 'account_id' => $new->id])->value('label'));
        $this->actingAs($customer)->postJson("/kod/{$key->id}/{$new->id}/2fa")->assertOk();
    }

    public function test_move_rejects_same_account(): void
    {
        $account = $this->account();
        $this->actingAs($this->admin())->post(route('admin.accounts.move', $account), ['target_id' => $account->id])->assertSessionHasErrors('target_id');
    }

    public function test_customer_requests_right_and_admin_approves(): void
    {
        [$key, $customer] = $this->sold($this->account(), ['max_uses' => 1]);
        $key->forceFill(['uses_count' => 1, 'last_code_hash' => 'eski'])->save();
        $url = "/kod/{$key->id}/{$key->account_id}/2fa";

        $response = $this->actingAs($customer)->postJson($url)->assertStatus(422)->assertJsonPath('reason', 'used_up');
        $askUrl = $response->json('request_url');
        $this->assertNotNull($askUrl);

        $this->actingAs($customer)->postJson($askUrl)->assertOk();
        $this->actingAs($customer)->postJson($askUrl)->assertOk(); // ikinci istek yeni kayıt açmaz
        $this->assertSame(1, RightRequest::where('status', 'pending')->count());
        $this->actingAs($customer)->postJson($url)->assertStatus(422)->assertJsonPath('request_pending', true);

        $ask = RightRequest::first();
        $this->actingAs($this->admin())->patch(route('admin.requests.handle', $ask), ['decision' => 'approve'])->assertRedirect();
        $this->assertSame('approved', $ask->fresh()->status);
        $this->assertSame(1, $key->fresh()->bonus_uses);

        $this->actingAs($customer)->postJson($url)->assertOk();
    }

    public function test_requests_can_be_turned_off_and_are_private(): void
    {
        [$key, $customer] = $this->sold($this->account(), ['max_uses' => 1]);
        $stranger = User::factory()->create();
        $askUrl = route('rights.request', [$key, $key->account_id]);

        $this->actingAs($stranger)->postJson($askUrl)->assertNotFound();

        Setting::put(['right_requests' => '0']);
        $key->forceFill(['uses_count' => 1])->save();
        $this->actingAs($customer)->postJson("/kod/{$key->id}/{$key->account_id}/2fa")->assertStatus(422)->assertJsonPath('request_url', null);
        $this->actingAs($customer)->postJson($askUrl)->assertNotFound();
    }

    public function test_customer_note_and_code_history_show_on_card(): void
    {
        [$key, $customer] = $this->sold($this->account('Steam A', ['customer_note' => 'Profil seçerken 2. profili kullan.']));
        $this->actingAs($customer)->postJson("/kod/{$key->id}/{$key->account_id}/2fa")->assertOk();

        $this->actingAs($customer)->get('/kodlarim')->assertOk()
            ->assertSee('Profil seçerken 2. profili kullan.')
            ->assertSee('Son kodların (1)');
    }

    public function test_shared_key_is_flagged_after_many_ips(): void
    {
        [$key, $customer] = $this->sold($this->account());

        foreach (['10.0.0.1', '10.0.0.2', '10.0.0.3'] as $ip) {
            ActivityLog::create(['event' => 'code.issued', 'user_id' => $customer->id, 'product_key_id' => $key->id, 'account_id' => $key->account_id, 'ip' => $ip]);
        }

        Alerts::codeIssued($key);

        $this->assertTrue(ActivityLog::where('event', 'key.shared')->where('product_key_id', $key->id)->exists());
        $this->actingAs($this->admin())->get('/yonetim')->assertOk()->assertSee('Paylaşılıyor olabilir')->assertSee('3 farklı IP');
    }

    public function test_telegram_notification_settings_and_test_message(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.settings.update', 'bildirimler'), [
            'notify_telegram_token' => '123456:ABC-def_ghi',
            'notify_telegram_chat' => '987654',
            'notify_broken' => '1', 'notify_stock' => '1', 'notify_shared' => '1', 'notify_request' => '1',
        ])->assertRedirect();

        $stored = Setting::get('notify_telegram_token');
        $this->assertNotSame('123456:ABC-def_ghi', $stored);
        $this->assertSame('123456:ABC-def_ghi', Crypt::decryptString($stored));

        $this->actingAs($admin)->post(route('admin.settings.notify-test'))->assertSessionHas('status');
        Http::assertSent(fn ($request) => str_contains($request->url(), 'bot123456:ABC-def_ghi/sendMessage') && $request['chat_id'] === '987654');

        // Boş bırakılan anahtar eskisini silmez.
        $this->actingAs($admin)->post(route('admin.settings.update', 'bildirimler'), ['notify_telegram_chat' => '987654']);
        $this->assertSame($stored, Setting::get('notify_telegram_token'));

        $this->actingAs($admin)->post(route('admin.settings.update', 'bildirimler'), ['notify_telegram_token' => 'yanlis'])->assertSessionHasErrors('notify_telegram_token');
    }

    public function test_rule_limits_are_not_counted_as_account_trouble(): void
    {
        $account = $this->account();
        ActivityLog::create(['event' => 'code.failed', 'account_id' => $account->id, 'meta' => ['type' => '2fa', 'reason' => 'period']]);
        ActivityLog::create(['event' => 'code.failed', 'account_id' => $account->id, 'meta' => ['type' => '2fa', 'reason' => 'used_up']]);

        $this->assertCount(0, \App\Support\StoreInsights::troubledAccounts());
    }

    public function test_broken_account_notifies_after_three_failures(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        Setting::put(['notify_telegram_token' => Crypt::encryptString('1:a'), 'notify_telegram_chat' => '5']);
        $account = $this->account();

        foreach (range(1, 3) as $i) {
            ActivityLog::create(['event' => 'code.failed', 'account_id' => $account->id, 'meta' => ['reason' => 'imap_connect']]);
        }

        Alerts::codeFailed($account, 'imap_connect');
        Alerts::codeFailed($account, 'imap_connect'); // aynı hesap için tekrar mesaj yok
        Alerts::codeFailed($account, 'no_mail');      // müşteri kaynaklı neden bildirim değil
        $this->app->terminate();

        Http::assertSentCount(1);
    }
}
