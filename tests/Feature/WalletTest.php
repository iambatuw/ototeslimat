<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\ProductKey;
use App\Models\User;
use App\Support\KeyCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
        $response->assertSee('Steam')->assertSee('Steam <span>3</span>', false)->assertSee('Netflix <span>1</span>', false);

        $html = $response->getContent();
        // Kartlar en son kod alınan hesaptan başlar: CS2 Prime 2 ilk sırada, hiç kullanılmamışlar sonra.
        preg_match_all('/data-hero="k\d+a(\d+)"/', $html, $m);
        $this->assertSame($second->id, (int) $m[1][0]);
        $this->assertSame($first->id, (int) $m[1][1]);
    }

    public function test_card_shows_seller_title_not_customer_label(): void
    {
        $account = $this->give('steam', 'CS2 Prime 1');
        DB::table('account_labels')->insert(['user_id' => $this->customer->id, 'account_id' => $account->id, 'label' => 'Benim adım', 'created_at' => now(), 'updated_at' => now()]);

        $this->actingAs($this->customer)->get('/kodlarim')->assertSee('CS2 Prime 1')->assertDontSee('Benim adım')->assertDontSee('Bu hesap için kendi adın');
        $this->actingAs($this->customer)->postJson("/kart/{$account->id}/ad", ['label' => 'X'])->assertNotFound();
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
