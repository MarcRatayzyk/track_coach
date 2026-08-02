<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyCoachEmailNotification extends BaseVerifyEmail implements ShouldQueue
{
    use Queueable;

    public function toMail($notifiable): MailMessage
    {
        $url = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject(__('mail.verify_email.subject'))
            ->line(__('mail.verify_email.line1'))
            ->action(__('mail.verify_email.action'), $url)
            ->line(__('mail.verify_email.line2'))
            ->salutation(__('mail.salutation'));
    }
}
