<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class BugReportMail extends Mailable
{
    /**
     * @param  array{title: string, category: string, description: string, page_url: ?string, user_agent: ?string}  $report
     */
    public function __construct(
        public User $reporter,
        public array $report,
        public ?UploadedFile $screenshot = null,
    ) {}

    public function envelope(): Envelope
    {
        $category = $this->categoryLabel($this->report['category']);

        return new Envelope(
            subject: "[Power Roster] {$category} : {$this->report['title']}",
            replyTo: [
                new Address($this->reporter->email, $this->reporter->name),
            ],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.bug-report',
            with: [
                'categoryLabel' => $this->categoryLabel($this->report['category']),
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        if ($this->screenshot === null) {
            return [];
        }

        return [
            Attachment::fromPath($this->screenshot->getRealPath())
                ->as($this->screenshot->getClientOriginalName() ?: 'screenshot.'.$this->screenshot->extension())
                ->withMime($this->screenshot->getMimeType() ?: 'application/octet-stream'),
        ];
    }

    private function categoryLabel(string $category): string
    {
        return match ($category) {
            'bug' => 'Bug',
            'fix' => 'Correctif',
            'idea' => 'Idée',
            default => 'Autre',
        };
    }
}
