<?php

namespace App\Services;

use App\Models\Contact;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ContactService
{
    protected SpamDetector $spamDetector;

    public function __construct(SpamDetector $spamDetector)
    {
        $this->spamDetector = $spamDetector;
    }

    /**
     * Tạo liên hệ mới
     */
    public function createContact(array $data, $file = null): Contact
    {
        // Xử lý file đính kèm
        if ($file && $file->isValid()) {
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->storeAs('contacts', $filename, 'public');
            $data['attachment'] = $filename;
        }

        // Tự động lưu IP và user agent
        $data['ip_address'] = request()->ip();
        $data['user_agent'] = request()->userAgent();
        $data['source'] = $data['source'] ?? 'web';

        // Nếu user đã đăng nhập
        if (auth('web')->check()) {
            $data['user_id'] = auth('web')->id();
        }

        $contact = Contact::create($data);

        // Kiểm tra spam
        if ($this->spamDetector->detect($contact)) {
            $contact->update(['status' => 'spam']);
            Log::info('Contact auto-marked as spam', [
                'contact_id' => $contact->id,
                'email' => $contact->email,
            ]);
        }

        return $contact;
    }

    /**
     * Cập nhật trạng thái liên hệ
     */
    public function updateStatus(Contact $contact, string $status, ?string $note = null): Contact
    {
        $contact->update([
            'status' => $status,
            'admin_note' => $note ? ($contact->admin_note . "\n\n" . $note) : $contact->admin_note,
        ]);

        // Nếu chuyển từ spam sang trạng thái khác, reset rate limit
        if ($contact->getOriginal('status') === 'spam' && $status !== 'spam') {
            $this->spamDetector->resetRateLimit($contact->email, $contact->ip_address);
        }

        return $contact->fresh();
    }

    /**
     * Cập nhật ghi chú nội bộ
     */
    public function updateNote(Contact $contact, string $note): Contact
    {
        $contact->update(['admin_note' => $note]);
        return $contact->fresh();
    }

    /**
     * Bulk update status
     */
    public function bulkUpdateStatus(array $contactIds, string $status): int
    {
        return Contact::whereIn('id', $contactIds)
            ->update(['status' => $status]);
    }

    /**
     * Bulk delete (soft delete)
     */
    public function bulkDelete(array $contactIds): int
    {
        return Contact::whereIn('id', $contactIds)->delete();
    }

    /**
     * Xóa file đính kèm
     */
    public function deleteAttachment(Contact $contact): bool
    {
        if ($contact->attachment) {
            Storage::disk('public')->delete('contacts/' . $contact->attachment);
            $contact->update(['attachment' => null]);
            return true;
        }
        return false;
    }
}

