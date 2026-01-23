<?php

namespace App\Services;

use App\Models\Contact;
use App\Mail\ContactReplyMail;
use App\Services\MailConfigService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

class ContactReplyService
{
    /**
     * Gửi email trả lời liên hệ
     */
    public function sendReply(Contact $contact, string $replyMessage, $attachment = null): array
    {
        try {
            if (!$contact->canReply()) {
                throw new \Exception('Không thể trả lời liên hệ này (thiếu email hoặc đã bị đánh dấu spam).');
            }

            // Tạo mailable instance
            $mail = new ContactReplyMail($contact, $replyMessage, $attachment);
            
            // Gửi email (sync để đảm bảo gửi ngay)
            try {
                $emailAccountId = config('email_defaults.contact_reply');
                MailConfigService::sendWithAccount($emailAccountId, function () use ($contact, $mail) {
                    Mail::to($contact->email)->send($mail);
                });
                Log::info('Email sent successfully', [
                    'to' => $contact->email,
                    'subject' => 'Phản hồi liên hệ: ' . ($contact->subject ?? 'Liên hệ của bạn'),
                ]);
            } catch (\Exception $mailException) {
                Log::error('Mail sending failed', [
                    'to' => $contact->email,
                    'error' => $mailException->getMessage(),
                    'trace' => $mailException->getTraceAsString(),
                ]);
                throw $mailException;
            }
            
            // Cleanup file tạm sau khi gửi
            $mail->cleanup();

            // Lưu nội dung trả lời vào admin_note
            $note = "\n\n--- Trả lời email ---\n";
            $note .= "Thời gian: " . now()->format('d/m/Y H:i') . "\n";
            $note .= "Người trả lời: " . (auth('web')->user()->name ?? 'Admin') . "\n";
            $note .= "Nội dung: " . $replyMessage;

            $contact->update([
                'admin_note' => ($contact->admin_note ?? '') . $note,
                'status' => $contact->status === 'new' ? 'processing' : $contact->status,
            ]);

            Log::info('Contact reply sent', [
                'contact_id' => $contact->id,
                'email' => $contact->email,
                'replied_by' => auth('web')->id(),
            ]);

            return [
                'success' => true,
                'message' => 'Đã gửi email trả lời thành công.',
            ];

        } catch (\Exception $e) {
            Log::error('Failed to send contact reply', [
                'contact_id' => $contact->id,
                'email' => $contact->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Cleanup file tạm nếu có lỗi
            if (isset($mail)) {
                $mail->cleanup();
            }

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}

