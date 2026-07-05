<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FeedbackRepliedNotification extends Notification
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
        $this->message->loadMissing(['sender:id,name', 'sessionFeedback']);

        $coachName = $this->message->sender?->name ?? 'Ton coach';

        return (new MailMessage)
            ->subject("{$coachName} a répondu à ton retour")
            ->line("{$coachName} a répondu à ton retour vidéo.")
            ->action('Voir la réponse', url('/messaging?thread='.$this->message->thread_id));
    }
}
