<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewMessageNotification extends Notification implements ShouldQueue
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

        $senderName = $this->message->sender?->name ?? __('mail.new_message.someone');

        return (new MailMessage)
            ->subject(__('mail.new_message.subject', ['name' => $senderName]))
            ->line(__('mail.new_message.line', ['name' => $senderName]))
            ->action(__('mail.new_message.action'), url('/messaging?thread='.$this->message->thread_id))
            ->salutation(__('mail.salutation'));
    }
}
