<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response
            ->assertStatus(200)
            ->assertSee(__('auth_pages.no_account'))
            ->assertSee(__('auth_pages.create_account_now'))
            ->assertSee('href="'.route('register').'"', false);
    }

    public function test_arabic_login_screen_links_to_registration(): void
    {
        $response = $this->withSession(['locale' => 'ar'])->get('/login');

        $response
            ->assertOk()
            ->assertSee(__('auth_pages.no_account', locale: 'ar'))
            ->assertSee(__('auth_pages.create_account_now', locale: 'ar'))
            ->assertSee('href="'.route('register').'"', false);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
