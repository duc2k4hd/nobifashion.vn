<?php

namespace App\Mail;

use App\Models\Contact;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ContactReplyMail extends Mailable
{
    use Queueable, SerializesModels;

    public Contact $contact;
    public string $replyMessage;
    public ?string $attachmentPath = null;
    public ?string $attachmentName = null;
    public ?string $attachmentMime = null;

    /**
     * Create a new message instance.
     */
    public function __construct(Contact $contact, string $replyMessage, $attachmentFile = null)
    {
        $this->contact = $contact;
        $this->replyMessage = $replyMessage;

        // Lưu file tạm thời và lưu thông tin path thay vì UploadedFile object
        if ($attachmentFile && $attachmentFile->isValid()) {
            // Lưu file vào storage tạm thời
            $tempPath = 'temp/contacts/reply_' . time() . '_' . uniqid() . '_' . $attachmentFile->getClientOriginalName();
            Storage::disk('public')->put($tempPath, file_get_contents($attachmentFile->getRealPath()));
            
            $this->attachmentPath = Storage::disk('public')->path($tempPath);
            $this->attachmentName = $attachmentFile->getClientOriginalName();
            $this->attachmentMime = $attachmentFile->getMimeType();
        }
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Phản hồi liên hệ: ' . ($this->contact->subject ?? 'Liên hệ của bạn'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.contacts.reply',
            with: [
                'contact' => $this->contact,
                'replyMessage' => $this->replyMessage,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];

        if ($this->attachmentPath && file_exists($this->attachmentPath)) {
            $attachments[] = Attachment::fromPath($this->attachmentPath)
                ->as($this->attachmentName)
                ->withMime($this->attachmentMime);
        }

        return $attachments;
    }

    /**
     * Clean up temporary file after sending
     * Sử dụng event listener thay vì __destruct để đảm bảo file không bị xóa quá sớm
     */
    public function cleanup(): void
    {
        if ($this->attachmentPath && file_exists($this->attachmentPath)) {
            // Xóa file tạm sau khi gửi email
            @unlink($this->attachmentPath);
        }
    }
}

