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
            ->from('/profile')
            ->put('/password', [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertTrue(Hash::check('new-password', $user->refresh()->password));
    }

    public function test_correct_password_must_be_provided_to_update_password(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->put('/password', [
                'current_password' => 'wrong-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('updatePassword', 'current_password')
            ->assertRedirect('/profile');
    }

    public function test_website_account_dashboard_shows_the_password_change_form(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('account.dashboard'))
            ->assertSee(__('admin.account_security'))
            ->assertSee(route('password.update'))
            ->assertSee('name="current_password"', false);
    }

    public function test_admin_can_change_their_password_from_the_website_account_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->from(route('account.dashboard'))
            ->put(route('password.update'), [
                'current_password' => 'password',
                'password' => 'Stronger-password-2026',
                'password_confirmation' => 'Stronger-password-2026',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('account.dashboard'));

        $this->assertTrue(Hash::check('Stronger-password-2026', $admin->refresh()->password));
    }
}
