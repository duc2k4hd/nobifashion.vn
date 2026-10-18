<?php

/**
 * ====================================================================================
 * COMMAND: assets:minify
 * ====================================================================================
 * TỰ ĐỘNG TỐI ƯU VÀ NÉN NHỎ CÁC FILE CSS & JS CHO CLIENT (TĂNG ĐIỂM PAGESPEED)
 *
 * Cú pháp:
 *   php artisan assets:minify
 * ====================================================================================
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MinifyAssetsCommand extends Command
{
    protected $signature = 'assets:minify';
    protected $description = 'Nén nhỏ (minify) các file CSS và JavaScript ở public/clients/assets để tăng tốc tải trang';

    public function handle(): int
    {
        $this->info("🚀 Đang tiến hành nén các file CSS và JS...");

        $cssFiles = [
            'public/clients/assets/css/main.css' => 'public/clients/assets/css/main.min.css',
            'public/clients/assets/css/blog-detail.css' => 'public/clients/assets/css/blog-detail.min.css',
            'public/clients/assets/css/responsive.css' => 'public/clients/assets/css/responsive.min.css',
            'public/clients/assets/css/author.css' => 'public/clients/assets/css/author.min.css',
            'public/clients/assets/css/single.css' => 'public/clients/assets/css/single.min.css',
            'public/clients/assets/css/home.css' => 'public/clients/assets/css/home.min.css',
            'public/clients/assets/css/blog.css' => 'public/clients/assets/css/blog.min.css',
            'public/clients/assets/css/shop.css' => 'public/clients/assets/css/shop.min.css'
        ];

        foreach ($cssFiles as $src => $dest) {
            $srcPath = base_path($src);
            $destPath = base_path($dest);

            if (!file_exists($srcPath)) {
                $this->warn("⚠️  Không tìm thấy: {$src}");
                continue;
            }

            $content = file_get_contents($srcPath);
            $origSize = strlen($content);
            $minified = $this->minifyCSS($content);
            file_put_contents($destPath, $minified);
            $newSize = strlen($minified);
            $saved = round((($origSize - $newSize) / $origSize) * 100, 1);

            $this->line("  ✓ " . basename($src) . " → " . basename($dest) . " (" . number_format($origSize) . "B → " . number_format($newSize) . "B, giảm {$saved}%)");
        }

        $jsFiles = [
            'public/clients/assets/js/main.js' => 'public/clients/assets/js/main.min.js',
            'public/clients/assets/js/header.js' => 'public/clients/assets/js/header.min.js',
            'public/clients/assets/js/single.js' => 'public/clients/assets/js/single.min.js',
            'public/clients/assets/js/home.js' => 'public/clients/assets/js/home.min.js',
            'public/clients/assets/js/shop.js' => 'public/clients/assets/js/shop.min.js'
        ];

        foreach ($jsFiles as $src => $dest) {
            $srcPath = base_path($src);
            $destPath = base_path($dest);

            if (!file_exists($srcPath)) {
                $this->warn("⚠️  Không tìm thấy: {$src}");
                continue;
            }

            $content = file_get_contents($srcPath);
            $origSize = strlen($content);
            $minified = $this->minifyJS($content);
            file_put_contents($destPath, $minified);
            $newSize = strlen($minified);
            $saved = round((($origSize - $newSize) / $origSize) * 100, 1);

            $this->line("  ✓ " . basename($src) . " → " . basename($dest) . " (" . number_format($origSize) . "B → " . number_format($newSize) . "B, giảm {$saved}%)");
        }

        $this->info("✨ Hoàn tất tối ưu hóa tài nguyên!");
        return 0;
    }

    protected function minifyCSS(string $css): string
    {
        // Xóa comment
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        // Chuyển newline và tab thành khoảng trắng
        $css = str_replace(["\r\n", "\r", "\n", "\t"], ' ', $css);
        // Xóa khoảng trắng quanh các ký tự đặc biệt (giữ dấu + để không lỗi hàm calc)
        $css = preg_replace('/\s*([\{\}:;,>~])\s*/', '$1', $css);
        // Xóa dấu chấm phẩy thừa trước dấu đóng ngoặc
        $css = str_replace(';}', '}', $css);
        // Rút gọn nhiều khoảng trắng liên tiếp
        $css = preg_replace('/\s+/', ' ', $css);
        return trim($css);
    }

    protected function minifyJS(string $js): string
    {
        // Nhận diện chuỗi (nháy đơn, nháy kép, template string backtick) để KHÔNG xóa nhầm /* */ hoặc // bên trong chuỗi
        $pattern = '/((?:\'[^\'\\\\]*(?:\\\\.[^\'\\\\]*)*\')|(?:"[^"\\\\]*(?:\\\\.[^"\\\\]*)*")|(?:`[^`\\\\]*(?:\\\\.[^`\\\\]*)*`))|(\/\*[\s\S]*?\*\/)|((?<!:)\/\/[^\n]*)/';
        $cleaned = preg_replace_callback($pattern, function ($matches) {
            // Nếu là chuỗi string literal (nhóm 1), giữ nguyên vẹn
            if (!empty($matches[1])) {
                return $matches[1];
            }
            // Nếu là comment (nhóm 2 hoặc 3), xóa bỏ
            return '';
        }, $js);

        // Tách dòng và loại bỏ dòng trống
        $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $cleaned));
        $cleanLines = [];
        foreach ($lines as $line) {
            $t = trim($line);
            if ($t !== '') {
                $cleanLines[] = $t;
            }
        }
        return implode("\n", $cleanLines);
    }
}
