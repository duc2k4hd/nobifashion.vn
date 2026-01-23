<?php

namespace App\Observers;

use App\Models\Contact;
use Illuminate\Support\Facades\Log;

class ContactObserver
{
    /**
     * Handle the Contact "created" event.
     */
    public function created(Contact $contact): void
    {
        $this->logStatusChange($contact, 'created', 'Liên hệ mới được tạo');
    }

    /**
     * Handle the Contact "updated" event.
     */
    public function updated(Contact $contact): void
    {
        // Kiểm tra nếu status thay đổi
        if ($contact->wasChanged('status')) {
            $oldStatus = $contact->getOriginal('status');
            $newStatus = $contact->status;
            $this->logStatusChange($contact, 'status_changed', "Trạng thái thay đổi từ {$oldStatus} sang {$newStatus}");
        }

        // Kiểm tra nếu admin_note thay đổi
        if ($contact->wasChanged('admin_note')) {
            $this->logStatusChange($contact, 'note_updated', 'Ghi chú nội bộ được cập nhật');
        }
    }

    /**
     * Handle the Contact "deleted" event.
     */
    public function deleted(Contact $contact): void
    {
        $this->logStatusChange($contact, 'deleted', 'Liên hệ bị xóa mềm');
    }

    /**
     * Handle the Contact "restored" event.
     */
    public function restored(Contact $contact): void
    {
        $this->logStatusChange($contact, 'restored', 'Liên hệ được khôi phục');
    }

    /**
     * Ghi log thay đổi trạng thái vào timeline
     */
    private function logStatusChange(Contact $contact, string $action, string $description): void
    {
        $contact->addTimelineEntry($action, $description);

        Log::info('Contact timeline updated', [
            'contact_id' => $contact->id,
            'action' => $action,
            'description' => $description,
        ]);
    }
}

