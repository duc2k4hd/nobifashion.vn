<?php

namespace App\Services;

use App\Models\Post;
use Illuminate\Support\Facades\Log;

class PostResponsiveImageService
{
    protected string $postsDir;
    protected int $targetWidth = 700;
    protected int $quality = 80;

    public function __construct()
    {
        $this->postsDir = public_path('clients/assets/img/posts');
    }

    /**
     * Xử lý tạo ảnh responsive và cập nhật srcset cho một bài viết cụ thể
     */
    public function processPost(Post $post): void
    {
        try {
            if (!is_dir($this->postsDir)) {
                return;
            }

            // 1. Tối ưu ảnh đại diện (Thumbnail)
            if (!empty($post->thumbnail)) {
                $this->processThumbnail($post->thumbnail);
            }

            // 2. Tối ưu các ảnh trong nội dung bài viết và gắn srcset
            if (!empty($post->content) && str_contains($post->content, '<img')) {
                $newContent = $this->processContent($post->content);
                if ($newContent !== $post->content) {
                    $post->updateQuietly(['content' => $newContent]);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Lỗi khi tự động tạo ảnh responsive cho bài viết ID ' . $post->id . ': ' . $e->getMessage(), [
                'post_id' => $post->id,
                'exception' => $e,
            ]);
        }
    }

    /**
     * Tạo các bản WebP responsive cho Thumbnail bài viết: 120w, 200w, 400w, 700w, 1200w
     */
    public function processThumbnail(string $thumbnail): void
    {
        $filename = basename(parse_url($thumbnail, PHP_URL_PATH) ?? $thumbnail);
        if (empty($filename)) {
            return;
        }

        $fullPath = $this->postsDir . DIRECTORY_SEPARATOR . $filename;
        $info = pathinfo($filename);
        $cleanBase = preg_replace('/-\d+w$/i', '', $info['filename']);

        // Danh sách các kích thước cần thiết theo chuẩn PageSpeed
        $targets = [
            120 => "{$cleanBase}-120w.webp",
            200 => "{$cleanBase}-200w.webp",
            400 => "{$cleanBase}-400w.webp",
            700 => "{$cleanBase}-700w.webp",
            1200 => "{$cleanBase}-1200w.webp",
        ];

        $p800 = $this->postsDir . DIRECTORY_SEPARATOR . $cleanBase . '-800w.webp';

        foreach ($targets as $width => $targetFilename) {
            $targetPath = $this->postsDir . DIRECTORY_SEPARATOR . $targetFilename;
            if (file_exists($targetPath)) {
                continue; // Bỏ qua O(1) tức thì nếu đã có
            }

            // Tận dụng bản 800w nếu có để nén nhanh hơn
            $sourceFile = (file_exists($p800) && $width < 800) ? $p800 : $fullPath;
            if (!file_exists($sourceFile)) {
                continue;
            }

            $this->resizeImage($sourceFile, $targetPath, $width, $this->quality);
        }
    }

    /**
     * Quét các thẻ <img> trong nội dung, tạo bản 700w và chèn srcset, sizes
     */
    public function processContent(string $content): string
    {
        // Loại bỏ sạch thuộc tính rác data-list-item-id do CKEditor 5 sinh ra trên thẻ <li>
        $content = preg_replace('/\s*data-list-item-id="[^"]*"/i', '', $content);

        return preg_replace_callback('/<img\b(?:[^>"\'\s]|\s(?![>])|"[^"]*"|\'[^\']*\')*>/is', function ($matches) {
            $tag = $matches[0];

            // Lấy đường dẫn src
            if (!preg_match('/\ssrc\s*=\s*["\']?([^"\'\s>]+)["\']?/i', $tag, $m)) {
                return $tag;
            }

            $src = trim($m[1], "\"'");
            $path = parse_url($src, PHP_URL_PATH);
            if (!$path || !str_contains($path, '/clients/assets/img/posts/')) {
                return $tag;
            }

            $filename = basename($path);
            $cleanFilename = preg_replace('/-\d+w(\.[a-zA-Z0-9]+)$/i', '$1', $filename);
            $info = pathinfo($cleanFilename);
            $cleanBase = $info['filename'];

            $resp700Name = "{$cleanBase}-700w.webp";
            $resp700Path = $this->postsDir . DIRECTORY_SEPARATOR . $resp700Name;
            $fullPath = $this->postsDir . DIRECTORY_SEPARATOR . $filename;
            $p800 = $this->postsDir . DIRECTORY_SEPARATOR . $cleanBase . '-800w.webp';
            $sourceFile = (file_exists($p800)) ? $p800 : $fullPath;

            // Tự động tạo bản 700w nếu chưa có
            if (!file_exists($resp700Path) && file_exists($sourceFile)) {
                $this->resizeImage($sourceFile, $resp700Path, $this->targetWidth, $this->quality);
            }

            // Nếu có bản 700w (hoặc vừa tạo xong), tiến hành chèn srcset/sizes
            if (file_exists($resp700Path)) {
                // Làm sạch srcset và sizes cũ nếu có
                $tag = preg_replace('/\s+srcset\s*=\s*(["\']).*?\1/is', '', $tag);
                $tag = preg_replace('/\s+sizes\s*=\s*(["\']).*?\1/is', '', $tag);

                $candidates = [];
                $candidates[] = "/clients/assets/img/posts/{$resp700Name} {$this->targetWidth}w";

                $resp800Name = "{$cleanBase}-800w.webp";
                if (file_exists($this->postsDir . DIRECTORY_SEPARATOR . $resp800Name)) {
                    $candidates[] = "/clients/assets/img/posts/{$resp800Name} 800w";
                }

                $resp1200Name = "{$cleanBase}-1200w.webp";
                if (file_exists($this->postsDir . DIRECTORY_SEPARATOR . $resp1200Name)) {
                    $candidates[] = "/clients/assets/img/posts/{$resp1200Name} 1200w";
                } else {
                    $candidates[] = "/clients/assets/img/posts/{$cleanFilename} 1200w";
                }

                $srcsetStr = implode(', ', $candidates);
                $srcsetAttr = " srcset=\"{$srcsetStr}\" sizes=\"(max-width: 768px) calc(100vw - 14px), 808px\"";

                if (!str_contains($tag, 'loading=')) {
                    $srcsetAttr .= ' loading="lazy"';
                }
                if (!str_contains($tag, 'decoding=')) {
                    $srcsetAttr .= ' decoding="async"';
                }

                return preg_replace('/(\s*\/?>)$/', $srcsetAttr . '$1', $tag);
            }

            return $tag;
        }, $content);
    }

    /**
     * Nén và resize ảnh sang WebP tối ưu bằng GD
     */
    public function resizeImage(string $sourceFile, string $targetPath, int $targetW, int $quality = 80): bool
    {
        if (file_exists($targetPath)) {
            return true;
        }

        if (!file_exists($sourceFile)) {
            return false;
        }

        $size = @getimagesize($sourceFile);
        if (!$size || $size[0] <= $targetW) {
            return false;
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
            return false;
        }

        $canvas = imagecreatetruecolor($newW, $newH);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);

        imagecopyresampled($canvas, $img, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
        $result = @imagewebp($canvas, $targetPath, $quality);

        imagedestroy($canvas);
        imagedestroy($img);

        return (bool) $result;
    }
}
