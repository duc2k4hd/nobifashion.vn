<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $table = 'payments';

    protected $fillable = [
        'order_id',
        'account_id',
        'method',
        'amount',
        'status',
        'transaction_code',
        'gateway',
        'raw_response',
        'card_brand',
        'last_four',
        'receipt_url',
        'notes',
        'paid_at',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'paid_at'      => 'datetime',
        'raw_response' => 'array',
    ];

    // ------------------------------
    // Quan hệ
    // ------------------------------

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    // ------------------------------
    // Scope
    // ------------------------------

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    // ------------------------------
    // Hàm tiện ích
    // ------------------------------

    /**
     * Đánh dấu đã thanh toán
     */
    public function markAsPaid(string $transactionCode = null)
    {
        $this->update([
            'status'          => 'completed',
            'transaction_code'=> $transactionCode,
            'paid_at'         => now(),
        ]);
    }

    /**
     * Đánh dấu thanh toán thất bại
     */
    public function markAsFailed(string $note = null)
    {
        $this->update([
            'status' => 'failed',
            'raw_response' => array_merge(
                $this->raw_response ?? [],
                ['error' => $note]
            ),
        ]);
    }

    /**
     * Kiểm tra đã thanh toán chưa
     */
    public function isPaid(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Kiểm tra có phải PayOS payment không
     */
    public function isPayOS(): bool
    {
        return $this->method === 'payos';
    }

    /**
     * Lấy PayOS order code
     */
    public function getPayOSOrderCode(): ?string
    {
        return $this->transaction_code;
    }
}
