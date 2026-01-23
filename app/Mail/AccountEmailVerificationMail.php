<?php

namespace App\Mail;

use App\Models\Account;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountEmailVerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Account $account,
        public string $verificationUrl,
        public ?string $targetEmail = null
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Xác minh email tài khoản của bạn',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.accounts.verify',
            with: [
                'verificationUrl' => $this->verificationUrl,
                'targetEmail' => $this->targetEmail ?? $this->account?->email ?? '',
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
