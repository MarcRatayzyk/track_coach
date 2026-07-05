<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends BaseResetPassword
{
    /**
     * @return array<string, mixed>
     */
    public function toMail(object $notifiable): MailMessage
    {
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        return (new MailMessage)
            ->subject('Réinitialise ton mot de passe Track Coach')
            ->line('Tu reçois cet e-mail car nous avons reçu une demande de réinitialisation de mot de passe pour ton compte.')
            ->action('Réinitialiser le mot de passe', $url)
            ->line('Ce lien expirera dans '.config('auth.passwords.'.config('auth.defaults.passwords').'.expire').' minutes.')
            ->line('Si tu n’as pas demandé de réinitialisation, ignore cet e-mail.');
    }
}
