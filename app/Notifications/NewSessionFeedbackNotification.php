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

        $athleteName = $this->feedback->athlete?->name ?? 'Un athlète';
        $sessionDate = $this->feedback->session_date?->locale('fr')->isoFormat('D MMMM YYYY') ?? '';

        return (new MailMessage)
            ->subject("Nouveau retour vidéo — {$athleteName}")
            ->line("{$athleteName} a envoyé un retour vidéo pour la séance du {$sessionDate}.")
            ->action('Voir le retour', url('/feedbacks/'.$this->feedback->id));
    }
}
