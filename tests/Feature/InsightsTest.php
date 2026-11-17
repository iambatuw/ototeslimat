<?php

namespace Tests\Feature;

use App\Mail\DailySummary;
use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\ProductKey;
use App\Models\Setting;
use App\Models\User;
use App\Support\KeyCode;
use App\Support\StoreInsights;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class InsightsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => User::ROLE_ADMIN])->save();

        return $user;
    }

    private function account(string $title = 'Netflix 1', string $platform = 'netflix'): Account
    {
        return Account::create(['platform' => $platform, 'title' => $title, 'totp_secret' => 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ']);
    }

    private function key(Account $account, array $extra = []): ProductKey
    {
        $body = KeyCode::generate();

        return ProductKey::create(['account_id' => $account->id, 'code' => KeyCode::format($body), 'code_hash' => KeyCode::hash($body)] + $extra);
    }

    private function log(string $event, array $attrs = []): ActivityLog
    {
        return ActivityLog::create(['event' => $event] + $attrs);
    }

    public function test_troubled_accounts_lists_failures_with_the_last_reason(): void
    {
        $bad = $this->account('Bozuk');
        $good = $this->account('Sağlam');

        $this->log('code.failed', ['account_id' => $bad->id, 'meta' => ['reason' => 'no_mail']]);
        $this->log('code.failed', ['account_id' => $bad->id, 'meta' => ['reason' => 'imap_connect']]);
        $this->log('code.issued', ['account_id' => $good->id]);

        $rows = StoreInsights::troubledAccounts();

        $this->assertCount(1, $rows);
        $this->assertSame('Bozuk', $rows[0]->account->title);
        $this->assertSame(2, $rows[0]->failed);
        $this->assertSame('posta kutusuna bağlanılamadı', $rows[0]->reason);
    }

    public function test_old_failures_do_not_count(): void
    {
        $account = $this->account();
        $this->log('code.failed', ['account_id' => $account->id])->forceFill(['created_at' => now()->subDays(2)])->save();

        $this->assertCount(0, StoreInsights::troubledAccounts());
    }

    public function test_low_stock_respects_threshold_and_skips_accounts_without_keys(): void
    {
        $low = $this->account('Az kalan');
        $this->key($low);
        $this->key($low);

        $plenty = $this->account('Bol');
        foreach (range(1, 5) as $i) {
            $this->key($plenty);
        }

        $this->account('Hiç anahtarı yok');

        $items = StoreInsights::lowStock();
        $this->assertSame(['Az kalan'], $items->pluck('title')->all());
        $this->assertSame(2, $items[0]->left);

        Setting::put(['low_stock_threshold' => '10']);
        $this->assertEqualsCanonicalizing(['Az kalan', 'Bol'], StoreInsights::lowStock()->pluck('title')->all());
    }

    public function test_platform_breakdown_and_top_customers(): void
    {
        $netflix = $this->account('N', 'netflix');
        $chatgpt = $this->account('C', 'chatgpt');
        $heavy = User::factory()->create(['name' => 'Çok Alan']);
        $light = User::factory()->create();

        foreach (range(1, 3) as $i) {
            $this->log('code.issued', ['account_id' => $netflix->id, 'user_id' => $heavy->id]);
        }
        $this->log('code.issued', ['account_id' => $chatgpt->id, 'user_id' => $light->id]);
        $this->log('code.failed', ['account_id' => $chatgpt->id, 'user_id' => $light->id]);

        $platforms = StoreInsights::platforms()->keyBy('key');
        $this->assertSame(3, $platforms['netflix']->issued);
        $this->assertSame(1, $platforms['chatgpt']->issued);
        $this->assertSame(1, $platforms['chatgpt']->failed);

        $top = StoreInsights::topCustomers();
        $this->assertSame('Çok Alan', $top[0]->user->name);
        $this->assertSame(3, $top[0]->codes);
    }

    public function test_dashboard_shows_the_new_panels(): void
    {
        $account = $this->account('Bozuk hesap');
        $this->log('code.failed', ['account_id' => $account->id, 'meta' => ['reason' => 'no_mail']]);
        $this->key($account);

        $this->actingAs($this->admin())->get('/yonetim')
            ->assertOk()
            ->assertSee('Hesap sağlığı')
            ->assertSee('Bozuk hesap')
            ->assertSee('son mailde kod yok')
            ->assertSee('1 anahtar kaldı')
            ->assertSee('Platformlar')
            ->assertSee('En çok kod alanlar');
    }

    public function test_search_finds_keys_customers_and_accounts(): void
    {
        $admin = $this->admin();
        $account = $this->account('Disney Aile');
        $other = $this->account('Disney Tekli');
        $key = $this->key($account, ['label' => 'siparis-4821']);
        $customer = User::factory()->create(['name' => 'Ayşe Yılmaz', 'email' => 'ayse@example.test']);

        $this->actingAs($admin)->get('/yonetim/ara?q=Disney')->assertOk()->assertSee('Disney Aile')->assertSee('Disney Tekli');
        $this->actingAs($admin)->get('/yonetim/ara?q='.urlencode($key->code))->assertOk()->assertSee($key->code);
        $this->actingAs($admin)->get('/yonetim/ara?q=siparis-4821')->assertOk()->assertSee($key->code);
        $this->actingAs($admin)->get('/yonetim/ara?q=ayse')->assertOk()->assertSee('Ayşe Yılmaz');
        $this->actingAs($admin)->get('/yonetim/ara?q=Tekli')->assertRedirect(route('admin.accounts.edit', $other));
        $this->actingAs($admin)->get('/yonetim/ara?q=yokboylebirsey')->assertOk()->assertSee('sonuç yok');
        $this->actingAs($admin)->get('/yonetim/ara?q=a')->assertOk()->assertSee('en az iki karakter');
    }

    public function test_search_is_admin_only(): void
    {
        $this->actingAs(User::factory()->create())->get('/yonetim/ara?q=test')->assertForbidden();
    }

    public function test_daily_summary_is_sent_only_when_enabled(): void
    {
        Mail::fake();
        $admin = $this->admin();
        $account = $this->account('Bozuk');
        $this->log('code.failed', ['account_id' => $account->id, 'meta' => ['reason' => 'no_mail']]);

        $this->artisan('guard:daily-summary')->assertSuccessful();
        Mail::assertNothingSent();

        Setting::put(['daily_summary' => '1']);
        $this->artisan('guard:daily-summary')->assertSuccessful();

        Mail::assertSent(DailySummary::class, fn (DailySummary $mail) => $mail->hasTo($admin->email) && $mail->troubled->count() === 1);
    }

    public function test_daily_summary_renders(): void
    {
        $account = $this->account('Bozuk');
        $this->key($account);
        $this->log('code.failed', ['account_id' => $account->id, 'meta' => ['reason' => 'imap_connect']]);
        $this->log('code.issued', ['account_id' => $account->id])->forceFill(['created_at' => now()->subDay()])->save();

        $html = (new DailySummary(StoreInsights::day(now()->subDay()), StoreInsights::troubledAccounts(), StoreInsights::lowStock()))->render();

        $this->assertStringContainsString('Verilen kod', $html);
        $this->assertStringContainsString('Bozuk: 1 hata, posta kutusuna bağlanılamadı', $html);
        $this->assertStringContainsString('1 anahtar kaldı', $html);
    }

    public function test_feature_settings_are_saved_and_validated(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/yonetim/ayarlar/ozellikler', ['registration_open' => '1', 'daily_summary' => '1', 'low_stock_threshold' => '7'])
            ->assertRedirect();
        $this->assertSame('7', Setting::get('low_stock_threshold'));
        $this->assertTrue(Setting::flag('daily_summary'));

        $this->actingAs($admin)->post('/yonetim/ayarlar/ozellikler', ['low_stock_threshold' => '-1'])
            ->assertSessionHasErrors('low_stock_threshold');
    }
}
