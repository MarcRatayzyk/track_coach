<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DemoWelcomeMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public User $coach,
        public int $demoHours,
        public string $expiresLabel,
        public string $dashboardUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Ta démo Power Roster est prête',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.demo-welcome',
        );
    }
}
