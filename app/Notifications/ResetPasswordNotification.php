<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly string $token) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $resetUrl = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        $expiresInMinutes = (int) config('auth.passwords.users.expire');

        return (new MailMessage)
            ->subject(__('auth_pages.reset_email_subject'))
            ->greeting(__('auth_pages.reset_email_greeting', ['name' => $notifiable->name]))
            ->line(__('auth_pages.reset_email_intro'))
            ->action(__('auth_pages.reset_email_action'), $resetUrl)
            ->line(__('auth_pages.reset_email_expiry', ['minutes' => $expiresInMinutes]))
            ->line(__('auth_pages.reset_email_security'))
            ->salutation(__('auth_pages.reset_email_salutation', [
                'company' => config('app.name', 'MASR Security'),
            ]));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
