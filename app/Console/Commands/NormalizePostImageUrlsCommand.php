<?php

/**
 * =========================================================================================
 * COMMAND: CHUẨN HÓA URL ẢNH BÀI VIẾT (NORMALIZE POST IMAGE URLS)
 * =========================================================================================
 *
 * [MỤC ĐÍCH]:
 * - Quét toàn bộ trường `content` trong bảng `posts` để chuẩn hóa các URL ảnh về cấu trúc nội bộ:
 *   /clients/assets/img/posts/{filename}.webp
 * - Chỉ xử lý đúng 3 dạng URL theo yêu cầu:
 *   1. storage/app/tmp/coolmate/extra/...
 *   2. https://www.coolmate.me/blog/wp-content/uploads/...
 *   3. https://n7media.coolmate.me/...
 * - Trích xuất tên file gốc (filename), bỏ phần mở rộng cũ (.jpg, .png, .jpeg...) và đổi thành .webp.
 * - Giữ nguyên vẹn 100% cấu trúc HTML/văn bản xung quanh, hỗ trợ bài viết có nhiều ảnh.
 * - Bỏ qua các URL đã chuẩn hóa dạng /clients/assets/img/posts/...
 * - Tuyệt đối không can thiệp hoặc sửa lỗi các đoạn chứa chuỗi $1.webp cũ.
 * - Xử lý theo chunk/batch tối ưu RAM và tốc độ thực thi nhanh nhất.
 *
 * [CÁC LỆNH CHẠY TERMINAL]:
 *
 * 1. Chạy thử nghiệm kiểm tra trước (KHÔNG ghi vào Database):
 *    php artisan posts:normalize-image-urls --dry-run
 *
 * 2. Chạy cập nhật thực tế vào Cơ sở dữ liệu:
 *    php artisan posts:normalize-image-urls
 *
 * 3. Tùy chỉnh batch size (mặc định 200):
 *    php artisan posts:normalize-image-urls --chunk=500
 *
 * =========================================================================================
 */

namespace App\Console\Commands;

use App\Models\Post;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class NormalizePostImageUrlsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'posts:normalize-image-urls
                            {--dry-run : Chạy thử nghiệm để kiểm tra kết quả mà không cập nhật database}
                            {--chunk=200 : Số lượng bài viết mỗi đợt quét (batch size)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Quét toàn bộ posts.content và chuẩn hóa URL ảnh về /clients/assets/img/posts/{filename}.webp';

    /**
     * Pattern Regex nhận diện 3 dạng URL theo yêu cầu:
     * 1. (https?://.../)?/?storage/app/tmp/coolmate/extra/...
     * 2. https?://(www.)?coolmate.me/blog/wp-content/uploads/...
     * 3. https?://n7media.coolmate.me/...
     */
    protected const URL_REGEX_PATTERN = '#(?:https?://[^\s"\'<>()]+/)?/?storage/app/tmp/coolmate/extra/[^\s"\'<>()]+|https?://(?:www\.)?coolmate\.me/blog/wp-content/uploads/[^\s"\'<>()]+|https?://n7media\.coolmate\.me/[^\s"\'<>()]+#i';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $chunkSize = max(1, (int) $this->option('chunk'));

        $this->newLine();
        $this->info($isDryRun
            ? '=========================================================' . PHP_EOL . '  CHẾ ĐỘ CHẠY THỬ (DRY-RUN) - KHÔNG THAY ĐỔI DATABASE' . PHP_EOL . '========================================================='
            : '=========================================================' . PHP_EOL . '  BẮT ĐẦU CHUẨN HÓA URL ẢNH TRONG BÀI VIẾT (DATABASE UPDATE)' . PHP_EOL . '========================================================='
        );

        $startTime = microtime(true);

        // Khởi tạo query lấy tất cả bài viết có nội dung (kể cả trong thùng rác để đảm bảo dữ liệu đồng nhất)
        $query = Post::withTrashed()
            ->whereNotNull('content')
            ->where('content', '!=', '');

        $totalPosts = $query->count();

        if ($totalPosts === 0) {
            $this->warn('Không tìm thấy bài viết nào có nội dung để quét.');
            return 0;
        }

        $this->line("• Tổng số bài viết cần duyệt: <comment>{$totalPosts}</comment>");
        $this->line("• Kích thước mỗi batch: <comment>{$chunkSize}</comment> bài");
        $this->newLine();

        $progressBar = $this->output->createProgressBar($totalPosts);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% -- %message%');
        $progressBar->setMessage('Đang xử lý...');
        $progressBar->start();

        $scannedPosts = 0;
        $updatedPosts = 0;
        $totalReplacedUrls = 0;
        $sampleReplacements = [];

        // Xử lý theo chunkById để tránh tràn RAM và tối ưu truy vấn
        $query->select('id', 'content')->chunkById($chunkSize, function ($posts) use (
            &$scannedPosts,
            &$updatedPosts,
            &$totalReplacedUrls,
            &$sampleReplacements,
            $isDryRun,
            $progressBar
        ) {
            $batchUpdates = [];

            foreach ($posts as $post) {
                $scannedPosts++;
                $progressBar->advance();

                $originalContent = (string) $post->content;

                // Tối ưu tốc độ: Nếu nội dung không chứa từ khóa 'coolmate', bỏ qua ngay lập tức
                if (! str_contains($originalContent, 'coolmate')) {
                    continue;
                }

                $postReplacedUrls = 0;
                $newContent = $this->replaceImageUrls($originalContent, $postReplacedUrls, $sampleReplacements);

                if ($postReplacedUrls > 0 && $newContent !== $originalContent) {
                    $updatedPosts++;
                    $totalReplacedUrls += $postReplacedUrls;

                    if (! $isDryRun) {
                        $batchUpdates[] = [
                            'id' => $post->id,
                            'content' => $newContent,
                        ];
                    }
                }
            }

            // Cập nhật database trực tiếp theo ID để đạt tốc độ cao nhất
            if (! $isDryRun && ! empty($batchUpdates)) {
                foreach ($batchUpdates as $item) {
                    DB::table('posts')
                        ->where('id', $item['id'])
                        ->update(['content' => $item['content']]);
                }
            }
        });

        $progressBar->setMessage('Hoàn tất!');
        $progressBar->finish();
        $this->newLine(2);

        $executionTime = round(microtime(true) - $startTime, 2);

        // Hiển thị bảng tổng kết
        $this->table(
            ['Chỉ số thống kê', 'Giá trị'],
            [
                ['Chế độ chạy', $isDryRun ? '<comment>Dry-run (Xem trước)</comment>' : '<info>Cập nhật thực tế</info>'],
                ['Số bài viết đã quét', number_format($scannedPosts)],
                ['Số bài viết có URL ảnh cần chuẩn hóa', number_format($updatedPosts)],
                ['Tổng số URL ảnh đã thay thế', number_format($totalReplacedUrls)],
                ['Thời gian thực thi', "{$executionTime} giây"],
            ]
        );

        // Hiển thị một số mẫu URL đã thay thế nếu có
        if (! empty($sampleReplacements)) {
            $this->newLine();
            $this->info('🔍 MẪU URL ĐÃ ĐƯỢC CHUẨN HÓA (Tối đa 5 mẫu):');
            $sampleTable = [];
            foreach (array_slice($sampleReplacements, 0, 5) as $sample) {
                $sampleTable[] = [
                    'OLD' => $sample['old'],
                    'NEW' => $sample['new'],
                ];
            }
            $this->table(['URL gốc', 'URL chuẩn hóa mới'], $sampleTable);
        }

        $this->newLine();
        if ($isDryRun) {
            $this->warn('ℹ LƯU Ý: Đây là kết quả chạy thử nghiệm (--dry-run).');
            $this->line('  Để thực thi cập nhật vào cơ sở dữ liệu, hãy chạy lệnh:');
            $this->line('  <info>php artisan posts:normalize-image-urls</info>');
        } else {
            $this->info('✓ Hoàn tất! Toàn bộ nội dung bài viết đã được cập nhật thành công vào cơ sở dữ liệu.');
        }
        $this->newLine();

        return 0;
    }

    /**
     * Thay thế các URL ảnh trong nội dung HTML.
     *
     * @param  string  $content
     * @param  int  &$replacedCount
     * @param  array  &$samples
     * @return string
     */
    protected function replaceImageUrls(string $content, int &$replacedCount, array &$samples): string
    {
        $replacedCount = 0;

        return preg_replace_callback(self::URL_REGEX_PATTERN, function ($matches) use (&$replacedCount, &$samples) {
            $url = $matches[0];

            // 1. Tuyệt đối không xử lý hay sửa các đoạn lỗi $1 hoặc $1.webp cũ
            if (str_contains($url, '$1')) {
                return $url;
            }

            // 2. Bỏ qua các URL đã ở dạng chuẩn /clients/assets/img/posts/...
            if (str_contains($url, '/clients/assets/img/posts/')) {
                return $url;
            }

            // 3. Tách đường dẫn path để trích xuất filename
            $path = parse_url($url, PHP_URL_PATH);
            if (! $path) {
                return $url;
            }

            // Lấy tên file gốc không kèm extension
            $basename = basename(urldecode((string) $path));
            $filename = pathinfo($basename, PATHINFO_FILENAME);

            // Bỏ qua nếu filename rỗng hoặc là chuỗi lỗi '$1'
            if ($filename === '' || $filename === '$1') {
                return $url;
            }

            $newUrl = '/clients/assets/img/posts/' . $filename . '.webp';

            $replacedCount++;

            // Thu thập mẫu để log cho người dùng kiểm tra
            if (count($samples) < 10) {
                $samples[] = [
                    'old' => $url,
                    'new' => $newUrl,
                ];
            }

            return $newUrl;
        }, $content);
    }
}
