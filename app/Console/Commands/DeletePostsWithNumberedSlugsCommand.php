<?php

/**
 * ====================================================================================
 * COMMAND: post:delete-numbered-slugs
 * ====================================================================================
 * CÔNG CỤ DỌN DẸP BÀI VIẾT TRÙNG LẶP CÓ ĐUÔI SỐ (-1, -2, -3...) VÀ XÓA ẢNH AN TOÀN
 *
 * 1. NGUYÊN LÝ HOẠT ĐỘNG:
 *    - Quét toàn bộ bài viết trong bảng posts có slug kết thúc bằng đuôi số đếm:
 *      Ví dụ: -1, -2, -3, ... -99, -100
 *    - TỰ ĐỘNG BẢO VỆ CÁC BÀI VIẾT CÓ ĐUÔI NĂM:
 *      Tự động loại trừ các slug kết thúc bằng năm 4 chữ số (1990 - 2099 như -2024, -2025, -2026),
 *      tránh tuyệt đối việc xóa nhầm các bài viết chuẩn SEO có gắn năm.
 *    - BẢO VỆ ẢNH CỦA BÀI VIẾT KHÁC (TRÁNH LÀM HỎNG GIAO DIỆN):
 *      Trước khi xóa bất kỳ file ảnh nào trên đĩa, công cụ sẽ kiểm tra chéo:
 *      + Nếu file ảnh đó ĐANG ĐƯỢC BÀI VIẾT KHÁC (bài giữ lại) SỬ DỤNG -> GIỮ LẠI FILE.
 *      + Nếu file ảnh đó ĐANG ĐƯỢC SẢN PHẨM HOẶC MEDIA KHÁC SỬ DỤNG -> GIỮ LẠI FILE.
 *      + Chỉ xóa file ảnh vật lý khi file đó hoàn toàn KHÔNG CÒN nơi nào khác dùng chung.
 *    - DỌN DẸP TOÀN DIỆN:
 *      Xóa bài viết, dọn dẹp revisions, comments, tag relations và record trong bảng images.
 *
 * 2. CÁC LỆNH CHẠY:
 *
 *    ① Chạy kiểm tra thử (DRY-RUN - Xem danh sách bài và ảnh sẽ xóa, KHÔNG XÓA THẬT):
 *       php artisan post:delete-numbered-slugs --dry-run
 *
 *    ② Chạy thực thi xóa thật (có hỏi xác nhận an toàn):
 *       php artisan post:delete-numbered-slugs
 *
 *    ③ Chạy thực thi xóa thật (bỏ qua hỏi xác nhận):
 *       php artisan post:delete-numbered-slugs --force
 *
 *    ④ Giới hạn số bài xóa thử (ví dụ xóa 10 bài đầu tiên):
 *       php artisan post:delete-numbered-slugs --limit=10 --force
 *
 *    ⑤ Chỉ xóa bài viết mà KHÔNG xóa file ảnh vật lý:
 *       php artisan post:delete-numbered-slugs --keep-files --force
 * ====================================================================================
 */

namespace App\Console\Commands;

use App\Models\Comment;
use App\Models\Image;
use App\Models\Post;
use App\Models\PostRevision;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class DeletePostsWithNumberedSlugsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'post:delete-numbered-slugs
                            {--dry-run : Chỉ quét kiểm tra và báo cáo, không xóa dữ liệu thật}
                            {--force : Bỏ qua bước xác nhận confirmation}
                            {--limit=0 : Giới hạn tối đa số bài viết cần xóa (0 = xóa hết)}
                            {--keep-files : Chỉ xóa bản ghi bài viết trong DB, không xóa file ảnh vật lý trên đĩa}
                            {--max-suffix=999 : Số đếm đuôi tối đa (-1 đến -999, mặc định 999 để tránh xóa năm 2024, 2025)}
                            {--include-years : Nếu muốn xóa cả những bài có đuôi năm 1990-2099}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Xóa toàn bộ bài viết có slug ở cuối có đuôi số (-1, -2, -3...) và xóa ảnh an toàn không làm hỏng bài viết khác';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $isForce = (bool) $this->option('force');
        $limit = max(0, (int) $this->option('limit'));
        $keepFiles = (bool) $this->option('keep-files');
        $maxSuffix = max(1, (int) $this->option('max-suffix'));
        $includeYears = (bool) $this->option('include-years');

        $this->displayBanner($isDryRun);

        $this->info("🔍 Đang quét cơ sở dữ liệu tìm bài viết có slug dạng -1, -2, -3... (Max suffix: {$maxSuffix})...");

        // 1. Quét tìm tất cả các bài viết có slug kết thúc bằng số trực tiếp từ SQL (Siêu nhanh, không tốn RAM)
        $query = DB::table('posts')
            ->select('id', 'title', 'slug', 'thumbnail', 'content')
            ->whereRaw("slug REGEXP '-[0-9]+$'");

        if (!$includeYears) {
            // Loại trừ trực tiếp các năm 1990 - 2099 ngay trong SQL
            $query->whereRaw("slug NOT REGEXP '-(199[0-9]|20[0-9]{2})$'");
        }

        $rawMatched = $query->orderBy('id', 'desc')->get();

        $targetPosts = [];
        foreach ($rawMatched as $post) {
            $slug = trim((string) $post->slug);
            if (!preg_match('/-(\d+)$/', $slug, $matches)) {
                continue;
            }

            $suffixNumber = (int) $matches[1];

            // Tự động bỏ qua các số năm (1990 - 2099) trừ khi có flag --include-years
            if (!$includeYears && ($suffixNumber >= 1990 && $suffixNumber <= 2099)) {
                continue;
            }

            // Giới hạn số đuôi nhân bản
            if ($suffixNumber > $maxSuffix && !$includeYears) {
                continue;
            }

            $targetPosts[] = $post;

            if ($limit > 0 && count($targetPosts) >= $limit) {
                break;
            }
        }

        $totalFound = count($targetPosts);

        if ($totalFound === 0) {
            $this->info("✨ Không tìm thấy bài viết nào có slug đuôi số (-1, -2, -3...) cần xóa!");
            return 0;
        }

        $this->warn("⚠️ Tìm thấy " . number_format($totalFound) . " bài viết có slug dạng đuôi số (-1, -2, -3...).");

        // Hiển thị một số bài mẫu
        $this->line("\n[Một số bài viết tiêu biểu sẽ bị xóa]:");
        foreach (array_slice($targetPosts, 0, 8) as $p) {
            $this->line("  • #{$p->id} | {$p->slug} | {$p->title}");
        }
        if ($totalFound > 8) {
            $this->line("  ... và còn " . ($totalFound - 8) . " bài viết khác.");
        }
        $this->line("");

        if (!$isDryRun && !$isForce) {
            if (!$this->confirm("❗ Bạn có CHẮC CHẮN muốn xóa vĩnh viễn {$totalFound} bài viết này cùng toàn bộ ảnh của chúng không?", false)) {
                $this->warn("Đã hủy thao tác.");
                return 0;
            }
        }

        $targetIds = array_column($targetPosts, 'id');
        $targetIdMap = array_flip($targetIds);

        // 2. Trích xuất danh sách tất cả các ảnh từ content và thumbnail của các bài bị xóa
        $this->info("📷 Đang phân tích danh sách ảnh trong content và thumbnail của các bài viết...");
        $candidateImages = []; // [filename => [fullPath, ...]]

        foreach ($targetPosts as $post) {
            // Ảnh thumbnail
            if (!empty($post->thumbnail)) {
                $thumbName = basename(parse_url($post->thumbnail, PHP_URL_PATH));
                if ($thumbName) {
                    $candidateImages[$thumbName] = $this->resolvePhysicalPaths($thumbName, $post->thumbnail);
                }
            }

            // Ảnh trong content
            if (!empty($post->content)) {
                if (preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', $post->content, $imgMatches)) {
                    foreach ($imgMatches[1] as $src) {
                        $srcClean = trim($src);
                        if (empty($srcClean)) continue;

                        $filename = basename(parse_url($srcClean, PHP_URL_PATH));
                        if ($filename && !isset($candidateImages[$filename])) {
                            $candidateImages[$filename] = $this->resolvePhysicalPaths($filename, $srcClean);
                        }
                    }
                }
            }
        }

        $totalImagesFound = count($candidateImages);
        $this->line("   ✓ Đã phát hiện tổng cộng " . number_format($totalImagesFound) . " file ảnh liên quan.");

        // 3. KIỂM TRA AN TOÀN TUYỆT ĐỐI SIÊU TỐC (Single-pass Streaming & Indexed Lookup)
        $safeToDeleteImages = [];
        $preservedImages = [];
        $usedInOtherPlaces = [];

        if (!$keepFiles && $totalImagesFound > 0) {
            $this->info("⚡ Đang kiểm tra chéo an toàn siêu tốc (Bảo vệ các ảnh đang được nơi khác dùng chung)...");

            $candidateNames = array_keys($candidateImages);

            // 3.1. Kiểm tra bảng images (Media / Products) bằng indexed WHERE IN theo chunk (0.005s)
            if (SchemaHasTable('images')) {
                foreach (array_chunk($candidateNames, 500) as $chunkNames) {
                    $foundMedia = DB::table('images')
                        ->where(function ($q) use ($targetIds) {
                            $q->where('entity_type', '!=', 'post')
                              ->orWhereNotIn('entity_id', $targetIds);
                        })
                        ->whereIn('name', $chunkNames)
                        ->pluck('name')
                        ->all();

                    foreach ($foundMedia as $mName) {
                        $usedInOtherPlaces[$mName] = true;
                    }
                }
            }

            // 3.2. Kiểm tra cột thumbnail của các bài viết giữ lại (0.01s)
            $otherThumbnails = DB::table('posts')
                ->whereNotIn('id', $targetIds)
                ->whereNotNull('thumbnail')
                ->where('thumbnail', '!=', '')
                ->pluck('thumbnail');

            foreach ($otherThumbnails as $thumb) {
                $thumbBase = basename(parse_url($thumb, PHP_URL_PATH));
                if ($thumbBase && isset($candidateImages[$thumbBase])) {
                    $usedInOtherPlaces[$thumbBase] = true;
                }
            }

            // 3.3. Kiểm tra cột content của các bài viết giữ lại bằng Single-Pass Chunk Streaming
            // Chỉ cần kiểm tra những ảnh chưa được đánh dấu là used
            $remainingCandidates = array_diff_key($candidateImages, $usedInOtherPlaces);

            if (!empty($remainingCandidates)) {
                $postCountWithImages = DB::table('posts')
                    ->whereNotIn('id', $targetIds)
                    ->where('content', 'LIKE', '%<img%')
                    ->count();

                if ($postCountWithImages > 0) {
                    $bar = $this->output->createProgressBar($postCountWithImages);
                    $bar->setFormat("   [%bar%] %percent:3s%% (%current%/%max% bài viết giữ lại)");
                    $bar->start();

                    DB::table('posts')
                        ->whereNotIn('id', $targetIds)
                        ->where('content', 'LIKE', '%<img%')
                        ->select('id', 'content')
                        ->orderBy('id', 'asc')
                        ->chunkById(500, function ($postsChunk) use (&$usedInOtherPlaces, &$remainingCandidates, $bar) {
                            // Gộp content của chunk vào bộ nhớ để quét strpos siêu tốc (hàm C của PHP)
                            $combined = '';
                            foreach ($postsChunk as $p) {
                                $combined .= $p->content . ' ';
                                $bar->advance();
                            }

                            // Quét tức thời bằng strpos
                            foreach ($remainingCandidates as $fn => $paths) {
                                if (strpos($combined, $fn) !== false) {
                                    $usedInOtherPlaces[$fn] = true;
                                    unset($remainingCandidates[$fn]);
                                }
                            }

                            // Nếu tất cả candidate images đều đã được phát hiện dùng chung -> Early exit ngay lập tức
                            if (empty($remainingCandidates)) {
                                return false;
                            }
                        });

                    $bar->finish();
                    $this->line("");
                }
            }

            // Phân loại ảnh an toàn và ảnh được bảo vệ
            foreach ($candidateImages as $filename => $paths) {
                if (isset($usedInOtherPlaces[$filename])) {
                    $preservedImages[$filename] = $paths;
                } else {
                    $safeToDeleteImages[$filename] = $paths;
                }
            }
        } else {
            $safeToDeleteImages = [];
            $preservedImages = $candidateImages;
        }

        $this->line("   • Ảnh an toàn ĐƯỢC PHÉP XÓA: <fg=green;options=bold>" . count($safeToDeleteImages) . "</> file");
        $this->line("   • Ảnh ĐƯỢC BẢO VỆ GIỮ LẠI (bài viết khác đang dùng chung): <fg=yellow;options=bold>" . count($preservedImages) . "</> file\n");

        // 4. TIẾN HÀNH XÓA DỮ LIỆU
        $deletedPostsCount = 0;
        $deletedFilesCount = 0;

        if (!$isDryRun) {
            $this->info("🗑️ Đang tiến hành xóa bài viết và dọn dẹp cơ sở dữ liệu...");

            // Xóa file vật lý an toàn
            if (!$keepFiles) {
                foreach ($safeToDeleteImages as $filename => $paths) {
                    foreach ($paths as $path) {
                        if (File::exists($path)) {
                            File::delete($path);
                            $deletedFilesCount++;
                        }
                    }
                }
            }

            // Xóa dữ liệu DB theo chunk
            foreach (array_chunk($targetIds, 100) as $chunkIds) {
                // Xóa revisions
                if (SchemaHasTable('post_revisions')) {
                    DB::table('post_revisions')->whereIn('post_id', $chunkIds)->delete();
                }

                // Xóa comments
                if (SchemaHasTable('comments')) {
                    DB::table('comments')
                        ->where('commentable_type', Post::class)
                        ->whereIn('commentable_id', $chunkIds)
                        ->delete();
                }

                // Xóa tags quan hệ
                if (SchemaHasTable('taggables')) {
                    DB::table('taggables')
                        ->where('taggable_type', Post::class)
                        ->whereIn('taggable_id', $chunkIds)
                        ->delete();
                }

                // Xóa records trong bảng images
                if (SchemaHasTable('images')) {
                    DB::table('images')
                        ->where('entity_type', 'post')
                        ->whereIn('entity_id', $chunkIds)
                        ->delete();
                }

                // Xóa bài viết vĩnh viễn
                $deletedPostsCount += DB::table('posts')->whereIn('id', $chunkIds)->delete();
            }

            $this->info("✨ Đã hoàn tất xóa bài viết và dọn dẹp file thành công!");
        } else {
            $this->info("💡 [DRY-RUN] Không có dữ liệu nào bị thay đổi trong Database và ổ đĩa.");
            $deletedPostsCount = $totalFound;
            $deletedFilesCount = count($safeToDeleteImages);
        }

        $totalPostsInDb = DB::table('posts')->count();
        $this->displaySummary([
            'Chế độ' => $isDryRun ? 'DRY-RUN (Xem trước)' : 'THỰC THI (Đã xóa vĩnh viễn)',
            'Tổng số bài viết trong hệ thống' => number_format($totalPostsInDb),
            'Số bài viết trùng slug (-1, -2..) cần xóa' => number_format($deletedPostsCount),
            'Tổng số file ảnh trong content/thumb' => number_format($totalImagesFound),
            'Số file ảnh vật lý đã xóa an toàn' => number_format($deletedFilesCount),
            'Số file ảnh được bảo vệ giữ lại (dùng chung)' => number_format(count($preservedImages)),
        ]);

        return 0;
    }

    /**
     * Xác định tất cả các đường dẫn file vật lý có thể có của một ảnh
     */
    protected function resolvePhysicalPaths(string $filename, string $originalSrc): array
    {
        $paths = [];

        // 1. Thư mục chuẩn posts: public/clients/assets/img/posts/
        $standardPath = public_path("clients/assets/img/posts/{$filename}");
        if (File::exists($standardPath)) {
            $paths[] = $standardPath;
        }

        // 2. Thử từ URL gốc (nếu là đường dẫn tương đối trong public)
        $parsedPath = parse_url($originalSrc, PHP_URL_PATH);
        if ($parsedPath) {
            $trimmed = ltrim($parsedPath, '/\\');
            $customPublicPath = public_path($trimmed);
            if (File::exists($customPublicPath) && !in_array($customPublicPath, $paths, true)) {
                $paths[] = $customPublicPath;
            }
        }

        return $paths;
    }

    /**
     * Banner hiển thị
     */
    protected function displayBanner(bool $isDryRun): void
    {
        $mode = $isDryRun ? "DRY-RUN (CHỈ QUÉT KIỂM TRA - KHÔNG XÓA THẬT)" : "CHẾ ĐỘ THỰC THI (XÓA DỮ LIỆU THẬT)";
        $this->line("======================================================================");
        $this->info("    CÔNG CỤ XÓA BÀI VIẾT TRÙNG SLUG (-1, -2, -3) & DỌN DẸP ẢNH");
        $this->line("    Trạng thái: <fg=" . ($isDryRun ? "yellow" : "red") . ";options=bold>{$mode}</>");
        $this->line("======================================================================");
    }

    /**
     * Bảng tổng kết
     */
    protected function displaySummary(array $stats): void
    {
        $this->line("======================================================================");
        $this->info("                       KẾT QUẢ DỌN DẸP");
        $this->line("======================================================================");
        $rows = [];
        foreach ($stats as $key => $val) {
            $rows[] = [$key, $val];
        }
        $this->table(['Hạng mục', 'Số liệu'], $rows);
        $this->line("======================================================================");
    }
}

/**
 * Helper kiểm tra bảng tồn tại an toàn
 */
function SchemaHasTable(string $table): bool
{
    try {
        return \Illuminate\Support\Facades\Schema::hasTable($table);
    } catch (\Throwable) {
        return false;
    }
}
