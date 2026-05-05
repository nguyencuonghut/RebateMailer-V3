<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MailpitProbeMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $appName,
        public string $sentAt,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Kiểm thử Mailpit cục bộ - {$this->appName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.mailpit-probe',
            with: [
                'appName' => $this->appName,
                'sentAt' => $this->sentAt,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
