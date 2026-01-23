<?php

namespace App\Console\Commands;

use App\Models\FlashSale;
use Illuminate\Console\Command;

class UpdateFlashSaleStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'flash-sale:update-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tự động cập nhật trạng thái Flash Sale';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Đang cập nhật trạng thái Flash Sale...');

        // Chuyển draft → active khi đến start_time
        $activated = FlashSale::where('status', 'draft')
            ->where('is_active', true)
            ->where('start_time', '<=', now())
            ->where('end_time', '>=', now())
            ->update(['status' => 'active']);

        // Chuyển active → expired khi hết end_time
        $expired = FlashSale::where('status', 'active')
            ->where('end_time', '<', now())
            ->update(['status' => 'expired']);

        // Auto lock các Flash Sale đang chạy
        FlashSale::where('status', 'active')
            ->where('is_active', true)
            ->where('start_time', '<=', now())
            ->where('end_time', '>=', now())
            ->where('is_locked', false)
            ->update(['is_locked' => true]);

        $this->info("Đã kích hoạt {$activated} Flash Sale.");
        $this->info("Đã kết thúc {$expired} Flash Sale.");
        $this->info('Cập nhật trạng thái Flash Sale thành công!');

        return Command::SUCCESS;
    }
}
