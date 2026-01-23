<?php

namespace App\Mail;

use App\Helpers\EmailHelper;
use App\Models\NewsletterSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewsletterMarketingMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public NewsletterSubscription $subscription,
        protected string $emailSubject,
        protected string $template,
        public array $data = [],
        public ?int $emailAccountId = null
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: EmailHelper::getFromEmail($this->emailAccountId),
            subject: $this->emailSubject,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: "emails.newsletters.{$this->template}",
            with: array_merge([
                'subscription' => $this->subscription,
                'subject' => $this->emailSubject,
            ], $this->data),
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

