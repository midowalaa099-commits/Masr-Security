<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('account.password'))
            ->put('/password', [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('account.password'));

        $this->assertTrue(Hash::check('new-password', $user->refresh()->password));
    }

    public function test_correct_password_must_be_provided_to_update_password(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('account.password'))
            ->put('/password', [
                'current_password' => 'wrong-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('updatePassword', 'current_password')
            ->assertRedirect(route('account.password'));
    }

    public function test_password_change_has_a_dedicated_account_page(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('account.dashboard'))
            ->assertDontSee(__('auth_pages.account_security'))
            ->assertDontSee('name="current_password"', false)
            ->assertSee('href="'.route('account.password').'"', false);

        $this->actingAs($admin)
            ->get(route('account.password'))
            ->assertOk()
            ->assertSee(__('auth_pages.account_security'))
            ->assertSee(__('store.change_password'))
            ->assertSee(route('password.update'))
            ->assertSee('name="current_password"', false);
    }

    public function test_guest_cannot_open_the_password_change_page(): void
    {
        $this->get(route('account.password'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_can_change_their_password_from_the_website_account_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->from(route('account.password'))
            ->put(route('password.update'), [
                'current_password' => 'password',
                'password' => 'Stronger-password-2026',
                'password_confirmation' => 'Stronger-password-2026',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('account.password'));

        $this->assertTrue(Hash::check('Stronger-password-2026', $admin->refresh()->password));
    }
}
