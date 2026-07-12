<?php

namespace App\Support;

use Illuminate\Notifications\Notification;
use Throwable;

class MailSendSupport
{
    public const DELIVERY_FAILED_MESSAGE = 'Impossible d\'envoyer l\'e-mail pour le moment. Réessaie dans quelques minutes.';

    public static function attempt(callable $callback): bool
    {
        try {
            $callback();

            return true;
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }
    }

    public static function notifySafely(?object $notifiable, Notification $notification): void
    {
        if ($notifiable === null) {
            return;
        }

        self::attempt(fn () => $notifiable->notify($notification));
    }
}
