<?php

namespace App\Console\Commands;

use App\Services\CoolmateCrawlerService;
use Illuminate\Console\Command;

class CrawlCoolmateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coolmate:crawl
                            {--file=storage/app/tmp/coolmate/urls.txt : Đường dẫn file chứa danh sách URL}
                            {--chunk=50 : Số lượng URL xử lý mỗi đợt}
                            {--limit=0 : Giới hạn tối đa số URL cần cào (0 = không giới hạn)}
                            {--recrawl : Cào lại cả những link đã có trong crawled_urls.json}
                            {--download-main : Tải cả ảnh đại diện chính (main image) về máy}
                            {--csv= : Tên file CSV xuất ra (mặc định 1 file duy nhất cho cả quá trình)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crawl hàng nghìn bài viết Coolmate siêu tốc từ file URL qua CLI';

    /**
     * Execute the console command.
     */
    public function handle(CoolmateCrawlerService $crawlerService): int
    {
        $filePath = (string) $this->option('file');
        $chunkSize = max(1, min((int) $this->option('chunk'), 500));
        $limit = max(0, (int) $this->option('limit'));
        $recrawl = (bool) $this->option('recrawl');
        $downloadMain = (bool) $this->option('download-main');

        $fullPath = base_path($filePath);
        if (! is_file($fullPath)) {
            $this->error("Không tìm thấy file danh sách URL tại: {$filePath}");
            return Command::FAILURE;
        }

        $lines = file($fullPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        $rawUrls = array_values(array_unique(array_filter(array_map('trim', $lines))));

        if (empty($rawUrls)) {
            $this->warn("File {$filePath} không có URL nào hợp lệ.");
            return Command::SUCCESS;
        }

        $allFileUrls = $rawUrls;
        if ($limit > 0 && count($rawUrls) > $limit) {
            $rawUrls = array_slice($rawUrls, 0, $limit);
        }

        $totalUrls = count($rawUrls);
        [$targetCsv, $isNewCsv] = $this->resolveTargetCsv($filePath, $allFileUrls, (string) $this->option('csv'));

        $this->info("================================================================================");
        $this->info("                   🕷️ BẮT ĐẦU CRAWL COOLMATE.ME (CLI)                         ");
        $this->info("================================================================================");
        $this->line("File nguồn:          <comment>{$filePath}</comment>");
        $this->line("Tổng số URL nạp:     <info>" . number_format($totalUrls) . "</info>");
        $this->line("Kích thước mỗi đợt:  <comment>{$chunkSize}</comment> URL/chunk");
        $this->line("File CSV đích:       <info>{$targetCsv}</info> " . ($isNewCsv ? '<comment>(Tạo file mới)</comment>' : '<info>(Tiếp tục ghi nối tiếp từ phiên trước)</info>'));
        $this->line("Tải ảnh đại diện:    " . ($downloadMain ? '<info>BẬT</info>' : '<comment>TẮT (mặc định - cào siêu tốc, giữ link ảnh gốc)</comment>'));
        $this->line("Cào lại bài cũ:      " . ($recrawl ? '<info>BẬT</info>' : '<comment>TẮT (tự động bỏ qua link đã cào thành công)</comment>'));
        $this->info("================================================================================");

        $chunks = array_chunk($rawUrls, $chunkSize);
        $totalChunks = count($chunks);

        $totalSuccess = 0;
        $totalFailed = 0;
        $totalSkipped = 0;
        $totalImages = 0;
        $startTime = microtime(true);

        foreach ($chunks as $index => $chunkUrls) {
            $chunkNumber = $index + 1;
            $chunkStartTime = microtime(true);

            $this->line(sprintf(
                "[%s] ⏳ Đang xử lý mẻ %d/%d (%d URL)...",
                now()->format('H:i:s'),
                $chunkNumber,
                $totalChunks,
                count($chunkUrls)
            ));

            try {
                $results = $crawlerService->crawlPostsToCsv(
                    $chunkUrls,
                    $recrawl,
                    $downloadMain,
                    $targetCsv
                );

                $successCount = (int) ($results['success'] ?? 0);
                $failedCount = (int) ($results['failed'] ?? 0);
                $skippedCount = (int) ($results['skipped'] ?? 0);
                $imageCount = (int) ($results['image_downloaded_count'] ?? 0);

                $totalSuccess += $successCount;
                $totalFailed += $failedCount;
                $totalSkipped += $skippedCount;
                $totalImages += $imageCount;

                $elapsed = round(microtime(true) - $chunkStartTime, 1);
                $ramMb = round(memory_get_usage(true) / 1024 / 1024, 1);
                $processedSoFar = min($totalUrls, $chunkNumber * $chunkSize);
                $percent = round(($processedSoFar / $totalUrls) * 100);

                $this->line(sprintf(
                    "[%s] ✅ Mẻ %d/%d hoàn thành trong <comment>%ss</comment> | Thành công: <info>%d</info> | Bỏ qua: <comment>%d</comment> | Thất bại: <error>%d</error> | Ảnh: <info>%d</info> | Tiến độ: <info>%s/%s (%d%%)</info> | RAM: <comment>%sMB</comment>",
                    now()->format('H:i:s'),
                    $chunkNumber,
                    $totalChunks,
                    $elapsed,
                    $successCount,
                    $skippedCount,
                    $failedCount,
                    $imageCount,
                    number_format($processedSoFar),
                    number_format($totalUrls),
                    $percent,
                    $ramMb
                ));

                if (! empty($results['errors'])) {
                    foreach (array_slice($results['errors'], 0, 3) as $err) {
                        $this->line("   ⚠️ <error>" . $err . "</error>");
                    }
                }
            } catch (\Throwable $e) {
                $this->error("Lỗi mẻ {$chunkNumber}: " . $e->getMessage());
                report($e);
            }
        }

        $totalElapsed = round(microtime(true) - $startTime, 1);
        $this->newLine();
        $this->info("================================================================================");
        $this->info("                   🎉 HOÀN THÀNH TOÀN BỘ QUÁ TRÌNH CRAWL                        ");
        $this->info("================================================================================");
        $this->line("Tổng số bài cào thành công: <info>" . number_format($totalSuccess) . "</info>");
        $this->line("Tổng số URL bỏ qua (đã cào):<comment>" . number_format($totalSkipped) . "</comment>");
        $this->line("Tổng số URL lỗi:            " . ($totalFailed > 0 ? "<error>" . number_format($totalFailed) . "</error>" : "0"));
        $this->line("Tổng số ảnh đã tải:         <info>" . number_format($totalImages) . "</info>");
        $this->line("Tổng thời gian thực hiện:   <comment>{$totalElapsed} giây (" . round($totalElapsed / 60, 2) . " phút)</comment>");
        $this->line("File CSV kết quả duy nhất:  <info>{$targetCsv}</info>");
        $this->line("Thư mục lưu trữ:            <comment>storage/app/tmp/coolmate/{$targetCsv}</comment>");

        if ($limit === 0 && $this->isAllUrlsCrawled($allFileUrls)) {
            $this->clearCrawlSession();
            $this->info("✨ Đã cào hoàn tất 100% danh sách URL! Lần sau chạy sẽ tự động tạo file CSV mới.");
        }
        $this->info("================================================================================");

        return Command::SUCCESS;
    }

    /**
     * Xác định file CSV đích: tiếp tục file cũ nếu chưa cào hết, tạo mới nếu đã cào xong.
     *
     * @return array{0: string, 1: bool} [tên file CSV, có phải file mới hay không]
     */
    private function resolveTargetCsv(string $filePath, array $rawUrls, string $userSpecifiedCsv): array
    {
        if ($userSpecifiedCsv !== '') {
            $csv = basename($userSpecifiedCsv);
            if (! str_ends_with(strtolower($csv), '.csv')) {
                $csv .= '.csv';
            }

            return [$csv, false];
        }

        $sessionFile = storage_path('app/tmp/coolmate/crawl_session.json');
        $crawledPath = storage_path('app/tmp/coolmate/crawled_urls.json');
        $crawledUrls = [];

        if (is_file($crawledPath)) {
            $crawledData = json_decode((string) file_get_contents($crawledPath), true);
            $crawledUrls = (array) ($crawledData['urls'] ?? []);
        }

        $hasPendingUrls = false;
        foreach ($rawUrls as $url) {
            $normalized = preg_replace('/^http:\/\/coolmate\.me/', 'https://www.coolmate.me', $url);
            if (! isset($crawledUrls[$url]) && ! isset($crawledUrls[$normalized])) {
                $hasPendingUrls = true;
                break;
            }
        }

        if ($hasPendingUrls) {
            if (is_file($sessionFile)) {
                $session = json_decode((string) file_get_contents($sessionFile), true);
                $existingCsv = (string) ($session['target_csv'] ?? '');
                if ($existingCsv !== '' && is_file(storage_path("app/tmp/coolmate/{$existingCsv}"))) {
                    return [$existingCsv, false];
                }
            }

            $csvFiles = glob(storage_path('app/tmp/coolmate/coolmate_posts_*.csv')) ?: [];
            if (! empty($csvFiles)) {
                usort($csvFiles, fn ($a, $b) => filemtime($b) <=> filemtime($a));
                $latestCsv = basename($csvFiles[0]);
                $this->saveCrawlSession($filePath, $latestCsv);

                return [$latestCsv, false];
            }
        }

        $newCsv = 'coolmate_posts_' . now()->format('Y-m-d_H-i-s') . '.csv';
        $this->saveCrawlSession($filePath, $newCsv);

        return [$newCsv, true];
    }

    private function isAllUrlsCrawled(array $rawUrls): bool
    {
        $crawledPath = storage_path('app/tmp/coolmate/crawled_urls.json');
        if (! is_file($crawledPath)) {
            return false;
        }

        $crawledData = json_decode((string) file_get_contents($crawledPath), true);
        $crawledUrls = (array) ($crawledData['urls'] ?? []);

        foreach ($rawUrls as $url) {
            $normalized = preg_replace('/^http:\/\/coolmate\.me/', 'https://www.coolmate.me', $url);
            if (! isset($crawledUrls[$url]) && ! isset($crawledUrls[$normalized])) {
                return false;
            }
        }

        return true;
    }

    private function saveCrawlSession(string $filePath, string $targetCsv): void
    {
        $sessionFile = storage_path('app/tmp/coolmate/crawl_session.json');
        $data = [
            'source_file' => $filePath,
            'target_csv' => $targetCsv,
            'updated_at' => now()->toIso8601String(),
        ];
        @file_put_contents($sessionFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    private function clearCrawlSession(): void
    {
        $sessionFile = storage_path('app/tmp/coolmate/crawl_session.json');
        if (is_file($sessionFile)) {
            @unlink($sessionFile);
        }
    }
}
