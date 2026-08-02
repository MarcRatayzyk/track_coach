<?php

namespace App\Notifications;

use App\Models\SessionFeedback;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewSessionFeedbackNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public SessionFeedback $feedback,
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
        $this->feedback->loadMissing(['athlete:id,name', 'programTrainingDay']);

        $athleteName = $this->feedback->athlete?->name ?? __('mail.new_feedback.an_athlete');
        $sessionDate = $this->feedback->session_date?->locale(app()->getLocale())->isoFormat('D MMMM YYYY') ?? '';

        return (new MailMessage)
            ->subject(__('mail.new_feedback.subject', ['name' => $athleteName]))
            ->line(__('mail.new_feedback.line', ['name' => $athleteName, 'date' => $sessionDate]))
            ->action(__('mail.new_feedback.action'), url('/feedbacks/'.$this->feedback->id))
            ->salutation(__('mail.salutation'));
    }
}
