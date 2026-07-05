<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CoachInvitationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public User $coach,
        public string $setupUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Active ton compte coach Track Coach',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.coach-invitation',
        );
    }
}
