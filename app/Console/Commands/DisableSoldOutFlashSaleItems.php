<?php

namespace App\Console\Commands;

use App\Models\FlashSaleItem;
use Illuminate\Console\Command;

class DisableSoldOutFlashSaleItems extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'flash-sale:disable-sold-out';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tự động tắt các sản phẩm Flash Sale đã hết hàng';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Đang kiểm tra sản phẩm Flash Sale đã hết hàng...');

        $disabled = FlashSaleItem::where('is_active', true)
            ->whereRaw('stock <= sold')
            ->update(['is_active' => false]);

        $this->info("Đã tắt {$disabled} sản phẩm Flash Sale đã hết hàng.");

        return Command::SUCCESS;
    }
}
