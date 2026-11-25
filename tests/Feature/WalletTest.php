<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\ProductKey;
use App\Models\User;
use App\Support\KeyCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Kodlarım: aynı platformdan birkaç hesap, müşterinin verdiği ad, son kullanılan önde. */
class WalletTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->customer = User::factory()->create();
    }

    private function give(string $platform, string $title): Account
    {
        $account = Account::create(['platform' => $platform, 'title' => $title, 'totp_secret' => 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ']);
        $body = KeyCode::generate();
        ProductKey::create(['account_id' => $account->id, 'code' => KeyCode::format($body), 'code_hash' => KeyCode::hash($body)])
            ->forceFill(['redeemed_by' => $this->customer->id, 'redeemed_at' => now()])->save();

        return $account;
    }

    public function test_same_platform_accounts_get_their_own_shelf_and_last_used_leads(): void
    {
        $first = $this->give('steam', 'CS2 Prime 1');
        $second = $this->give('steam', 'CS2 Prime 2');
        $this->give('steam', 'GTA V');
        $this->give('netflix', 'Netflix 4K');

        ActivityLog::create(['event' => 'code.issued', 'user_id' => $this->customer->id, 'account_id' => $first->id, 'created_at' => now()->subDays(3)]);
        ActivityLog::create(['event' => 'code.issued', 'user_id' => $this->customer->id, 'account_id' => $second->id, 'created_at' => now()->subHour()]);

        $response = $this->actingAs($this->customer)->get('/kodlarim')->assertOk();
        $response->assertSee('Steam')->assertSee('· 3 hesap', false)->assertSee('Diğer hesapların');

        $html = $response->getContent();
        // Üstte açık olan kart en son kod alınan hesap (CS2 Prime 2); diğerleri gizli.
        $article = fn (Account $a) => preg_match('/<article[^>]*data-hero="k\d+a'.$a->id.'"[^>]*>/s', $html, $m) ? $m[0] : '';
        $this->assertStringNotContainsString('hidden', $article($second));
        $this->assertStringContainsString('hidden', $article($first));
        $this->assertStringContainsString('Son kullandığın', $html);
    }

    public function test_customer_names_an_account_and_sees_it(): void
    {
        $account = $this->give('steam', 'CS2 Prime 1');

        $this->actingAs($this->customer)->postJson("/kart/{$account->id}/ad", ['label' => '  Ana   hesap '])->assertOk()->assertJson(['label' => 'Ana hesap']);
        $this->actingAs($this->customer)->get('/kodlarim')->assertSee('Ana hesap');

        $this->actingAs($this->customer)->postJson("/kart/{$account->id}/ad", ['label' => ''])->assertOk();
        $this->assertDatabaseMissing('account_labels', ['account_id' => $account->id]);
    }

    public function test_customer_cannot_name_someone_elses_account(): void
    {
        $other = Account::create(['platform' => 'steam', 'title' => 'Başkasının', 'totp_secret' => 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ']);

        $this->actingAs($this->customer)->postJson("/kart/{$other->id}/ad", ['label' => 'Benim'])->assertNotFound();
        $this->actingAs($this->customer)->postJson("/kart/{$this->give('steam', 'X')->id}/ad", ['label' => str_repeat('a', 41)])->assertStatus(422);
    }

    public function test_selected_account_opens_without_javascript(): void
    {
        $this->give('steam', 'CS2 Prime 1');
        $target = $this->give('netflix', 'Netflix 4K');
        $id = 'k'.ProductKey::where('account_id', $target->id)->value('id').'a'.$target->id;

        $html = $this->actingAs($this->customer)->get('/kodlarim?hesap='.$id)->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/data-hero="'.$id.'"\s+data-p="netflix"\s+data-game[^>]*aria-labelledby/', $html);
        $this->assertDoesNotMatchRegularExpression('/data-hero="'.$id.'"[^>]*hidden/', $html);
    }
}
