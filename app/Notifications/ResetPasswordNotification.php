<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends BaseResetPassword implements ShouldQueue
{
    use Queueable;

    /**
     * @return array<string, mixed>
     */
    public function toMail($notifiable): MailMessage
    {
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        $minutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject(__('mail.reset_password.subject'))
            ->line(__('mail.reset_password.line1'))
            ->action(__('mail.reset_password.action'), $url)
            ->line(__('mail.reset_password.expires', ['minutes' => $minutes]))
            ->line(__('mail.reset_password.line2'))
            ->salutation(__('mail.salutation'));
    }
}
