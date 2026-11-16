<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_renders(): void
    {
        $this->get('/login')->assertOk()->assertSee('Bu cihazda açık kalsın')->assertSee('Şifremi unuttum');
    }

    public function test_remember_me_sets_a_remember_cookie(): void
    {
        $user = User::factory()->create(['password' => 'gizli-sifre-1']);

        $this->post('/login', ['email' => $user->email, 'password' => 'gizli-sifre-1', 'remember' => '1'])
            ->assertRedirect()
            ->assertCookie(Auth::guard()->getRecallerName());

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->remember_token);
    }

    public function test_login_without_remember_sets_no_remember_cookie(): void
    {
        $user = User::factory()->create(['password' => 'gizli-sifre-1']);

        $this->post('/login', ['email' => $user->email, 'password' => 'gizli-sifre-1'])
            ->assertRedirect()
            ->assertCookieMissing(Auth::guard()->getRecallerName());

        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_returns_an_error_and_keeps_the_email(): void
    {
        $user = User::factory()->create(['password' => 'gizli-sifre-1']);

        $this->from('/login')->post('/login', ['email' => $user->email, 'password' => 'yanlis'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email')
            ->assertSessionHasInput('email', $user->email);

        $this->assertGuest();
    }

    public function test_forgot_password_page_renders(): void
    {
        $this->get('/forgot-password')->assertOk()->assertSee('Bağlantıyı gönder');
    }
}
