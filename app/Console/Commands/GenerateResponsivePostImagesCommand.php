<?php

/**
 * ====================================================================================================
 * COMMAND: posts:generate-responsive-images
 * ====================================================================================================
 * CÔNG CỤ TỰ ĐỘNG TẠO ẢNH RESPONSIVE (700w WEBP) ĐA TIẾN TRÌNH SIÊU TỐC CHO BÀI VIẾT
 *
 * 🎯 1. TẠI SAO PHẢI LÀ 700w THAY VÌ 800w?
 *    - Google PageSpeed Mobile (Moto G Power) có chiều rộng hiển thị thực tế là 697x522px
 *      (Màn hình 412px - 14px padding lề = 398px * mật độ DPR 1.75 = 696.85px ~ 697px).
 *    - Nếu để 800w: Google sẽ báo lỗi "Tệp hình ảnh lớn hơn mức cần thiết (800x600 so với 697x522)".
 *    - Khi dùng 700w: Kích thước 700px khớp 100% với 697px (chỉ chênh 3px = 0.4%), triệt tiêu
 *      hoàn toàn lỗi phân phối ảnh và tiết kiệm thêm dung lượng tải trang!
 *
 * 🛡️ 2. BẢO VỆ DỮ LIỆU & AN TOÀN TUYỆT ĐỐI:
 *    - Ảnh gốc (JPG/PNG/WEBP) KHÔNG BAO GIỜ bị ghi đè hay xóa, chỉ tạo thêm bản phụ đuôi "-700w.webp".
 *    - Tận dụng thông minh: Tự động phát hiện các ảnh đã có bản "-800w.webp" trên server để nén tiếp
 *      sang "-700w.webp" trong 1 phần nghìn giây (siêu nhanh, không tốn CPU).
 *    - Sử dụng đường dẫn tương đối ("/clients/assets/img/posts/..."): Vĩnh viễn không bị lỗi domain
 *      "http://localhost" khi chạy qua Terminal SSH / cronjob.
 *    - Bỏ qua O(1) tức thì: Tự động phát hiện các ảnh đã có bản 700w, không nén lại.
 *
 * ====================================================================================================
 * 🚀 3. HƯỚNG DẪN CÁC LỆNH CHẠY CHI TIẾT TRÊN SERVER (VPS):
 * ====================================================================================================
 *
 * 🟢 KỊCH BẢN 1: KHÔI PHỤC TOÀN BỘ VỀ GỐC (NẾU MUỐN RESET HOẶC SỬA LỖI ẢNH)
 *    Lệnh này sẽ quét toàn bộ bài viết trong DB, gỡ sạch mọi thẻ srcset/sizes bị lỗi, đưa ảnh về link gốc sạch 100%:
 *    👉 php artisan posts:generate-responsive-images --restore
 *    👉 php artisan optimize:clear
 *
 * 🟢 KỊCH BẢN 2: CHẠY ĐẦY ĐỦ (NÉN ẢNH ĐA LUỒNG + CẬP NHẬT DATABASE) [KHUYÊN DÙNG]
 *    Tận dụng 12 luồng CPU máy chủ song song, tự động tạo bản 700w từ 800w siêu nhanh, cập nhật DB an toàn:
 *    👉 php artisan posts:generate-responsive-images --update-content --concurrency=12
 *
 * 🟢 KỊCH BẢN 3: CHỈ CẬP NHẬT DATABASE (CHẠY SIÊU TỐC TRONG 2 GIÂY)
 *    Dùng khi các file ảnh -700w.webp đã có sẵn trên máy chủ, chỉ muốn cập nhật lại thẻ <img> trong DB:
 *    👉 php artisan posts:generate-responsive-images --only-content
 *
 * 🟢 KỊCH BẢN 4: TÙY BIẾN THÔNG SỐ NÂNG CAO (NẾU CẦN)
 *    - Thay đổi độ rộng ảnh mục tiêu: --target-width=700 (mặc định 700px tối ưu Google PageSpeed)
 *    - Thay đổi chất lượng nén:       --quality=80 (mặc định 80)
 *    Ví dụ:
 *    👉 php artisan posts:generate-responsive-images --update-content --target-width=700 --quality=80 --concurrency=8
 * ====================================================================================================
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

class GenerateResponsivePostImagesCommand extends Command
{
    protected $signature = 'posts:generate-responsive-images
                            {--target-width=700 : Chiều rộng ảnh mobile mục tiêu (mặc định 700px chuẩn xác Google PageSpeed)}
                            {--quality=80 : Chất lượng nén WebP (0-100, tối ưu 80)}
                            {--concurrency=8 : Số luồng tiến trình chạy song song (mặc định 8 luồng)}
                            {--all : Quét toàn bộ thư mục (bao gồm ảnh rác). Mặc định chỉ quét ảnh đang dùng trong bảng posts}
                            {--update-content : Tự động cập nhật srcset và sizes vào thẻ <img> trong DB bảng posts}
                            {--only-content : Chỉ cập nhật nội dung DB, bỏ qua bước nén ảnh (siêu nhanh 1s)}
                            {--restore : Khôi phục toàn bộ bài viết, gỡ bỏ toàn bộ srcset lỗi, hiển thị lại ảnh gốc 100%}
                            {--worker : Cờ nội bộ cho worker tiến trình con}
                            {--batch-file= : File batch danh sách ảnh cho worker}';

    protected $description = 'Tự động tạo các bản ảnh 700w.webp đa luồng siêu tốc cho bài viết để tối ưu Google PageSpeed 100 điểm';

    public function handle(): int
    {
        $targetWidth = (int) $this->option('target-width');
        $quality = (int) $this->option('quality');
        $concurrency = max(1, (int) $this->option('concurrency'));
        $updateContent = (bool) $this->option('update-content');
        $onlyContent = (bool) $this->option('only-content');
        $isWorker = (bool) $this->option('worker');
        $batchFile = $this->option('batch-file');

        $postsDir = public_path('clients/assets/img/posts');
        if (!is_dir($postsDir)) {
            $this->error("❌ Không tìm thấy thư mục: {$postsDir}");
            return 1;
        }

        // =====================================================================
        // TRƯỜNG HỢP: KHÔI PHỤC TOÀN BỘ BÀI VIẾT VỀ NGUYÊN BẢN SẠCH SẼ
        // =====================================================================
        if ($this->option('restore')) {
            $this->info("🔄 Đang khôi phục toàn bộ bài viết về nguyên bản (gỡ bỏ các thuộc tính srcset bị lỗi)...");
            $totalFixed = 0;
            DB::table('posts')->whereNotNull('content')->where('content', 'LIKE', '%srcset%')->orderBy('id')->chunk(100, function ($posts) use (&$totalFixed) {
                foreach ($posts as $post) {
                    $cleaned = preg_replace('/\s+srcset\s*=\s*(["\']).*?\1/is', '', $post->content);
                    $cleaned = preg_replace('/\s+sizes\s*=\s*(["\']).*?\1/is', '', $cleaned);
                    if ($cleaned !== $post->content) {
                        DB::table('posts')->where('id', $post->id)->update(['content' => $cleaned]);
                        $totalFixed++;
                    }
                }
            });
            $this->info("✨ Đã khôi phục thành công {$totalFixed} bài viết! Toàn bộ ảnh đã hiển thị lại 100% nguyên bản!");
            return 0;
        }

        // =====================================================================
        // TRƯỜNG HỢP 1: NẾU LÀ WORKER TIẾN TRÌNH CON
        // =====================================================================
        if ($isWorker && !empty($batchFile)) {
            return $this->runWorker($targetWidth, $quality, $batchFile, $postsDir);
        }

        // =====================================================================
        // TRƯỜNG HỢP 2: TIẾN TRÌNH CHA ĐIỀU PHỐI (MASTER PROCESS)
        // =====================================================================
        $this->info("==========================================================================");
        $this->info("⚡ CÔNG CỤ TẠO ẢNH RESPONSIVE (800w WEBP) ĐA LUỒNG SIÊU TỐC");
        $this->info("==========================================================================");

        // BƯỚC 1: LẬP CHỈ MỤC O(1) CÁC ẢNH ĐÃ CÓ BẢN -800w.webp TRONG BỘ NHỚ RAM
        $allFiles = @scandir($postsDir) ?: [];
        $existingGenerated = [];
        $candidates = [];

        foreach ($allFiles as $f) {
            if ($f === '.' || $f === '..') {
                continue;
            }
            if (str_ends_with($f, "-{$targetWidth}w.webp")) {
                $existingGenerated[$f] = true;
                continue;
            }
            $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $candidates[] = $f;
            }
        }

        $totalOriginalInDir = count($candidates);
        $totalExisting = count($existingGenerated);

        $this->line("📂 File ảnh trong thư mục: <fg=green;options=bold>{$totalOriginalInDir}</>");
        $this->line("⚡ Đã có sẵn bản {$targetWidth}w.webp: <fg=cyan;options=bold>{$totalExisting}</> file.");

        // BƯỚC 2: THU THẬP DANH SÁCH ẢNH CẦN NÉN (NẾU KHÔNG DÙNG --only-content)
        if (!$onlyContent) {
            $queue = [];

            if ($this->option('all')) {
                $this->warn("⚠️  Chế độ quét toàn bộ thư mục (--all): Quét tất cả file ảnh trên ổ đĩa...");
                foreach ($candidates as $file) {
                    $info = pathinfo($file);
                    $targetFilename = $info['filename'] . "-{$targetWidth}w.webp";
                    if (!isset($existingGenerated[$targetFilename])) {
                        $queue[] = [
                            'file' => $file,
                            'target' => $targetFilename,
                        ];
                    }
                }
            } else {
                $this->info("🎯 Chế độ thông minh: Chỉ lọc các ảnh THỰC TẾ ĐANG DÙNG trong bảng posts...");
                $usedFiles = [];

                // 1. Quét từ thumbnails của bài viết
                $thumbnails = DB::table('posts')->whereNotNull('thumbnail')->pluck('thumbnail');
                foreach ($thumbnails as $t) {
                    $base = basename($t);
                    if (!empty($base)) {
                        $usedFiles[$base] = true;
                        $info = pathinfo($base);
                        $cleanBase = preg_replace('/-\d+w$/i', '', $info['filename']);

                        // Bản 120w cho thumbnail bài liên quan chuẩn 16:9 (120x68px siêu nhẹ ~1.5KB)
                        $t120 = $cleanBase . "-120w.webp";
                        if (!file_exists($postsDir . DIRECTORY_SEPARATOR . $t120)) {
                            $queue[] = [
                                'file' => $base,
                                'target' => $t120,
                                'width' => 120,
                            ];
                        }

                        // Bản 200w cho thumbnail phụ
                        $t200 = $cleanBase . "-200w.webp";
                        if (!file_exists($postsDir . DIRECTORY_SEPARATOR . $t200)) {
                            $queue[] = [
                                'file' => $base,
                                'target' => $t200,
                                'width' => 200,
                            ];
                        }

                        // Bản 1200w cho hero image desktop
                        $t1200 = $cleanBase . "-1200w.webp";
                        if (!file_exists($postsDir . DIRECTORY_SEPARATOR . $t1200)) {
                            $queue[] = [
                                'file' => $base,
                                'target' => $t1200,
                                'width' => 1200,
                            ];
                        }
                    }
                }

                // 2. Quét từ thẻ <img> trong nội dung bài viết
                DB::table('posts')->whereNotNull('content')->where('content', 'LIKE', '%<img%')->orderBy('id')->chunk(100, function ($posts) use (&$usedFiles) {
                    foreach ($posts as $p) {
                        if (preg_match_all('/\/clients\/assets\/img\/posts\/([a-zA-Z0-9_\.\-]+)/i', $p->content, $m)) {
                            foreach ($m[1] as $fn) {
                                $usedFiles[basename($fn)] = true;
                            }
                        }
                    }
                });

                $this->line("📌 Ảnh thực tế đang hiển thị trong bài viết: <fg=cyan;options=bold>" . count($usedFiles) . "</> ảnh.");

                foreach (array_keys($usedFiles) as $file) {
                    if (str_ends_with($file, "-{$targetWidth}w.webp")) {
                        continue;
                    }

                    $info = pathinfo($file);
                    $cleanBase = preg_replace('/-\d+w$/i', '', $info['filename']);
                    $targetFilename = $cleanBase . "-{$targetWidth}w.webp";

                    $fullPath = $postsDir . DIRECTORY_SEPARATOR . $file;
                    $p800 = $postsDir . DIRECTORY_SEPARATOR . $cleanBase . '-800w.webp';
                    if (!file_exists($fullPath) && !file_exists($p800)) {
                        continue;
                    }

                    if (!isset($existingGenerated[$targetFilename])) {
                        $queue[] = [
                            'file' => $file,
                            'target' => $targetFilename,
                            'width' => $targetWidth,
                        ];
                    }
                }
            }

            $needToProcess = count($queue);

            if ($needToProcess === 0) {
                $this->info("✅ Tất cả các ảnh cần thiết đã có bản responsive đầy đủ, bỏ qua 100% không cần nén lại!");
            } else {
                $this->info("🚀 Cần tạo mới: <fg=yellow;options=bold>{$needToProcess}</> ảnh. Đang kích hoạt <fg=green;options=bold>{$concurrency} luồng</> song song...");

                // CHIA BATCH THÀNH CÁC PHẦN BẰNG NHAU CHO CÁC WORKER TIẾN TRÌNH CON
                $batchCount = min($concurrency, $needToProcess);
                $chunks = array_chunk($queue, (int) ceil($needToProcess / $batchCount));
                $tempDir = storage_path('framework/cache');
                if (!is_dir($tempDir)) {
                    @mkdir($tempDir, 0755, true);
                }

                $processes = [];
                $batchFiles = [];
                $phpBinary = (new PhpExecutableFinder())->find() ?: 'php';

                foreach ($chunks as $index => $chunk) {
                    $bFile = $tempDir . DIRECTORY_SEPARATOR . "img_batch_{$index}_" . time() . ".json";
                    file_put_contents($bFile, json_encode($chunk));
                    $batchFiles[] = $bFile;

                    $p = new Process([
                        $phpBinary,
                        base_path('artisan'),
                        'posts:generate-responsive-images',
                        '--worker',
                        '--batch-file=' . $bFile,
                        '--target-width=' . $targetWidth,
                        '--quality=' . $quality,
                    ]);
                    $p->setTimeout(7200);
                    $p->start();
                    $processes[] = $p;
                }

                $bar = $this->output->createProgressBar($needToProcess);
                $bar->setFormat(" %current%/%max% [%bar%] %percent:3s%% (Đang chạy %message% luồng song song)");
                $bar->setMessage((string) count($processes));
                $bar->start();

                // VÒNG LẶP THEO DÕI CÁC WORKER CHẠY SONG SONG
                while (count($processes) > 0) {
                    foreach ($processes as $k => $p) {
                        $output = $p->getIncrementalOutput();
                        if (!empty($output)) {
                            $lines = array_filter(explode("\n", trim($output)));
                            foreach ($lines as $line) {
                                if (str_starts_with($line, 'DONE:') || str_starts_with($line, 'SKIP:')) {
                                    $bar->advance();
                                }
                            }
                        }

                        if (!$p->isRunning()) {
                            unset($processes[$k]);
                            $bar->setMessage((string) count($processes));
                        }
                    }
                    usleep(25000); // 25ms nghỉ tránh nghẽn CPU
                }

                $bar->finish();
                $this->newLine(2);

                // DỌN DẸP FILE TẠM
                foreach ($batchFiles as $bf) {
                    @unlink($bf);
                }

                $this->info("✨ Đã hoàn tất toàn bộ tiến trình nén đa luồng!");

                // Cập nhật lại danh sách existingGenerated
                foreach ($queue as $q) {
                    $existingGenerated[$q['target']] = true;
                }
            }
        }

        // BƯỚC 3: CẬP NHẬT DATABASE BẢNG POSTS (SIÊU TỐC TRONG RAM)
        if ($updateContent || $onlyContent) {
            $this->newLine();
            $this->info("🔄 Đang cập nhật responsive srcset vào bài viết trong database...");

            $postsQuery = DB::table('posts')->whereNotNull('content')->where('content', 'LIKE', '%<img%');
            $totalPosts = $postsQuery->count();

            if ($totalPosts === 0) {
                $this->info("ℹ️ Không tìm thấy bài viết nào chứa thẻ <img>.");
                return 0;
            }

            $bar = $this->output->createProgressBar($totalPosts);
            $bar->setFormat(" %current%/%max% [%bar%] %percent:3s%%");
            $bar->start();

            $updatedPosts = 0;

            $postsQuery->orderBy('id')->chunk(100, function ($posts) use (&$updatedPosts, $existingGenerated, $targetWidth, $bar) {
                foreach ($posts as $post) {
                    $originalContent = $post->content;
                    $newContent = preg_replace_callback('/<img\b(?:[^>"\'\s]|\s(?![>])|"[^"]*"|\'[^\']*\')*>/is', function ($matches) use ($existingGenerated, $targetWidth) {
                        $tag = $matches[0];

                        // Nếu có srcset chứa localhost hoặc sizes cũ thì làm mới lại sạch sẽ
                        if (str_contains($tag, 'localhost') || str_contains($tag, 'https//') || str_contains($tag, '100vw, 800px') || str_contains($tag, 'calc(100vw - 32px)') || (str_contains($tag, '-800w.webp') && !str_contains($tag, "-{$targetWidth}w.webp"))) {
                            $tag = preg_replace('/\s+srcset\s*=\s*(["\']).*?\1/is', '', $tag);
                            $tag = preg_replace('/\s+sizes\s*=\s*(["\']).*?\1/is', '', $tag);
                        }

                        if (str_contains($tag, 'srcset=')) {
                            return $tag;
                        }

                        if (preg_match('/\ssrc\s*=\s*["\']?([^"\'\s>]+)["\']?/i', $tag, $m)) {
                            $src = trim($m[1], "\"'");
                            $path = parse_url($src, PHP_URL_PATH);
                            if ($path && str_contains($path, '/clients/assets/img/posts/')) {
                                $filename = basename($path);
                                $cleanFilename = preg_replace('/-\d+w(\.[a-zA-Z0-9]+)$/i', '$1', $filename);
                                $info = pathinfo($cleanFilename);
                                $cleanBase = $info['filename'];
                                $responsiveName = $cleanBase . "-{$targetWidth}w.webp";
                                $responsive800Name = $cleanBase . "-800w.webp";
                                $responsive1200Name = $cleanBase . "-1200w.webp";

                                // Kiểm tra O(1) ngay trong RAM
                                if (isset($existingGenerated[$responsiveName])) {
                                    $candidates = [];
                                    $candidates[] = "/clients/assets/img/posts/{$responsiveName} {$targetWidth}w";

                                    if (isset($existingGenerated[$responsive800Name])) {
                                        $candidates[] = "/clients/assets/img/posts/{$responsive800Name} 800w";
                                    }
                                    if (isset($existingGenerated[$responsive1200Name])) {
                                        $candidates[] = "/clients/assets/img/posts/{$responsive1200Name} 1200w";
                                    } else {
                                        $candidates[] = "/clients/assets/img/posts/{$cleanFilename} 1200w";
                                    }

                                    $srcsetStr = implode(', ', $candidates);
                                    $srcsetAttr = " srcset=\"{$srcsetStr}\" sizes=\"(max-width: 768px) calc(100vw - 14px), 808px\"";
                                    return preg_replace('/(\s*\/?>)$/', $srcsetAttr . '$1', $tag);
                                }
                            }
                        }

                        return $tag;
                    }, $originalContent);


                    if ($newContent !== $originalContent) {
                        DB::table('posts')->where('id', $post->id)->update(['content' => $newContent]);
                        $updatedPosts++;
                    }

                    $bar->advance();
                }
            });

            $bar->finish();
            $this->newLine(2);
            $this->info("✨ Hoàn tất cập nhật DB: Đã thêm responsive srcset cho {$updatedPosts} bài viết!");
        }

        return 0;
    }

    /**
     * Hàm xử lý dành riêng cho từng Worker tiến trình con
     */
    protected function runWorker(int $targetWidth, int $quality, string $batchFile, string $postsDir): int
    {
        if (!file_exists($batchFile)) {
            return 1;
        }

        $items = json_decode(file_get_contents($batchFile), true) ?: [];

        foreach ($items as $item) {
            $file = $item['file'];
            $targetFilename = $item['target'];
            $fullPath = $postsDir . DIRECTORY_SEPARATOR . $file;
            $targetPath = $postsDir . DIRECTORY_SEPARATOR . $targetFilename;

            if (file_exists($targetPath)) {
                $this->line("SKIP:{$file}");
                continue;
            }

            if (!file_exists($fullPath)) {
                $this->line("SKIP:{$file}");
                continue;
            }

            $targetW = (int) ($item['width'] ?? $targetWidth);
            $cleanBase = preg_replace('/-\d+w$/i', '', pathinfo($file, PATHINFO_FILENAME));
            $p800 = $postsDir . DIRECTORY_SEPARATOR . $cleanBase . '-800w.webp';
            $sourceFile = (file_exists($p800) && $targetW < 800) ? $p800 : $fullPath;

            if (!file_exists($sourceFile)) {
                $this->line("SKIP:{$file}");
                continue;
            }

            $size = @getimagesize($sourceFile);
            if (!$size || $size[0] <= $targetW) {
                $this->line("SKIP:{$file}");
                continue;
            }

            $origW = $size[0];
            $origH = $size[1];
            $type = $size[2] ?? 0;

            $newW = $targetW;
            $newH = (int) round($origH * ($newW / $origW));

            $img = match ($type) {
                IMAGETYPE_JPEG => @imagecreatefromjpeg($sourceFile),
                IMAGETYPE_PNG => @imagecreatefrompng($sourceFile),
                IMAGETYPE_WEBP => @imagecreatefromwebp($sourceFile),
                default => null,
            };

            if (!$img) {
                $this->line("SKIP:{$file}");
                continue;
            }

            $canvas = imagecreatetruecolor($newW, $newH);
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);

            imagecopyresampled($canvas, $img, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
            @imagewebp($canvas, $targetPath, $quality);

            imagedestroy($canvas);
            imagedestroy($img);

            $this->line("DONE:{$file}");
        }

        return 0;
    }
}
