<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) {
            $response = $this->get('/reset-password/'.$notification->token);

            $response->assertStatus(200);

            return true;
        });
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) use ($user) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

            $response
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('login'));

            return true;
        });
    }

    public function test_reset_password_email_contains_the_secure_branded_action(): void
    {
        Notification::fake();

        $user = User::factory()->create(['name' => 'Mona']);

        $this->post(route('password.email'), ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPasswordNotification::class, function (ResetPasswordNotification $notification) use ($user): bool {
            $message = $notification->toMail($user);

            $this->assertSame(__('auth_pages.reset_email_subject'), $message->subject);
            $this->assertSame(__('auth_pages.reset_email_action'), $message->actionText);
            $this->assertStringContainsString(route('password.reset', $notification->token), $message->actionUrl);
            $this->assertStringContainsString(urlencode($user->email), $message->actionUrl);

            return true;
        });
    }

    public function test_admin_can_request_a_password_reset_email(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();

        $this->post(route('password.email'), ['email' => $admin->email])
            ->assertSessionHasNoErrors();

        Notification::assertSentTo($admin, ResetPasswordNotification::class);
    }

    public function test_password_reset_requests_are_rate_limited(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post(route('password.email'), ['email' => $user->email]);
        }

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertTooManyRequests();
    }
}
