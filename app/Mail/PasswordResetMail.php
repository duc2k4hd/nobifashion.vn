<?php

namespace App\Mail;

use App\Helpers\EmailHelper;
use App\Models\Account;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public Account $account,
        public string $resetUrl,
        public ?int $emailAccountId = null
    ) {
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $fromEmail = EmailHelper::getFromEmail($this->emailAccountId);
        $fromName = EmailHelper::getFromName($this->emailAccountId);
        
        $from = null;
        if ($fromEmail) {
            $from = $fromName 
                ? new Address($fromEmail, $fromName)
                : $fromEmail;
        }
        
        return new Envelope(
            from: $from,
            subject: 'Đặt lại mật khẩu tài khoản của bạn',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.auth.password-reset',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
