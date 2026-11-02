<?php

namespace App\Console\Commands;

use App\Services\ReelAnalytics\ReelMetricsRollupService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class RollupReelsDailyMetricsCommand extends Command
{
    /**
     * Tên và cú pháp của lệnh console
     */
    protected $signature = 'reels:rollup-metrics {--date= : Ngày cần tổng hợp (YYYY-MM-DD), mặc định là ngày hôm qua}';

    /**
     * Mô tả lệnh
     */
    protected $description = 'Tổng hợp số liệu thống kê lượt xem và tương tác video Reels theo ngày (Rollup)';

    /**
     * Thực thi lệnh
     */
    public function handle(ReelMetricsRollupService $rollupService): int
    {
        $date = $this->option('date') ?: Carbon::yesterday()->toDateString();

        $this->info("Bắt đầu tổng hợp số liệu Reels cho ngày: {$date}...");

        try {
            $rollupService->rollupDate($date);
            $this->info("✓ Đã hoàn thành Rollup số liệu ngày {$date} thành công!");
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("✗ Lỗi khi tổng hợp: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

