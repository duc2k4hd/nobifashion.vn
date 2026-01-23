<?php

namespace App\Mail;

use App\Models\NewsletterSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewsletterVerifyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public NewsletterSubscription $subscription,
        public string $verifyUrl
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Xác nhận đăng ký nhận thông báo từ ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.newsletters.verify',
            with: [
                'subscription' => $this->subscription,
                'verifyUrl' => $this->verifyUrl,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

