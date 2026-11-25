<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\ProductKey;
use App\Models\User;
use App\Services\Guard\CodeExtractor;
use App\Services\Guard\EmailCodeFetcher;
use App\Support\KeyCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Steam Guard, oyun platformları ve mail kodu akışı (posta kutusu taklit edilir). */
class GameFlowTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => User::ROLE_ADMIN])->save();

        return $user;
    }

    private function redeem(Account $account): array
    {
        $body = KeyCode::generate();
        $key = ProductKey::create(['account_id' => $account->id, 'code' => KeyCode::format($body), 'code_hash' => KeyCode::hash($body)]);
        $customer = User::factory()->create();
        $this->actingAs($customer)->post('/anahtar', ['key' => KeyCode::format($body)])->assertRedirect('/kodlarim');

        return [$key->fresh(), $customer];
    }

    private function mailAccount(string $platform = 'steam'): Account
    {
        return Account::create([
            'platform' => $platform, 'title' => ucfirst($platform).' 1',
            'mail_host' => 'imap.example.com', 'mail_port' => 993, 'mail_encryption' => 'ssl',
            'mail_username' => 'kutu@example.com', 'mail_password' => 'gizli',
        ]);
    }

    /** Posta kutusunu verilen maillerle taklit eder. */
    private function inbox(array $messages): void
    {
        $this->app->instance(EmailCodeFetcher::class, new class($messages) extends EmailCodeFetcher
        {
            public function __construct(private array $messages)
            {
                parent::__construct(app(CodeExtractor::class));
            }

            public function fetch(Account $account): ?array
            {
                return $this->pick($this->messages, $account);
            }
        });
    }

    public function test_admin_saves_steam_account_from_mafile(): void
    {
        $shared = base64_encode('12345678901234567890');

        $this->actingAs($this->admin())->post('/yonetim/hesaplar', [
            'platform' => 'steam', 'title' => 'CS2 Prime', 'is_active' => 1, 'use_mail' => 0, 'use_totp' => 1,
            'totp_secret' => json_encode(['shared_secret' => $shared, 'account_name' => 'gamer123']),
        ])->assertRedirect();

        $account = Account::firstWhere('title', 'CS2 Prime');
        $this->assertTrue($account->isSteamGuard());
        $this->assertSame(5, $account->codeLength('2fa'));
        $this->assertStringNotContainsString($shared, DB::table('accounts')->value('totp_secret'));
    }

    public function test_customer_gets_five_character_steam_guard_code(): void
    {
        $account = Account::create(['platform' => 'steam', 'title' => 'Steam', 'totp_secret' => 'steam:'.base64_encode('12345678901234567890')]);
        [$key, $customer] = $this->redeem($account);

        $this->actingAs($customer)->get('/kodlarim')->assertOk()->assertSee('Steam Guard kodunu al')->assertSee('data-length="5"', false);

        $response = $this->actingAs($customer)->postJson("/kod/{$key->id}/{$account->id}/2fa")->assertOk();
        $this->assertMatchesRegularExpression('/^[23456789BCDFGHJKMNPQRTVWXY]{5}$/', $response->json('code'));
        $this->assertTrue($response->json('steam'));
    }

    public function test_steam_mail_code_is_delivered(): void
    {
        $account = $this->mailAccount();
        [$key, $customer] = $this->redeem($account);
        $this->inbox([[
            'from' => 'noreply@steampowered.com', 'subject' => 'Your Steam account: Access from new web or mobile device',
            'text' => 'Here is the Steam Guard code you need to login to account gamer123: Login Code F7K2Q Valve Corporation PO Box 1688 Bellevue, WA 98009',
            'received_at' => Carbon::now()->subMinute(), 'folder' => 'INBOX',
        ]]);

        $this->actingAs($customer)->postJson("/kod/{$key->id}/{$account->id}/mail")->assertOk()->assertJsonFragment(['code' => 'F7K2Q']);
    }

    public function test_email_change_code_is_never_shown(): void
    {
        $account = $this->mailAccount('ea');
        [$key, $customer] = $this->redeem($account);
        $this->inbox([[
            'from' => 'ea@e.ea.com', 'subject' => 'Your EA Security Code is: 551204',
            'text' => 'You requested to change your email address. Your EA Security Code: 551204 The EA Team',
            'received_at' => Carbon::now()->subMinute(), 'folder' => 'INBOX',
        ]]);

        $response = $this->actingAs($customer)->postJson("/kod/{$key->id}/{$account->id}/mail")->assertStatus(422);
        $this->assertSame('blocked_purpose', $response->json('reason'));
        $this->assertStringNotContainsString('551204', $response->getContent());
        $this->assertSame(0, $key->fresh()->uses_count);
    }

    public function test_waiting_for_mail_asks_to_retry_and_polls_are_not_logged(): void
    {
        $account = $this->mailAccount('epic');
        [$key, $customer] = $this->redeem($account);
        $this->inbox([]);

        $this->actingAs($customer)->postJson("/kod/{$key->id}/{$account->id}/mail")->assertStatus(422)->assertJson(['retry' => true, 'reason' => 'no_mail']);
        $this->actingAs($customer)->postJson("/kod/{$key->id}/{$account->id}/mail?poll=1")->assertStatus(422);
        $this->actingAs($customer)->postJson("/kod/{$key->id}/{$account->id}/mail?poll=1")->assertStatus(422);

        $this->assertSame(1, ActivityLog::where('event', 'code.failed')->count());
    }

    public function test_account_form_lists_game_platforms(): void
    {
        $this->actingAs($this->admin())->get('/yonetim/hesaplar/yeni')->assertOk()
            ->assertSee('Oyun')->assertSee('Steam')->assertSee('Epic Games')->assertSee('Ubisoft')->assertSee('maFile');
    }
}
