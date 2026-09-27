<?php

/**
 * ====================================================================================
 * COMMAND: posts:add-lazy-loading (Bí danh: post:add-lazy-loading)
 * ====================================================================================
 * TỰ ĐỘNG THÊM LOADING="LAZY" VÀO THẺ <IMG> BÀI VIẾT (CHUẨN TỐC ĐỘ CAO - AN TOÀN DỮ LIỆU)
 *
 * 1. NGUYÊN LÝ HOẠT ĐỘNG:
 *    - Quét nội dung cột `content` trong bảng `posts`, tìm các thẻ <img>.
 *    - Nếu thẻ <img> chưa có thuộc tính loading="..." thì tự động bổ sung loading="lazy".
 *    - Nếu thẻ đã có sẵn loading (lazy/eager/...) thì giữ nguyên, không sửa, không tạo thêm.
 *    - Tuyệt đối không nhận diện nhầm các thuộc tính khác như data-loading, class="loading-...".
 *    - Bảo toàn 100% tiếng Việt UTF-8, ký tự đặc biệt, entities và cấu trúc HTML xung quanh.
 *    - Hiệu năng tối đa: Dùng chunkById, lọc WHERE content LIKE %<img%, direct DB update, giải phóng RAM liên tục.
 *
 * 2. TỔNG HỢP CÁC LỆNH CHẠY THƯỜNG DÙNG:
 *
 *    ① Chạy thử nghiệm mô phỏng (Dry-Run - Không ghi DB, in mẫu Preview):
 *       php artisan posts:add-lazy-loading --dry-run --preview
 *
 *    ② Chạy thử nghiệm trên 1 bài viết cụ thể theo ID:
 *       php artisan posts:add-lazy-loading --id=40 --dry-run --preview
 *
 *    ③ Chạy thực thi cập nhật thật vào Database (Có bước hỏi xác nhận an toàn):
 *       php artisan posts:add-lazy-loading
 *
 *    ④ Chạy thực thi bỏ qua bước hỏi xác nhận (Dùng cho Script / Cron / Deploy):
 *       php artisan posts:add-lazy-loading --force
 *
 *    ⑤ Chạy với Batch size lớn hơn (Ví dụ 500 bài/batch cho server khỏe):
 *       php artisan posts:add-lazy-loading --chunk=500 --force
 *
 *    ⑥ Cập nhật nội dung đồng thời cập nhật lại cột updated_at:
 *       php artisan posts:add-lazy-loading --touch-timestamps --force
 *
 *    ⑦ Quét toàn bộ bảng bài viết không dùng bộ lọc %<img%:
 *       php artisan posts:add-lazy-loading --all --force
 * ====================================================================================
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AddLazyLoadingToPostImagesCommand extends Command
{
    /**
     * Tên và cú pháp của lệnh artisan
     *
     * @var string
     */
    protected $signature = 'posts:add-lazy-loading
                            {--chunk=200 : Số lượng bài viết xử lý mỗi batch}
                            {--id= : Chỉ xử lý duy nhất 1 bài viết theo ID để kiểm tra}
                            {--all : Quét toàn bộ bảng posts không dùng bộ lọc WHERE content LIKE %<img%}
                            {--dimensions : Tự động đo kích thước thật và bổ sung width/height nếu ảnh chưa có (Mặc định bật)}
                            {--no-dimensions : Không tự động bổ sung width/height}
                            {--touch-timestamps : Tự động cập nhật cột updated_at khi sửa nội dung (mặc định giữ nguyên)}
                            {--dry-run : Chạy thử nghiệm, kiểm tra thống kê và preview mà không ghi vào DB}
                            {--preview : In mẫu các thẻ <img> trước và sau khi thêm loading="lazy" và width/height}
                            {--force : Bỏ qua bước xác nhận khi chạy thật}';

    /**
     * Các bí danh (aliases) để gọi lệnh
     *
     * @var array
     */
    protected $aliases = ['post:add-lazy-loading'];

    /**
     * Mô tả lệnh
     *
     * @var string
     */
    protected $description = 'Quét toàn bộ cột content trong bảng posts và tự động thêm loading="lazy" vào các thẻ <img> chưa có';

    /**
     * Regex khớp chính xác thẻ <img> chuẩn HTML5, an toàn tuyệt đối với dấu > nằm bên trong dấu ngoặc kép/đơn của attribute
     */
    protected const IMG_PATTERN = '/<img\b(?:[^>"\'\s]|\s(?![>])|"[^"]*"|\'[^\']*\')*>/is';

    /**
     * Regex kiểm tra xem thẻ <img> đã có thuộc tính loading hay chưa
     * Chỉ khớp khi loading là một attribute riêng biệt (phía trước là khoảng trắng)
     * Tránh khớp nhầm các attribute như: data-loading="...", class="loading-icon", ...
     */
    protected const HAS_LOADING_PATTERN = '/\sloading(?:\s*=|[\s\/>])/i';

    /**
     * Thực thi lệnh Console
     */
    public function handle(): int
    {
        $startTime = microtime(true);

        $chunkSize = max(1, (int) $this->option('chunk'));
        $postId = $this->option('id') ? (int) $this->option('id') : null;
        $scanAll = (bool) $this->option('all');
        $touchTimestamps = (bool) $this->option('touch-timestamps');
        $isDryRun = (bool) $this->option('dry-run');
        $isPreview = (bool) $this->option('preview');
        $isForce = (bool) $this->option('force');
        $addDimensions = !$this->option('no-dimensions');

        $this->line("");
        $this->info("==========================================================================");
        $this->info("🚀 CÔNG CỤ TỰ ĐỘNG THÊM LOADING=\"LAZY\" VÀO THẺ <IMG> BÀI VIẾT (POSTS)");
        $this->info("==========================================================================");

        if ($isDryRun) {
            $this->warn("⚠️  CHẾ ĐỘ DRY-RUN ĐANG BẬT: Dữ liệu database sẽ KHÔNG bị thay đổi.");
        }

        // 1. Xây dựng truy vấn tối ưu cao (chỉ select các cột thiết yếu: id, title, content)
        $query = DB::table('posts')
            ->select(['id', 'title', 'content'])
            ->whereNotNull('content')
            ->where('content', '!=', '');

        if ($postId) {
            $query->where('id', $postId);
            $this->line("🎯 Mục tiêu: Chỉ xử lý bài viết có ID = {$postId}");
        } elseif (!$scanAll) {
            // Tối ưu hóa cực đại: Lọc nhanh từ DB các bài viết có chứa thẻ img, tránh scan vô ích các bài viết thuần chữ
            $query->where(function ($q) {
                $q->where('content', 'LIKE', '%<img%')
                  ->orWhere('content', 'LIKE', '%<IMG%');
            });
            $this->line("🔍 Bộ lọc: Chỉ quét các bài viết có chứa thẻ <img (dùng --all nếu muốn quét toàn bộ)");
        } else {
            $this->line("🔍 Chế độ: Quét toàn bộ bài viết trong bảng posts (--all)");
        }

        $totalCount = $query->count();
        $this->line("📊 Tổng số bài viết tìm thấy: <fg=green;options=bold>" . number_format($totalCount) . "</>");

        if ($totalCount === 0) {
            $this->info("✨ Không có bài viết nào cần xử lý. Kết thúc!");
            return 0;
        }

        // Xác nhận nếu chạy thật mà không có cờ --force
        if (!$isDryRun && !$isForce) {
            $confirmed = $this->confirm("Bạn có chắc chắn muốn quét và cập nhật loading=\"lazy\" cho {$totalCount} bài viết?", true);
            if (!$confirmed) {
                $this->warn("⛔ Đã hủy thao tác.");
                return 0;
            }
        }

        $this->line("");
        $this->info("⏳ Đang tiến hành xử lý...");

        // Khởi tạo Progress Bar
        $bar = $this->output->createProgressBar($totalCount);
        $bar->setFormat(" %current%/%max% [%bar%] %percent:3s%% | Sửa: %modified_posts% bài (%modified_images% ảnh) | RAM: %memory%");
        $bar->setMessage('0', 'modified_posts');
        $bar->setMessage('0', 'modified_images');
        $bar->setMessage($this->formatMemory(memory_get_usage(true)), 'memory');
        $bar->start();

        $processedPosts = 0;
        $updatedPosts = 0;
        $skippedPosts = 0;
        $totalImagesScanned = 0;
        $totalImagesModified = 0;
        $totalImagesAlreadyLazy = 0;
        $previewList = [];
        $errorCount = 0;

        // Xử lý từng đợt bằng chunkById để đảm bảo hiệu năng và không tràn RAM
        $query->chunkById($chunkSize, function ($posts) use (
            &$processedPosts,
            &$updatedPosts,
            &$skippedPosts,
            &$totalImagesScanned,
            &$totalImagesModified,
            &$totalImagesAlreadyLazy,
            &$previewList,
            &$errorCount,
            $isDryRun,
            $isPreview,
            $touchTimestamps,
            $addDimensions,
            $bar
        ) {
            foreach ($posts as $post) {
                try {
                    $processedPosts++;
                    $content = $post->content ?? '';

                    if (trim($content) === '') {
                        $skippedPosts++;
                        $bar->advance();
                        continue;
                    }

                    $res = $this->processContent($content, $addDimensions);

                    $totalImagesScanned += $res['total_images'];
                    $totalImagesModified += $res['modified_images'];
                    $totalImagesAlreadyLazy += $res['skipped_images'];

                    if ($res['modified_images'] > 0) {
                        $updatedPosts++;

                        if (!$isDryRun) {
                            $updateData = ['content' => $res['new_content']];
                            if ($touchTimestamps) {
                                $updateData['updated_at'] = now();
                            }
                            DB::table('posts')->where('id', $post->id)->update($updateData);
                        }

                        if ($isPreview && count($previewList) < 5) {
                            $previewList[] = [
                                'id' => $post->id,
                                'title' => $post->title ?? 'N/A',
                                'modified_count' => $res['modified_images'],
                                'samples' => $res['preview_changes'],
                            ];
                        }
                    } else {
                        $skippedPosts++;
                    }
                } catch (\Throwable $e) {
                    $errorCount++;
                    $this->warn("\n⚠️ Lỗi khi xử lý bài viết #{$post->id}: " . $e->getMessage());
                }

                $bar->setMessage((string)$updatedPosts, 'modified_posts');
                $bar->setMessage((string)$totalImagesModified, 'modified_images');
                $bar->setMessage($this->formatMemory(memory_get_usage(true)), 'memory');
                $bar->advance();
            }

            // Giải phóng bộ nhớ RAM sau mỗi chunk
            unset($posts);
            gc_collect_cycles();
        });

        $bar->finish();
        $this->line("");
        $this->line("");

        // In các đoạn Preview nếu người dùng yêu cầu hoặc chạy với --preview
        if (!empty($previewList)) {
            $this->info("==========================================================================");
            $this->info("👁️  XEM TRƯỚC (PREVIEW) CÁC THAY ĐỔI:");
            $this->info("==========================================================================");
            foreach ($previewList as $item) {
                $this->line("<fg=yellow;options=bold>Bài viết #{$item['id']}:</> {$item['title']} (Đã thêm lazy cho {$item['modified_count']} ảnh)");
                foreach ($item['samples'] as $idx => $sample) {
                    $num = $idx + 1;
                    $this->line("  [Ảnh #{$num}]");
                    $this->line("  <fg=red>- Trước :</> " . trim($sample['original']));
                    $this->line("  <fg=green>+ Sau   :</> " . trim($sample['modified']));
                }
                $this->line("--------------------------------------------------------------------------");
            }
        }

        $duration = round(microtime(true) - $startTime, 2);
        $peakMemory = $this->formatMemory(memory_get_peak_usage(true));

        // In bảng tổng kết
        $this->displaySummary([
            'Trạng thái' => $isDryRun ? 'DRY-RUN (Mô phỏng, chưa lưu DB)' : 'THÀNH CÔNG (Đã lưu vào Database)',
            'Tổng bài viết quét' => number_format($processedPosts),
            'Số bài viết được cập nhật' => number_format($updatedPosts),
            'Số bài giữ nguyên (đã có/không có ảnh)' => number_format($skippedPosts),
            'Tổng số thẻ <img> quét thấy' => number_format($totalImagesScanned),
            'Số ảnh đã thêm loading="lazy"' => number_format($totalImagesModified),
            'Số ảnh đã có sẵn loading (bỏ qua)' => number_format($totalImagesAlreadyLazy),
            'Số lỗi ngoại lệ' => number_format($errorCount),
            'Thời gian thực thi' => "{$duration} giây",
            'Bộ nhớ RAM đỉnh điểm' => $peakMemory,
        ]);

        return $errorCount === 0 ? 0 : 1;
    }

    /**
     * Xử lý nội dung của một bài viết: thêm loading="lazy" và width/height vào các thẻ <img> chưa có
     *
     * @param string $content
     * @param bool $addDimensions
     * @return array
     */
    public function processContent(string $content, bool $addDimensions = true): array
    {
        $totalImages = 0;
        $modifiedImages = 0;
        $skippedImages = 0;
        $previewChanges = [];

        $newContent = preg_replace_callback(self::IMG_PATTERN, function ($matches) use (
            &$totalImages,
            &$modifiedImages,
            &$skippedImages,
            &$previewChanges,
            $addDimensions
        ) {
            $tag = $matches[0];
            $totalImages++;

            $hasLoading = (bool) preg_match(self::HAS_LOADING_PATTERN, $tag);
            $hasWidth = (bool) preg_match('/\swidth\s*=/i', $tag);
            $hasHeight = (bool) preg_match('/\sheight\s*=/i', $tag);

            $needsLoading = !$hasLoading;
            $needsDimensions = $addDimensions && (!$hasWidth || !$hasHeight);

            // Tìm kích thước thật trên disk nếu thiếu width hoặc height
            $dimensionAttrs = '';
            if ($needsDimensions) {
                if (preg_match('/\ssrc\s*=\s*(["\'])(.*?)\1/i', $tag, $srcMatch)) {
                    $src = $srcMatch[2];
                    $parsedPath = parse_url($src, PHP_URL_PATH);
                    if ($parsedPath) {
                        $localFile = public_path(ltrim($parsedPath, '/\\'));
                        if (file_exists($localFile) && !is_dir($localFile)) {
                            $imgSize = @getimagesize($localFile);
                            if ($imgSize && !empty($imgSize[0]) && !empty($imgSize[1])) {
                                if (!$hasWidth) {
                                    $dimensionAttrs .= " width=\"{$imgSize[0]}\"";
                                }
                                if (!$hasHeight) {
                                    $dimensionAttrs .= " height=\"{$imgSize[1]}\"";
                                }
                            }
                        }
                    }
                }
            }

            // Nếu không cần thêm loading và cũng không cần thêm width/height thì giữ nguyên
            if (!$needsLoading && empty($dimensionAttrs)) {
                $skippedImages++;
                return $tag;
            }

            $modifiedImages++;
            $attributesToAppend = ($needsLoading ? ' loading="lazy"' : '') . $dimensionAttrs;

            // Bảo toàn cấu trúc thẻ: nếu là thẻ tự đóng <img ... /> thì giữ />
            if (preg_match('/\/\s*>$/', $tag)) {
                $newTag = preg_replace('/\s*\/\s*>$/', $attributesToAppend . ' />', $tag);
            } else {
                $newTag = preg_replace('/\s*>$/', $attributesToAppend . '>', $tag);
            }

            if (count($previewChanges) < 5) {
                $previewChanges[] = [
                    'original' => $tag,
                    'modified' => $newTag,
                ];
            }

            return $newTag;
        }, $content);

        return [
            'new_content' => $newContent,
            'total_images' => $totalImages,
            'modified_images' => $modifiedImages,
            'skipped_images' => $skippedImages,
            'preview_changes' => $previewChanges,
        ];
    }

    /**
     * Định dạng dung lượng RAM dễ nhìn
     */
    protected function formatMemory(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return round($bytes / 1073741824, 2) . ' GB';
        }
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }

    /**
     * Hiển thị bảng tóm tắt kết quả
     */
    protected function displaySummary(array $stats): void
    {
        $this->info("==========================================================================");
        $this->info("📊 BÁO CÁO TỔNG KẾT QUÁ TRÌNH");
        $this->info("==========================================================================");

        $rows = [];
        foreach ($stats as $label => $value) {
            $rows[] = [$label, $value];
        }

        $this->table(['Chỉ số', 'Chi tiết'], $rows);
        $this->line("");
    }
}
