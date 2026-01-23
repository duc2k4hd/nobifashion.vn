<?php

namespace App\Observers;

use App\Models\Voucher;
use Illuminate\Support\Facades\Cache;

class VoucherObserver
{
    /**
     * Handle the Voucher "created" event.
     */
    public function created(Voucher $voucher): void
    {
        $this->clearVoucherCache($voucher);
    }

    /**
     * Handle the Voucher "updated" event.
     */
    public function updated(Voucher $voucher): void
    {
        $this->clearVoucherCache($voucher);
        
        // Clear cache cho code cũ nếu code bị thay đổi
        if ($voucher->isDirty('code')) {
            $oldCode = $voucher->getOriginal('code');
            if ($oldCode) {
                Cache::forget("voucher:code:{$oldCode}");
            }
        }
    }

    /**
     * Handle the Voucher "deleted" event.
     */
    public function deleted(Voucher $voucher): void
    {
        $this->clearVoucherCache($voucher);
    }

    /**
     * Handle the Voucher "restored" event.
     */
    public function restored(Voucher $voucher): void
    {
        $this->clearVoucherCache($voucher);
    }

    /**
     * Clear all cache related to voucher
     */
    private function clearVoucherCache(Voucher $voucher): void
    {
        // Clear cache theo code
        Cache::forget("voucher:code:{$voucher->code}");
        
        // Clear cache danh sách active vouchers
        Cache::forget('vouchers:active:all');
        Cache::forget('vouchers:active:public');
        
        // Clear cache theo account_id nếu có
        if ($voucher->account_id) {
            Cache::forget("vouchers:active:account:{$voucher->account_id}");
        }
        
        // Clear cache statistics
        Cache::forget('vouchers:statistics');
    }
}


