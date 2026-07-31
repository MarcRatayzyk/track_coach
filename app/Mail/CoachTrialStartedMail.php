<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CoachTrialStartedMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public User $coach,
        public int $trialDays,
        public string $trialEndsLabel,
        public string $dashboardUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Ton essai gratuit de {$this->trialDays} jours a commencé",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.coach-trial-started',
        );
    }
}
