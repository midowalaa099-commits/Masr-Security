<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AccountDetailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_requires_a_valid_mobile_number(): void
    {
        $this->post(route('register'), [
            'name' => 'Customer',
            'email' => 'customer@example.com',
            'phone' => '12345',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ])->assertSessionHasErrors('phone');
    }

    public function test_registration_rejects_a_mobile_number_already_used_by_another_account(): void
    {
        User::factory()->create(['phone' => '01012345678']);

        $this->post(route('register'), [
            'name' => 'Another Customer',
            'email' => 'another@example.com',
            'phone' => '+20 101 234 5678',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ])->assertSessionHasErrors('phone');
    }

    public function test_customer_can_register_and_log_in_with_mobile_or_email(): void
    {
        $this->post(route('register'), [
            'name' => 'Customer',
            'email' => 'customer@example.com',
            'phone' => '+20 101 234 5678',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ])->assertRedirect();

        $user = User::query()->where('email', 'customer@example.com')->firstOrFail();
        $this->assertSame('01012345678', $user->phone);

        $this->post(route('logout'));

        $this->post(route('login'), ['email' => '٠١٠١٢٣٤٥٦٧٨', 'password' => 'SecurePass123!'])
            ->assertRedirect();
        $this->assertAuthenticatedAs($user);

        $this->post(route('logout'));
        $this->post(route('login'), ['email' => 'customer@example.com', 'password' => 'SecurePass123!'])
            ->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }

    public function test_sensitive_account_changes_require_current_password(): void
    {
        $user = User::factory()->create(['phone' => '01012345678']);

        $this->actingAs($user)->patch(route('account.update'), [
            'name' => 'New Name',
            'email' => 'new@example.com',
            'phone' => '01112345678',
        ])->assertSessionHasErrors('current_password');

        $this->assertNotSame('new@example.com', $user->fresh()->email);

        $this->actingAs($user)->patch(route('account.update'), [
            'name' => 'New Name',
            'email' => 'new@example.com',
            'phone' => '01112345678',
            'current_password' => 'password',
        ])->assertRedirect(route('account.edit'));

        $this->assertSame('New Name', $user->fresh()->name);
        $this->assertSame('01112345678', $user->fresh()->phone);
    }

    public function test_admin_can_leave_mobile_number_empty(): void
    {
        $admin = User::factory()->admin()->create(['phone' => null]);

        $this->actingAs($admin)->get(route('account.edit'))->assertOk()
            ->assertSee(__('auth_pages.account_details'));

        $this->actingAs($admin)->patch(route('account.update'), [
            'name' => 'Admin Name',
            'email' => $admin->email,
            'phone' => '',
        ])->assertRedirect(route('account.edit'));

        $this->assertNull($admin->fresh()->phone);
    }

    public function test_changing_email_resets_verification_and_sends_a_new_link(): void
    {
        Notification::fake();

        $user = User::factory()->create(['phone' => '01012345678']);

        $this->actingAs($user)->patch(route('account.update'), [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'phone' => '01112345678',
            'current_password' => 'password',
        ])->assertRedirect(route('account.edit'));

        $updatedUser = $user->fresh();

        $this->assertSame('Updated Name', $updatedUser->name);
        $this->assertSame('updated@example.com', $updatedUser->email);
        $this->assertSame('01112345678', $updatedUser->phone);
        $this->assertNull($updatedUser->email_verified_at);
        Notification::assertSentTo($updatedUser, VerifyEmail::class);
    }

    public function test_changing_name_does_not_reset_email_verification_or_send_a_link(): void
    {
        Notification::fake();

        $user = User::factory()->create(['phone' => '01012345678']);

        $this->actingAs($user)->patch(route('account.update'), [
            'name' => 'Updated Name',
            'email' => $user->email,
            'phone' => $user->phone,
        ])->assertRedirect(route('account.edit'));

        $this->assertNotNull($user->fresh()->email_verified_at);
        Notification::assertNothingSent();
    }
}
