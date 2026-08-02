<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FeedbackRepliedNotification extends Notification implements ShouldQueue
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

        $coachName = $this->message->sender?->name ?? __('mail.feedback_replied.your_coach');

        return (new MailMessage)
            ->subject(__('mail.feedback_replied.subject', ['name' => $coachName]))
            ->line(__('mail.feedback_replied.line', ['name' => $coachName]))
            ->action(__('mail.feedback_replied.action'), url('/messaging?thread='.$this->message->thread_id))
            ->salutation(__('mail.salutation'));
    }
}
