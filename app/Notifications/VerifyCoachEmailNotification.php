<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyCoachEmailNotification extends BaseVerifyEmail implements ShouldQueue
{
    public function toMail($notifiable): MailMessage
    {
        $url = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Confirme ton adresse e-mail — Power Roster')
            ->line('Merci de t’être inscrit sur Power Roster. Clique sur le bouton ci-dessous pour confirmer ton adresse e-mail et accéder à ton dashboard.')
            ->action('Confirmer mon e-mail', $url)
            ->line('Si tu n’as pas créé de compte, ignore cet e-mail.');
    }
}
