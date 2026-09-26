<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Services\Media\PostMediaSyncService;
use Illuminate\Console\Command;

class SyncPostImagesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'media:sync-posts
                            {--chunk=100 : Số lượng bài viết mỗi batch}
                            {--cleanup-ghosts : Dọn dẹp các bản ghi ảnh rác thiếu file vật lý}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Đồng bộ và gán ảnh từ nội dung bài viết vào Media Library (chỉ gán ảnh thật có sẵn, không tạo mới)';

    /**
     * Execute the console command.
     */
    public function handle(PostMediaSyncService $service): int
    {
        if ($this->option('cleanup-ghosts')) {
            $this->info('🧹 Đang dọn dẹp các bản ghi ảnh thiếu file vật lý...');
            $totalCleaned = 0;
            while (true) {
                $cleanRes = $service->cleanupGhostImages(5000);
                $totalCleaned += $cleanRes['deleted'];
                $this->line("  ✓ Đã dọn dẹp {$totalCleaned} bản ghi...");
                if (empty($cleanRes['has_more']) || $cleanRes['deleted'] === 0) {
                    break;
                }
            }
            $this->info("✨ Hoàn tất dọn dẹp: Đã xóa {$totalCleaned} bản ghi ảnh rác!");
            return 0;
        }

        $this->info('🚀 Bắt đầu quá trình đồng bộ và gán ảnh bài viết...');

        $overview = $service->getSyncOverview();
        $this->table(
            ['Chỉ số', 'Giá trị'],
            [
                ['Tổng số bài viết', number_format($overview['total_posts'])],
                ['Bài viết có nội dung / thumbnail', number_format($overview['posts_with_content'])],
                ['Tổng số ảnh trong media', number_format($overview['total_images'])],
                ['Số file vật lý thư mục posts', number_format($overview['physical_files_count'])],
                ['Ảnh bài viết đã gán đối tượng', number_format($overview['assigned_post_images'])],
                ['Ảnh bài viết chưa gán', number_format($overview['unassigned_post_images'])],
            ]
        );

        $chunkSize = max(10, (int) $this->option('chunk'));
        $totalPosts = $overview['total_posts'];

        $this->info("🔍 Quét nội dung bài viết và đối soát gán ảnh có sẵn trong Media (batch size: {$chunkSize})...");

        $bar = $this->output->createProgressBar($totalPosts);
        $bar->start();

        $offset = 0;
        $totalDetected = 0;
        $totalAssigned = 0;

        while (true) {
            $chunkRes = $service->syncPostsChunk($offset, $chunkSize, [
                'update_meta' => true,
            ]);

            $processed = $chunkRes['processed_posts'] ?? 0;
            if ($processed === 0) {
                break;
            }

            $totalDetected += $chunkRes['images_detected'] ?? 0;
            $totalAssigned += $chunkRes['images_assigned'] ?? 0;

            $bar->advance($processed);

            if (!empty($chunkRes['finished'])) {
                break;
            }

            $offset = $chunkRes['next_offset'] ?? ($offset + $chunkSize);
        }

        $bar->finish();
        $this->newLine(2);

        $this->info('🎉 HOÀN TẤT ĐỒNG BỘ ẢNH BÀI VIẾT!');
        $this->table(
            ['Kết quả', 'Số lượng'],
            [
                ['Tổng số ảnh phát hiện trong bài viết', number_format($totalDetected)],
                ['Ảnh có sẵn trong Media đã gán vào bài', number_format($totalAssigned)],
            ]
        );

        return 0;
    }
}
