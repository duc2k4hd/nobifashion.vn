<?php

use App\Models\Setting;
use Illuminate\Support\Facades\Auth;

if (!function_exists('renderMeta')) {
    function renderMeta($text) {
        // Nếu chưa có settings trong config thì nạp vào
        if (!config()->has('settings')) {
            try {
                // Query DB 1 lần
                $settings = Setting::pluck('value', 'key')->toArray();
                config(['settings' => $settings]);
            } catch (\Exception $e) {
                // Nếu DB chưa migrate hoặc lỗi thì fallback rỗng
                config(['settings' => []]);
            }
        }

        

        // Lấy subname từ config, fallback mặc định
        $shopName = config('settings.subname', 'NOBI FASHION');

        return str_replace(
            [
                        '[NOBI]currentyear[NOBI]',
                        '[NOBI]subname[NOBI]'
                    ],
            [
                        date('n') >= 11 ? date('Y') + 1 : date('Y'),
                        $shopName
                    ],
            $text
        );
    }
}

if (!function_exists('replaceYearsWithPlaceholder')) {
    /**
     * Thay thế các năm từ 2010-2026 thành [NOBI]currentyear[NOBI]
     */
    function replaceYearsWithPlaceholder(string $text): string
    {
        $currentYear = date('Y');
        $placeholder = '[NOBI]currentyear[NOBI]';
        
        // Thay thế các năm từ 2010-2026
        for ($year = 2010; $year <= 2026; $year++) {
            $text = preg_replace('/\b' . $year . '\b/', $placeholder, $text);
        }
        
        return $text;
    }
}

if (!function_exists('getPostThumbnailUrl')) {
    /**
     * Lấy URL ảnh thumbnail nhỏ gọn cho bài viết (mặc định 200px webp siêu nhẹ ~2-4KB)
     * Tự động sinh file thumbnail -200w.webp nếu chưa có
     */
    function getPostThumbnailUrl(?string $imagePath, int $targetWidth = 200): string
    {
        if (empty($imagePath)) {
            return asset('clients/assets/img/no-image.webp');
        }

        $filename = basename(parse_url($imagePath, PHP_URL_PATH) ?? $imagePath);
        $postsDir = public_path('clients/assets/img/posts');
        $origPath = $postsDir . DIRECTORY_SEPARATOR . $filename;

        if (!file_exists($origPath)) {
            return asset('clients/assets/img/no-image.webp');
        }

        $info = pathinfo($filename);
        $cleanBase = preg_replace('/-\d+w$/i', '', $info['filename']);
        $thumbName = $cleanBase . "-{$targetWidth}w.webp";
        $thumbPath = $postsDir . DIRECTORY_SEPARATOR . $thumbName;

        // Nếu file thumbnail 200w đã có, trả về ngay
        if (file_exists($thumbPath)) {
            return asset('clients/assets/img/posts/' . $thumbName);
        }

        // Tự động sinh thumbnail nhỏ gọn (200px) nếu chưa có
        try {
            $size = @getimagesize($origPath);
            if ($size && $size[0] > 0) {
                $origW = $size[0];
                $origH = $size[1];

                if ($origW <= $targetWidth) {
                    return asset('clients/assets/img/posts/' . $filename);
                }

                $newW = $targetWidth;
                $newH = (int) round($origH * ($newW / $origW));

                $type = $size[2] ?? 0;
                $img = match ($type) {
                    IMAGETYPE_JPEG => @imagecreatefromjpeg($origPath),
                    IMAGETYPE_PNG => @imagecreatefrompng($origPath),
                    IMAGETYPE_WEBP => @imagecreatefromwebp($origPath),
                    default => null,
                };

                if ($img) {
                    $canvas = imagecreatetruecolor($newW, $newH);
                    imagealphablending($canvas, false);
                    imagesavealpha($canvas, true);
                    imagecopyresampled($canvas, $img, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
                    @imagewebp($canvas, $thumbPath, 80);
                    @imagedestroy($canvas);
                    @imagedestroy($img);

                    if (file_exists($thumbPath)) {
                        return asset('clients/assets/img/posts/' . $thumbName);
                    }
                }
            }
        } catch (\Throwable $e) {
            // Tránh lỗi nếu GD gặp sự cố
        }

        // Nếu có bản 200w.webp đã tạo trước đó thì dùng tạm
        $fallback200 = $cleanBase . '-200w.webp';
        if (file_exists($postsDir . DIRECTORY_SEPARATOR . $fallback200)) {
            return asset('clients/assets/img/posts/' . $fallback200);
        }

        // Nếu có bản 800w.webp đã tạo trước đó thì dùng tạm
        $fallback800 = $cleanBase . '-800w.webp';
        if (file_exists($postsDir . DIRECTORY_SEPARATOR . $fallback800)) {
            return asset('clients/assets/img/posts/' . $fallback800);
        }

        return asset('clients/assets/img/posts/' . $filename);
    }
}

if (!function_exists('getResponsivePostImageUrl')) {
    /**
     * Lấy đường dẫn ảnh responsive (700w/800w & 1200w webp) cho ảnh bài viết
     */
    function getResponsivePostImageUrl(?string $imagePath, int $targetWidth = 700): array
    {
        if (empty($imagePath)) {
            return [
                'original' => null,
                'responsive' => null,
                'responsive1200' => null,
                'src' => null,
                'srcset' => null,
                'width' => 1200,
                'height' => 675,
            ];
        }

        $filename = basename(parse_url($imagePath, PHP_URL_PATH) ?? $imagePath);
        $postsDir = public_path('clients/assets/img/posts');
        $origPath = $postsDir . DIRECTORY_SEPARATOR . $filename;
        $info = pathinfo($filename);
        $cleanBase = preg_replace('/-\d+w$/i', '', $info['filename']);
        $origName = $filename;

        // URL gốc
        $origUrl = asset('clients/assets/img/posts/' . $origName);
        $responsive400Name = $cleanBase . '-400w.webp';
        $responsive400Path = $postsDir . DIRECTORY_SEPARATOR . $responsive400Name;
        $responsiveName = $cleanBase . '-' . $targetWidth . 'w.webp';
        $responsivePath = $postsDir . DIRECTORY_SEPARATOR . $responsiveName;
        $responsive800Name = $cleanBase . '-800w.webp';
        $responsive800Path = $postsDir . DIRECTORY_SEPARATOR . $responsive800Name;
        $responsive1200Name = $cleanBase . '-1200w.webp';
        $responsive1200Path = $postsDir . DIRECTORY_SEPARATOR . $responsive1200Name;

        // Kích thước chuẩn 16:9 mặc định (tránh giật layout CLS)
        $width = 1200;
        $height = 675;

        // Kiểm tra nhanh các file phái sinh đã có sẵn trên đĩa (Không resize blocking trong request)
        $has400 = file_exists($responsive400Path);
        $hasResponsive = file_exists($responsivePath);
        $has800 = file_exists($responsive800Path);
        $has1200 = file_exists($responsive1200Path);
        $hasOrig = file_exists($origPath);

        // Tự động tạo bản 400w siêu nhẹ cho Mobile nếu chưa có
        if (!$has400 && ($hasResponsive || $has800 || $hasOrig) && function_exists('imagewebp')) {
            $sourceFor400 = $hasResponsive ? $responsivePath : ($has800 ? $responsive800Path : $origPath);
            try {
                $sInfo = @getimagesize($sourceFor400);
                if ($sInfo && $sInfo[0] > 400) {
                    $sW = $sInfo[0];
                    $sH = $sInfo[1];
                    $tW = 400;
                    $tH = (int) round($sH * ($tW / $sW));
                    $sImg = match ($sInfo[2] ?? 0) {
                        IMAGETYPE_JPEG => @imagecreatefromjpeg($sourceFor400),
                        IMAGETYPE_PNG => @imagecreatefrompng($sourceFor400),
                        IMAGETYPE_WEBP => @imagecreatefromwebp($sourceFor400),
                        default => null,
                    };
                    if ($sImg) {
                        $canvas = imagecreatetruecolor($tW, $tH);
                        imagealphablending($canvas, false);
                        imagesavealpha($canvas, true);
                        imagecopyresampled($canvas, $sImg, 0, 0, 0, 0, $tW, $tH, $sW, $sH);
                        @imagewebp($canvas, $responsive400Path, 78);
                        @imagedestroy($canvas);
                        @imagedestroy($sImg);
                        $has400 = file_exists($responsive400Path);
                    }
                }
            } catch (\Throwable $e) {}
        }

        $responsive400Url = $has400 ? asset('clients/assets/img/posts/' . $responsive400Name) : null;
        $responsiveUrl = $hasResponsive ? asset('clients/assets/img/posts/' . $responsiveName) : null;
        $responsive800Url = $has800 ? asset('clients/assets/img/posts/' . $responsive800Name) : null;
        $responsive1200Url = $has1200 ? asset('clients/assets/img/posts/' . $responsive1200Name) : null;

        $srcsetParts = [];
        if ($responsive400Url) {
            $srcsetParts[] = "{$responsive400Url} 400w";
        }
        if ($responsiveUrl) {
            $srcsetParts[] = "{$responsiveUrl} {$targetWidth}w";
        }
        if ($responsive800Url) {
            $srcsetParts[] = "{$responsive800Url} 800w";
        }
        if ($responsive1200Url) {
            $srcsetParts[] = "{$responsive1200Url} 1200w";
        }
        if (!empty($srcsetParts) && $hasOrig) {
            $srcsetParts[] = "{$origUrl} 1200w";
        }

        $srcset = !empty($srcsetParts) ? implode(', ', $srcsetParts) : null;
        $src = $responsive400Url ?? $responsiveUrl ?? $responsive800Url ?? $responsive1200Url ?? $origUrl;

        return [
            'original' => $origUrl,
            'responsive400' => $responsive400Url,
            'responsive' => $responsiveUrl ?? $responsive800Url,
            'responsive1200' => $responsive1200Url,
            'src' => $src,
            'srcset' => $srcset,
            'width' => $width,
            'height' => $height,
        ];
    }
}

if (!function_exists('optimizePostHtml')) {
    /**
     * Tự động tối ưu tất cả thẻ <img> trong nội dung bài viết:
     * - Thêm loading="lazy" và decoding="async"
     * - Tự động bổ sung width / height chống layout shift (CLS = 0)
     * - Nâng cấp srcset và sizes responsive chuẩn 700w cho mobile & 808px cho desktop
     */
    function optimizePostHtml(string $html): string
    {
        if (empty($html)) {
            return $html;
        }

        return preg_replace_callback('/<img\b(?:[^>"\'\s]|\s(?![>])|"[^"]*"|\'[^\']*\')*>/is', function ($matches) {
            $tag = $matches[0];

            // 1. Đảm bảo loading="lazy"
            if (!preg_match('/\bloading\s*=/i', $tag)) {
                $tag = preg_replace('/<img\b/i', '<img loading="lazy"', $tag);
            }

            // 2. Đảm bảo decoding="async"
            if (!preg_match('/\bdecoding\s*=/i', $tag)) {
                $tag = preg_replace('/<img\b/i', '<img decoding="async"', $tag);
            }

            // 3. Chuẩn hóa & nâng cấp srcset / sizes (nếu thẻ img đang dùng sizes cũ hoặc chỉ có 800w)
            if (str_contains($tag, 'calc(100vw - 32px)') || str_contains($tag, '100vw, 800px') || (str_contains($tag, '-800w.webp') && !str_contains($tag, '-700w.webp'))) {
                $tag = preg_replace('/\s+srcset\s*=\s*(["\']).*?\1/is', '', $tag);
                $tag = preg_replace('/\s+sizes\s*=\s*(["\']).*?\1/is', '', $tag);
            }

            // 4. Trích xuất src
            if (preg_match('/\ssrc\s*=\s*["\']?([^"\'\s>]+)["\']?/i', $tag, $srcMatches)) {
                $src = trim($srcMatches[1], "\"'");
                $path = parse_url($src, PHP_URL_PATH) ?? '';

                if (str_contains($path, '/clients/assets/img/posts/')) {
                    $cleanFilename = preg_replace('/-\d+w(\.[a-zA-Z0-9]+)$/i', '$1', basename($path));
                    $imageData = getResponsivePostImageUrl($cleanFilename, 700);

                    // Thêm width & height nếu thiếu
                    if (!preg_match('/\bwidth\s*=/i', $tag) && !preg_match('/\bheight\s*=/i', $tag)) {
                        $w = $imageData['width'];
                        $h = $imageData['height'];
                        $tag = preg_replace('/<img\b/i', "<img width=\"{$w}\" height=\"{$h}\"", $tag);
                    }

                    // Thêm srcset nếu chưa có
                    if (!preg_match('/\bsrcset\s*=/i', $tag) && !empty($imageData['srcset'])) {
                        $srcset = $imageData['srcset'];
                        $sizes = '(max-width: 768px) calc(100vw - 14px), 808px';
                        $tag = preg_replace('/(\s*\/?>)$/', " srcset=\"{$srcset}\" sizes=\"{$sizes}\"$1", $tag);
                    }
                }
            }

            return $tag;
        }, $html);
    }
}

