<?php

namespace App\Console\Commands;

use App\Services\Media\MediaAssignmentService;
use Illuminate\Console\Command;

class MediaFastDeleteCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'media:fast-delete
                            {--scope=unassigned_record : Phạm vi xóa (unassigned_record)}
                            {--batch=1000 : Số lượng ảnh xử lý mỗi batch}
                            {--limit=0 : Giới hạn tối đa số ảnh cần xóa (0 = xóa hết)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Xóa hàng loạt hàng trăm nghìn ảnh cực nhanh, siêu tiết kiệm RAM, xóa đồng bộ DB và file vật lý';

    /**
     * Execute the console command.
     */
    public function handle(MediaAssignmentService $assignment): int
    {
        $scope = (string) $this->option('scope');
        $batchSize = max(50, min((int) $this->option('batch'), 10000));
        $limit = max(0, (int) $this->option('limit'));

        $this->info("=== BẮT ĐẦU XÓA MEDIA SIÊU TỐC ===");
        $this->line("Phạm vi (Scope): <comment>{$scope}</comment>");
        $this->line("Kích thước Batch: <comment>{$batchSize}</comment> ảnh/batch");
        if ($limit > 0) {
            $this->line("Giới hạn tối đa: <comment>{$limit}</comment> ảnh");
        }

        $totalProcessed = 0;
        $totalPreserved = 0;
        $batchIndex = 1;
        $startTime = microtime(true);

        while (true) {
            $currentBatchLimit = $batchSize;
            if ($limit > 0 && ($totalProcessed + $batchSize) > $limit) {
                $currentBatchLimit = $limit - $totalProcessed;
            }

            if ($currentBatchLimit <= 0) {
                break;
            }

            $batchStartTime = microtime(true);
            $res = $assignment->deleteScopeChunk($scope, $currentBatchLimit);

            $processed = $res['processed'];
            $preserved = $res['preserved_files_count'];
            $remaining = $res['remaining'];
            $finished = $res['finished'];

            if ($processed === 0 && $finished) {
                $this->info("Không còn ảnh nào cần xóa trong phạm vi này.");
                break;
            }

            $totalProcessed += $processed;
            $totalPreserved += $preserved;
            $batchElapsed = round((microtime(true) - $batchStartTime) * 1000, 1);
            $memoryMb = round(memory_get_usage(true) / 1024 / 1024, 2);

            $this->line(sprintf(
                "[%s] Batch #%d: Đã xóa <info>%d</info> ảnh (bảo lưu %d file dùng chung) trong <comment>%sms</comment> | Còn lại: <info>%s</info> | RAM: <comment>%s MB</comment>",
                now()->format('H:i:s'),
                $batchIndex,
                $processed,
                $preserved,
                $batchElapsed,
                number_format($remaining),
                $memoryMb
            ));

            $batchIndex++;

            if ($finished || ($limit > 0 && $totalProcessed >= $limit)) {
                break;
            }
        }

        $totalElapsed = round(microtime(true) - $startTime, 2);
        $this->newLine();
        $this->info("=== HOÀN TẤT XÓA MEDIA ===");
        $this->line("Tổng số ảnh đã xóa trong DB: <info>" . number_format($totalProcessed) . "</info>");
        $this->line("Số file vật lý được giữ lại do dùng chung: <comment>" . number_format($totalPreserved) . "</comment>");
        $this->line("Thời gian thực hiện: <comment>{$totalElapsed}s</comment>");

        return Command::SUCCESS;
    }
}
