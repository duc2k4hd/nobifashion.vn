<?php

namespace App\Notifications;

use App\Models\Contact;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewContactNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public Contact $contact;

    /**
     * Create a new notification instance.
     */
    public function __construct(Contact $contact)
    {
        $this->contact = $contact;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Có liên hệ mới: ' . ($this->contact->subject ?? 'Không có tiêu đề'))
            ->line('Bạn có một liên hệ mới từ khách hàng.')
            ->line('Tên: ' . ($this->contact->name ?? 'N/A'))
            ->line('Email: ' . ($this->contact->email ?? 'N/A'))
            ->line('Chủ đề: ' . ($this->contact->subject ?? 'N/A'))
            ->action('Xem chi tiết', route('admin.contacts.show', $this->contact))
            ->line('Vui lòng xử lý liên hệ này trong thời gian sớm nhất.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'contact_id' => $this->contact->id,
            'contact_name' => $this->contact->name,
            'contact_email' => $this->contact->email,
            'subject' => $this->contact->subject,
            'message' => 'Có liên hệ mới từ ' . ($this->contact->name ?? 'Khách hàng'),
            'url' => route('admin.contacts.show', $this->contact),
        ];
    }
}

