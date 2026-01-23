<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $table = 'orders';

    protected $fillable = [
        'code',
        'account_id',
        'session_id',
        'total_price',
        'shipping_fee',
        'tax',
        'discount',
        'voucher_id',
        'voucher_discount',
        'voucher_code',
        'final_price',
        'receiver_name',
        'receiver_phone',
        'receiver_email',
        'shipping_address',
        'shipping_province_id',
        'shipping_district_id',
        'shipping_ward_id',
        'payment_method',
        'payment_status',
        'transaction_code',
        'shipping_partner',
        'shipping_tracking_code',
        'shipping_raw_response',
        'delivery_status',
        'status',
        'customer_note',
        'admin_note',
        'is_flash_sale',
    ];

    protected $casts = [
        'total_price'          => 'decimal:2',
        'shipping_fee'         => 'decimal:2',
        'tax'                  => 'decimal:2',
        'discount'             => 'decimal:2',
        'voucher_discount'     => 'decimal:2',
        'final_price'          => 'decimal:2',
        'shipping_raw_response'=> 'array',
        'is_flash_sale'        => 'boolean',
    ];

    // ------------------------------
    // Quan hệ
    // ------------------------------

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }

    // ------------------------------
    // Scope
    // ------------------------------

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    // ------------------------------
    // Hàm tiện ích
    // ------------------------------

    public function markAsProcessing()
    {
        $this->update(['status' => 'processing']);
    }

    public function markAsCompleted()
    {
        $this->update(['status' => 'completed', 'delivery_status' => 'delivered']);
    }

    public function markAsCancelled(string $note = null)
    {
        $this->update([
            'status' => 'cancelled',
            'admin_note' => $note,
        ]);
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function isDelivered(): bool
    {
        return $this->delivery_status === 'delivered';
    }

    // Normalize payment_method to enum values at assignment time
    public function setPaymentMethodAttribute($value): void
    {
        $normalized = in_array($value, ['bank', 'payos'], true) ? 'bank_transfer' : (string) $value;
        $this->attributes['payment_method'] = $normalized;
    }

    /**
     * Kiểm tra có phải thanh toán PayOS không
     */
    public function isPayOSPayment(): bool
    {
        return $this->payment_method === 'payos';
    }

    /**
     * Lấy payment PayOS gần nhất
     */
    public function getPayOSPayment()
    {
        return $this->payments()->where('method', 'payos')->latest()->first();
    }

    /**
     * Lịch sử trạng thái giao hàng (GHN)
     */
    public function getShippingStatusHistoryAttribute(): array
    {
        $raw = $this->shipping_raw_response ?? [];
        return $raw['status_history'] ?? [];
    }

    /**
     * Trạng thái giao hàng hiện tại (GHN)
     */
    public function getCurrentShippingStatusAttribute(): ?string
    {
        $raw = $this->shipping_raw_response ?? [];
        return $raw['current_status'] ?? null;
    }

    /**
     * Metadata trạng thái giao hàng hiện tại
     */
    public function getCurrentShippingStatusMetaAttribute(): ?array
    {
        $status = $this->current_shipping_status;
        if (!$status) {
            return null;
        }
        $definitions = config('ghn.shipping_statuses', []);
        if (!isset($definitions[$status])) {
            return null;
        }

        return array_merge([
            'status' => $status,
        ], $definitions[$status]);
    }

    /**
     * Có thể lên đơn GHN hay không
     */
    public function canCreateGhnShipment(): bool
    {
        // Đã có tracking code rồi thì không tạo lại
        if ($this->shipping_tracking_code) {
            return false;
        }

        // Đơn đã hủy hoặc hoàn thành thì không tạo
        if (in_array($this->status, ['cancelled', 'completed'], true)) {
            return false;
        }

        // Delivery status đã hủy thì không tạo
        if ($this->delivery_status === 'cancelled') {
            return false;
        }

        // Nếu đã chỉ định hãng vận chuyển khác, không hiển thị nút GHN
        if ($this->shipping_partner && $this->shipping_partner !== 'ghn') {
            return false;
        }

        // Kiểm tra đủ thông tin địa chỉ
        if (!$this->shipping_province_id || !$this->shipping_district_id || !$this->shipping_ward_id) {
            return false;
        }

        // Kiểm tra thông tin người nhận
        if (!$this->receiver_name || !$this->receiver_phone || !$this->shipping_address) {
            return false;
        }

        // Cho phép tạo GHN khi đơn ở trạng thái hợp lệ (pending, processing)
        // Không bắt buộc payment_status === 'paid' vì có thể tạo đơn trước, thanh toán sau
        return true;
    }

    /**
     * Có thể hủy đơn hay không
     */
    public function canCancel(): bool
    {
        if (in_array($this->status, ['completed', 'cancelled'], true)) {
            return false;
        }

        if ($this->delivery_status === 'delivered') {
            return false;
        }

        return true;
    }
}
