<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StaffResetPassword extends Notification
{
    use Queueable;

    public function __construct(public string $url)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Staff Password Reset'))
            ->line(__('You are receiving this email because we received a password reset request for your staff account.'))
            ->action(__('Reset Password'), $this->url)
            ->line(__('This password reset link will expire in :count minutes.', ['count' => config('auth.passwords.staff.expire', 60)]))
            ->line(__('If you did not request a password reset, no further action is required.'));
    }
}
