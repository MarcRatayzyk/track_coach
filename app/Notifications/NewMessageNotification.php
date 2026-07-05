<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewMessageNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Message $message,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->message->loadMissing('sender:id,name');

        $senderName = $this->message->sender?->name ?? 'Quelqu’un';

        return (new MailMessage)
            ->subject("Nouveau message de {$senderName}")
            ->line("{$senderName} t’a envoyé un message sur Track Coach.")
            ->action('Ouvrir la messagerie', url('/messaging?thread='.$this->message->thread_id));
    }
}
