<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Game;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/** Oyun kataloğu: varsayılan kapalı, ayardan açılır; yönetimde ekle/liste/gizle; kartta kapak. */
class GameCatalogTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => User::ROLE_ADMIN])->save();

        return $admin;
    }

    public function test_catalog_is_on_by_default_and_can_be_turned_off(): void
    {
        $this->assertTrue(Setting::flag('games_catalog'));
        Game::create(['name' => 'Elden Ring', 'platform' => 'steam', 'featured' => true, 'note' => 'Deluxe sürüm']);
        Game::create(['name' => 'Gizli Oyun', 'platform' => 'epic', 'is_visible' => false]);

        $this->get('/oyunlar')->assertOk()->assertSee('Elden Ring')->assertSee('Öne çıkan')->assertSee('Deluxe sürüm')->assertDontSee('Gizli Oyun');
        // Ana sayfada yalnızca üst çubukta bağlantı; oyunlar ayrı sayfada.
        $this->get('/')->assertOk()->assertSee(route('games'))->assertDontSee('Elden Ring');
        $this->actingAs($this->admin())->get('/yonetim')->assertSee(route('admin.games.index'));

        Setting::put(['games_catalog' => '0']);
        auth()->logout();
        $this->get('/oyunlar')->assertNotFound();
        $this->get('/')->assertOk()->assertDontSee(route('games'));
    }

    public function test_suggestions_come_from_steam_search(): void
    {
        \Illuminate\Support\Facades\Http::preventStrayRequests();
        \Illuminate\Support\Facades\Http::fake(['api.steampowered.com/*' => \Illuminate\Support\Facades\Http::response(['response' => ['store_items' => [
            // Yeni oyun: dikey kapak karma kodlu klasörde
            ['success' => 1, 'name' => 'Call of Duty®: Modern Warfare® 4', 'assets' => [
                'asset_url_format' => 'steam/apps/4435490/${FILENAME}?t=1',
                'library_capsule' => 'c71b/library_capsule.jpg',
                'header' => '0c49/header.jpg',
            ]],
            // Eski oyun: dikey kapak yok, yatay görsel kullanılır
            ['success' => 1, 'name' => 'Battlefield™ 1', 'assets' => ['asset_url_format' => 'steam/apps/1238840/${FILENAME}?t=2', 'header' => 'header.jpg']],
            ['success' => 1, 'name' => 'Battlefield 1 Soundtrack', 'assets' => ['asset_url_format' => 'steam/apps/9/${FILENAME}', 'header' => 'header.jpg']],
        ]]])]);

        $this->actingAs($this->admin())->getJson(route('admin.games.suggest', ['q' => 'battlefield 1']))
            ->assertOk()
            ->assertJsonCount(2, 'items')
            ->assertJsonPath('items.0.name', 'Call of Duty: Modern Warfare 4')
            ->assertJsonPath('items.0.cover', 'https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/4435490/c71b/library_capsule.jpg?t=1')
            ->assertJsonPath('items.1.cover', null)
            ->assertJsonPath('items.1.wide', 'https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/1238840/header.jpg?t=2');

        $this->actingAs($this->admin())->getJson(route('admin.games.suggest', ['q' => 'b']))->assertJsonCount(0, 'items');
        $this->actingAs(User::factory()->create())->getJson(route('admin.games.suggest', ['q' => 'battlefield']))->assertForbidden();
    }

    public function test_admin_adds_game_with_cover_link_and_bulk_list(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.games.store'), [
            'name' => 'Cyberpunk 2077', 'platform' => 'steam', 'cover_url' => 'https://cdn.example.com/c.jpg', 'featured' => '1', 'is_visible' => '1',
        ])->assertRedirect(route('admin.games.index'));
        $game = Game::firstWhere('name', 'Cyberpunk 2077');
        $this->assertTrue($game->featured);
        $this->assertSame('https://cdn.example.com/c.jpg', $game->coverUrl());

        $this->actingAs($admin)->post(route('admin.games.bulk'), ['platform' => 'steam', 'names' => "Cyberpunk 2077\nElden Ring\n\nelden ring\nRed Dead Redemption 2"])->assertRedirect();
        $this->assertSame(3, Game::count()); // tekrar edenler atlanır

        // Kapak bağlantısı http ya da görsel olmayan adres olamaz; oyun platformu olmayan platform reddedilir.
        $this->actingAs($admin)->post(route('admin.games.store'), ['name' => 'X', 'platform' => 'steam', 'cover_url' => 'http://a.com/x.jpg'])->assertSessionHasErrors('cover_url');
        $this->actingAs($admin)->post(route('admin.games.store'), ['name' => 'X', 'platform' => 'netflix'])->assertSessionHasErrors('platform');
    }

    public function test_cover_upload_and_toggles(): void
    {
        $admin = $this->admin();
        $game = Game::create(['name' => 'Hades', 'platform' => 'epic']);

        $this->actingAs($admin)->put(route('admin.games.update', $game), [
            'name' => 'Hades II', 'platform' => 'epic', 'cover_file' => UploadedFile::fake()->image('kapak.png', 300, 450), 'is_visible' => '1',
        ])->assertRedirect();
        $game->refresh();
        $this->assertSame('Hades II', $game->name);
        $this->assertStringStartsWith('uploads/games/', $game->cover);
        File::delete(public_path($game->cover));

        $this->actingAs($admin)->patch(route('admin.games.toggle', $game), ['field' => 'featured']);
        $this->actingAs($admin)->patch(route('admin.games.toggle', $game), ['field' => 'is_visible']);
        $game->refresh();
        $this->assertTrue($game->featured);
        $this->assertFalse($game->is_visible);

        $this->actingAs(User::factory()->create())->get(route('admin.games.index'))->assertForbidden();
    }

    public function test_linked_game_cover_shows_on_customer_card(): void
    {
        $game = Game::create(['name' => 'GTA V', 'platform' => 'steam', 'cover' => 'https://cdn.example.com/gta.jpg']);
        $account = Account::create(['platform' => 'steam', 'title' => 'Steam GTA', 'game_id' => $game->id, 'totp_secret' => 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ']);
        $body = \App\Support\KeyCode::generate();
        $key = \App\Models\ProductKey::create(['account_id' => $account->id, 'code' => \App\Support\KeyCode::format($body), 'code_hash' => \App\Support\KeyCode::hash($body)]);
        $customer = User::factory()->create();
        $key->forceFill(['redeemed_by' => $customer->id, 'redeemed_at' => now()])->save();

        $this->actingAs($customer)->get('/kodlarim')->assertOk()->assertSee('https://cdn.example.com/gta.jpg');

        $game->delete();
        $this->assertNull($account->fresh()->game_id); // hesap kalır
    }
}
