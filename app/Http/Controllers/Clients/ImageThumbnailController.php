<?php

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class ImageThumbnailController extends Controller
{
    /**
     * Tạo thumbnail động tối ưu hóa hiệu năng, giảm tải dung lượng theo chuẩn WebP/AVIF
     * Phục vụ Responsive Images và giải quyết triệt để cảnh báo Image Delivery trên PageSpeed
     *
     * @param string $size Định dạng: {width}x{height} (ví dụ: 325x434, 96x96)
     * @param string $filename Tên file ảnh gốc
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function thumb(Request $request, string $size, string $filename)
    {
        // 1. Whitelist kích thước được phép (Chặn triệt để Image Bombing / DoS ép server resize kích thước lạ)
        $allowedSizes = [
            '64x85'   => [64, 85],
            '128x170' => [128, 170],
            '325x434' => [325, 434],
            '96x96'   => [96, 96],
            '100x133' => [100, 133],
            '180x240' => [180, 240],
            '400x533' => [400, 533],
            '500x667' => [500, 667],
        ];

        if (!isset($allowedSizes[$size])) {
            abort(404);
        }

        [$targetW, $targetH] = $allowedSizes[$size];

        // 2. Làm sạch tên file chống Path Traversal
        $filename = basename($filename);
        $cleanName = pathinfo($filename, PATHINFO_FILENAME);

        $sourcePath = public_path('clients/assets/img/clothes/' . $filename);

        // Nếu file ảnh gốc với tên chính xác không tồn tại, thử tìm file cùng tên nhưng khác đuôi (.webp, .avif, .jpg, .png)
        if (!File::exists($sourcePath)) {
            $candidates = [
                public_path("clients/assets/img/clothes/{$cleanName}.webp"),
                public_path("clients/assets/img/clothes/{$cleanName}.avif"),
                public_path("clients/assets/img/clothes/{$cleanName}.jpg"),
                public_path("clients/assets/img/clothes/{$cleanName}.jpeg"),
                public_path("clients/assets/img/clothes/{$cleanName}.png"),
            ];
            foreach ($candidates as $cand) {
                if (File::exists($cand)) {
                    $sourcePath = $cand;
                    break;
                }
            }
        }

        // Nếu vẫn không có file gốc thì trả về no-image
        if (!File::exists($sourcePath)) {
            $fallback = public_path('clients/assets/img/no-image.webp');
            if (File::exists($fallback)) {
                return response()->file($fallback, [
                    'Content-Type' => 'image/webp',
                    'Cache-Control' => 'public, max-age=86400',
                ]);
            }
            abort(404);
        }

        // 3. Đường dẫn lưu cache thumbnail WebP
        $thumbDir = public_path("clients/assets/img/clothes/thumbs/{$size}");
        if (!File::isDirectory($thumbDir)) {
            File::makeDirectory($thumbDir, 0755, true, true);
        }
        $thumbPath = "{$thumbDir}/{$cleanName}.webp";

        // 4. Nếu thumbnail đã tồn tại trên đĩa thì stream trả về ngay (0ms)
        if (File::exists($thumbPath) && File::size($thumbPath) > 0) {
            return response()->file($thumbPath, [
                'Content-Type' => 'image/webp',
                'Cache-Control' => 'public, max-age=31536000, immutable',
            ]);
        }

        // 5. Nếu chưa có, sinh thumbnail tự động bằng GD
        try {
            $srcImg = null;
            $ext = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));

            if ($ext === 'webp' && function_exists('imagecreatefromwebp')) {
                $srcImg = @imagecreatefromwebp($sourcePath);
            } elseif ($ext === 'avif' && function_exists('imagecreatefromavif')) {
                $srcImg = @imagecreatefromavif($sourcePath);
            } elseif (in_array($ext, ['jpg', 'jpeg']) && function_exists('imagecreatefromjpeg')) {
                $srcImg = @imagecreatefromjpeg($sourcePath);
            } elseif ($ext === 'png' && function_exists('imagecreatefrompng')) {
                $srcImg = @imagecreatefrompng($sourcePath);
            }

            if (!$srcImg) {
                // Thử nhận diện theo mime type nếu đuôi file không khớp
                $info = @getimagesize($sourcePath);
                $mime = $info['mime'] ?? '';
                if ($mime === 'image/webp') $srcImg = @imagecreatefromwebp($sourcePath);
                elseif ($mime === 'image/avif') $srcImg = @imagecreatefromavif($sourcePath);
                elseif ($mime === 'image/jpeg') $srcImg = @imagecreatefromjpeg($sourcePath);
                elseif ($mime === 'image/png') $srcImg = @imagecreatefrompng($sourcePath);
            }

            if ($srcImg) {
                $origW = imagesx($srcImg);
                $origH = imagesy($srcImg);

                $dstImg = imagecreatetruecolor($targetW, $targetH);
                imagealphablending($dstImg, false);
                imagesavealpha($dstImg, true);

                // Giữ màu nền trắng hoặc trong suốt
                $transparent = imagecolorallocatealpha($dstImg, 255, 255, 255, 127);
                imagefilledrectangle($dstImg, 0, 0, $targetW, $targetH, $transparent);

                imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $targetW, $targetH, $origW, $origH);

                if (function_exists('imagewebp')) {
                    imagewebp($dstImg, $thumbPath, 82);
                }

                if (File::exists($thumbPath) && File::size($thumbPath) > 0) {
                    return response()->file($thumbPath, [
                        'Content-Type' => 'image/webp',
                        'Cache-Control' => 'public, max-age=31536000, immutable',
                    ]);
                }
            }
        } catch (\Throwable $e) {
            // Không làm gián đoạn người dùng nếu có ngoại lệ
        }

        // Fallback: Trả về ảnh gốc nếu không thể resize
        return response()->file($sourcePath, [
            'Cache-Control' => 'public, max-age=604800',
        ]);
    }

    /**
     * Tạo thumbnail cho logo thương hiệu (Brand logos)
     */
    public function brandThumb(Request $request, string $size, string $filename)
    {
        $allowedSizes = [
            '68x68' => [68, 68],
            '96x96' => [96, 96],
        ];

        if (!isset($allowedSizes[$size])) {
            abort(404);
        }

        [$targetW, $targetH] = $allowedSizes[$size];

        $filename = basename($filename);
        $cleanName = pathinfo($filename, PATHINFO_FILENAME);

        $sourcePath = public_path('clients/assets/img/brands/' . $filename);

        if (!File::exists($sourcePath)) {
            $candidates = [
                public_path("clients/assets/img/brands/{$cleanName}.webp"),
                public_path("clients/assets/img/brands/{$cleanName}.avif"),
                public_path("clients/assets/img/brands/{$cleanName}.jpg"),
                public_path("clients/assets/img/brands/{$cleanName}.jpeg"),
                public_path("clients/assets/img/brands/{$cleanName}.png"),
            ];
            foreach ($candidates as $cand) {
                if (File::exists($cand)) {
                    $sourcePath = $cand;
                    break;
                }
            }
        }

        if (!File::exists($sourcePath)) {
            $fallback = public_path('clients/assets/img/clothes/no-image.webp');
            return response()->file($fallback, [
                'Content-Type' => 'image/webp',
                'Cache-Control' => 'public, max-age=86400',
            ]);
        }

        $cacheDir = public_path("clients/assets/img/brands/thumbs/{$size}");
        if (!File::isDirectory($cacheDir)) {
            File::makeDirectory($cacheDir, 0755, true, true);
        }

        $thumbPath = "{$cacheDir}/{$cleanName}.webp";

        if (File::exists($thumbPath) && File::size($thumbPath) > 0) {
            return response()->file($thumbPath, [
                'Content-Type' => 'image/webp',
                'Cache-Control' => 'public, max-age=31536000, immutable',
            ]);
        }

        try {
            $srcImg = null;
            $ext = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));

            if ($ext === 'webp' && function_exists('imagecreatefromwebp')) {
                $srcImg = @imagecreatefromwebp($sourcePath);
            } elseif ($ext === 'avif' && function_exists('imagecreatefromavif')) {
                $srcImg = @imagecreatefromavif($sourcePath);
            } elseif (in_array($ext, ['jpg', 'jpeg']) && function_exists('imagecreatefromjpeg')) {
                $srcImg = @imagecreatefromjpeg($sourcePath);
            } elseif ($ext === 'png' && function_exists('imagecreatefrompng')) {
                $srcImg = @imagecreatefrompng($sourcePath);
            }

            if (!$srcImg) {
                $info = @getimagesize($sourcePath);
                $mime = $info['mime'] ?? '';
                if ($mime === 'image/webp') $srcImg = @imagecreatefromwebp($sourcePath);
                elseif ($mime === 'image/avif') $srcImg = @imagecreatefromavif($sourcePath);
                elseif ($mime === 'image/jpeg') $srcImg = @imagecreatefromjpeg($sourcePath);
                elseif ($mime === 'image/png') $srcImg = @imagecreatefrompng($sourcePath);
            }

            if ($srcImg) {
                $origW = imagesx($srcImg);
                $origH = imagesy($srcImg);

                $dstImg = imagecreatetruecolor($targetW, $targetH);
                imagealphablending($dstImg, false);
                imagesavealpha($dstImg, true);

                $transparent = imagecolorallocatealpha($dstImg, 255, 255, 255, 127);
                imagefilledrectangle($dstImg, 0, 0, $targetW, $targetH, $transparent);

                imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $targetW, $targetH, $origW, $origH);

                if (function_exists('imagewebp')) {
                    imagewebp($dstImg, $thumbPath, 85);
                }

                if (File::exists($thumbPath) && File::size($thumbPath) > 0) {
                    return response()->file($thumbPath, [
                        'Content-Type' => 'image/webp',
                        'Cache-Control' => 'public, max-age=31536000, immutable',
                    ]);
                }
            }
        } catch (\Throwable $e) {
        }

        return response()->file($sourcePath, [
            'Cache-Control' => 'public, max-age=604800',
        ]);
    }
}
