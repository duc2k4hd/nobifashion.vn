<?php

namespace App\Console\Commands;

use App\Models\Voucher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateVoucherStatusCommand extends Command
{
    protected $signature = 'voucher:update-status';
    protected $description = 'Tự động cập nhật trạng thái voucher (scheduled → active → expired)';

    public function handle(): int
    {
        $this->info('Đang cập nhật trạng thái voucher...');
        
        $now = now();
        $updated = 0;

        // Cập nhật scheduled → active
        $scheduledToActive = Voucher::where('status', 'scheduled')
            ->whereNotNull('start_at')
            ->where('start_at', '<=', $now)
            ->update(['status' => 'active']);
        
        if ($scheduledToActive > 0) {
            $this->info("✓ Đã kích hoạt {$scheduledToActive} voucher scheduled → active");
            $updated += $scheduledToActive;
        }

        // Cập nhật active → expired (khi end_at đã qua)
        $activeToExpired = Voucher::where('status', 'active')
            ->whereNotNull('end_at')
            ->where('end_at', '<', $now)
            ->update(['status' => 'expired']);
        
        if ($activeToExpired > 0) {
            $this->info("✓ Đã hết hạn {$activeToExpired} voucher active → expired");
            $updated += $activeToExpired;
        }

        // Cập nhật active → expired (khi hết lượt sử dụng)
        $outOfUsage = Voucher::where('status', 'active')
            ->whereNotNull('usage_limit')
            ->whereRaw('usage_count >= usage_limit')
            ->update(['status' => 'expired']);
        
        if ($outOfUsage > 0) {
            $this->info("✓ Đã hết lượt {$outOfUsage} voucher active → expired");
            $updated += $outOfUsage;
        }

        if ($updated === 0) {
            $this->info('Không có voucher nào cần cập nhật trạng thái.');
        } else {
            Log::info('Voucher status updated', ['count' => $updated]);
        }

        return Command::SUCCESS;
    }
}


