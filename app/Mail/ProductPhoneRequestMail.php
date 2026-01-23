<?php

namespace App\Mail;

use App\Helpers\EmailHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProductPhoneRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $phone;
    public string $productName;
    public string $productSku;
    public string $productUrl;
    public string $ipAddress;
    public string $userAgent;
    public ?int $emailAccountId;

    /**
     * Create a new message instance.
     */
    public function __construct(
        string $phone,
        string $productName,
        string $productSku,
        string $productUrl,
        string $ipAddress,
        string $userAgent,
        ?int $emailAccountId = null
    ) {
        $this->phone = $phone;
        $this->productName = $productName;
        $this->productSku = $productSku;
        $this->productUrl = $productUrl;
        $this->ipAddress = $ipAddress;
        $this->userAgent = $userAgent;
        $this->emailAccountId = $emailAccountId;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: EmailHelper::getFromEmail($this->emailAccountId),
            subject: '🔔 Khách hàng cần tư vấn: ' . $this->phone . ' - Sản phẩm: ' . $this->productName,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.products.phone-request',
            with: [
                'phone' => $this->phone,
                'productName' => $this->productName,
                'productSku' => $this->productSku,
                'productUrl' => $this->productUrl,
                'ipAddress' => $this->ipAddress,
                'userAgent' => $this->userAgent,
            ],
        );
    }
}

