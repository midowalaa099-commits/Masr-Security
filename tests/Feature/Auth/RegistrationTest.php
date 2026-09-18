<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
        $response->assertSee(__('auth_pages.password_requirements_short'));
        $this->assertSame(2, substr_count($response->getContent(), 'x-on:click="showPassword = !showPassword"'));
        $this->assertSame(2, substr_count($response->getContent(), 'x-bind:type='));
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '01012345678',
            'password' => 'Q7!mZ2@p',
            'password_confirmation' => 'Q7!mZ2@p',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_weak_passwords_are_rejected(): void
    {
        $response = $this->from('/register')->post('/register', [
            'name' => 'Test User',
            'email' => 'weak-password@example.com',
            'phone' => '01012345678',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response
            ->assertRedirect('/register')
            ->assertSessionHasErrors('password');
        $this->assertGuest();
    }
}
