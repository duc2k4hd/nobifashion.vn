<?php

/**
 * =========================================================================================
 * COMMAND: ÉP GỌN NỘI DUNG HTML BÀI VIẾT (HTML MINIFIER / COMPRESSOR)
 * =========================================================================================
 *
 * [MỤC ĐÍCH]:
 * - Ép gọn toàn bộ mã HTML trong nội dung bài viết (trường `content` bảng `posts`).
 * - Loại bỏ toàn bộ ký tự xuống dòng (\r\n, \r, \n) và tab (\t) gây lỗi vỡ hàng trong Excel/CSV.
 * - Ép sát các thẻ HTML lại với nhau (>  < thành ><) và rút gọn khoảng trắng dư thừa.
 * - Loại bỏ các thẻ ghi chú HTML rác (<!-- ... -->).
 * - Giảm dung lượng văn bản, chống tràn giới hạn 32.767 ký tự/ô của Microsoft Excel.
 * - Tăng tốc độ load trang và giảm tải băng thông hệ thống.
 * - Bảo toàn nguyên vẹn 100% thẻ ảnh, link, cấu trúc CSS/HTML và tiếng Việt có dấu UTF-8.
 *
 * [CÁC LỆNH CHẠY TERMINAL]:
 *
 * 1. Chạy thực tế (Cập nhật trực tiếp vào Cơ sở dữ liệu):
 *    php artisan posts:compress-html
 *    -> Giải thích: Quét toàn bộ bài viết trong DB (kể cả bài trong Thùng rác), ép gọn
 *       nội dung HTML và lưu cập nhật trực tiếp vào database theo từng batch 200 bài.
 *
 * 2. Chạy thử nghiệm (Kiểm tra dung lượng tiết kiệm, KHÔNG lưu vào Database):
 *    php artisan posts:compress-html --dry-run
 *    -> Giải thích: Quét thử nghiệm để đo lường xem có bao nhiêu bài viết cần nén,
 *       tính toán chính xác dung lượng (KB/MB) và số ký tự tiết kiệm được mà không ghi DB.
 *
 * =========================================================================================
 */

namespace App\Console\Commands;

use App\Models\Post;
use App\Services\HtmlCompressorService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CompressPostContentCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'posts:compress-html {--dry-run : Chạy thử nghiệm để kiểm tra dung lượng tiết kiệm mà không ghi vào database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ép gọn nội dung HTML bài viết liền nhau, loại bỏ khoảng trắng và xuống dòng thừa';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');

        $this->info($isDryRun ? '=== CHẾ ĐỘ CHẠY THỬ (DRY RUN - KHÔNG LƯU DB) ===' : '=== BẮT ĐẦU ÉP GỌN NỘI DUNG HTML BÀI VIẾT ===');

        $query = Post::withTrashed()->whereNotNull('content')->where('content', '!=', '');
        $total = $query->count();

        if ($total === 0) {
            $this->warn('Không có bài viết nào có nội dung để xử lý.');

            return 0;
        }

        $this->info("Tổng số bài viết cần phân tích: {$total}");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $processed = 0;
        $updated = 0;
        $totalCharsBefore = 0;
        $totalCharsAfter = 0;

        $query->select('id', 'content')->chunkById(200, function ($posts) use (&$processed, &$updated, &$totalCharsBefore, &$totalCharsAfter, $isDryRun, $bar) {
            $updates = [];

            foreach ($posts as $post) {
                $processed++;
                $bar->advance();

                $original = (string) $post->content;
                $originalLen = mb_strlen($original);
                $totalCharsBefore += $originalLen;

                $compressed = HtmlCompressorService::compress($original);
                $compressedLen = mb_strlen($compressed);
                $totalCharsAfter += $compressedLen;

                if ($original !== $compressed) {
                    $updated++;
                    if (! $isDryRun) {
                        $updates[] = [
                            'id' => $post->id,
                            'content' => $compressed,
                        ];
                    }
                }
            }

            if (! $isDryRun && ! empty($updates)) {
                // Cập nhật nhanh theo batch bằng query trực tiếp để tối ưu tốc độ nhanh nhất
                foreach ($updates as $up) {
                    DB::table('posts')->where('id', $up['id'])->update(['content' => $up['content']]);
                }
            }
        });

        $bar->finish();
        $this->newLine(2);

        $savedChars = $totalCharsBefore - $totalCharsAfter;
        $percent = $totalCharsBefore > 0 ? round(($savedChars / $totalCharsBefore) * 100, 2) : 0;
        $savedKb = round($savedChars / 1024, 2);

        $this->info('--- KẾT QUẢ XỬ LÝ ---');
        $this->line("Tổng bài viết đã duyệt: <info>{$processed}</info>");
        $this->line("Số bài viết có nội dung được ép gọn: <info>{$updated}</info>");
        $this->line('Tổng ký tự trước khi nén: <comment>'.number_format($totalCharsBefore).' ký tự</comment>');
        $this->line('Tổng ký tự sau khi nén: <comment>'.number_format($totalCharsAfter).' ký tự</comment>');
        $this->line('Dung lượng tiết kiệm được: <fg=green;options=bold>'.number_format($savedChars)." ký tự (~{$savedKb} KB, giảm {$percent}%)</>");

        if ($isDryRun) {
            $this->warn('Đây là bản chạy thử. Chạy lại lệnh không có cờ --dry-run để áp dụng thực tế: php artisan posts:compress-html');
        } else {
            $this->info('Đã cập nhật toàn bộ bài viết trong cơ sở dữ liệu thành công!');
        }

        return 0;
    }
}
