<?php

namespace App\Support;

use App\Mail\AthleteInvitationMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class ActivationDelivery
{
    public static function usesManualLinks(): bool
    {
        return (bool) config('trackcoach.manual_activation_links', true);
    }

    public static function markCoachEmailVerified(User $coach): void
    {
        if ($coach->hasVerifiedEmail()) {
            return;
        }

        $coach->forceFill(['email_verified_at' => now()])->save();
    }

    /**
     * @return bool|null null = manual link only, true/false = e-mail attempt result
     */
    public static function sendAthleteInvitation(User $athlete, User $coach, string $setupUrl): ?bool
    {
        if (self::usesManualLinks() || $athlete->hasPendingEmail()) {
            return null;
        }

        return MailSendSupport::attempt(
            fn () => Mail::to($athlete)->send(new AthleteInvitationMail($athlete, $coach, $setupUrl)),
        );
    }

    /**
     * @return bool|null null = manual link only, true/false = e-mail attempt result
     */
    public static function sendCoachEmailVerification(User $coach): ?bool
    {
        if (self::usesManualLinks()) {
            self::markCoachEmailVerified($coach);

            return null;
        }

        return MailSendSupport::attempt(
            fn () => $coach->sendEmailVerificationNotification(),
        );
    }

    public static function athleteInvitationSuccessMessage(string $label, ?bool $emailSent, bool $emailPending = false): string
    {
        if (self::usesManualLinks() || $emailPending) {
            return "Athlète ajouté. Copie le lien d’activation et transmets-le à {$label} (WhatsApp, SMS…).";
        }

        return $emailSent
            ? "Invitation envoyée par e-mail. Tu as aussi le lien d’activation ci-dessous à partager si besoin."
            : "Athlète ajouté, mais l’e-mail d’invitation n’a pas pu être envoyé. Partage le lien d’activation ci-dessous.";
    }

    public static function athleteResendSuccessMessage(string $label, ?bool $emailSent, bool $emailPending = false): string
    {
        if (self::usesManualLinks() || $emailPending) {
            return "Lien d’activation régénéré pour {$label}. Copie-le et transmets-le à l’athlète.";
        }

        return $emailSent
            ? "Invitation renvoyée à {$label}."
            : "Impossible de renvoyer l’e-mail. Partage le lien d’activation ci-dessous.";
    }
}
