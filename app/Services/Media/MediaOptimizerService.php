<?php

namespace App\Services\Media;

use Illuminate\Support\Str;

class MediaOptimizerService
{
    protected FileHelperService $files;

    public function __construct(FileHelperService $files)
    {
        $this->files = $files;
    }

    /**
     * Tạo các phiên bản thumbnail / medium / webp cho ảnh đã upload.
     *
     * @return array{thumbnail?:string, medium?:string, webp?:string}
     */
    public function generateVariants(string $absolutePath, string $relativePath): array
    {
        $info = pathinfo($relativePath);
        $dir = ($info['dirname'] ?? '') === '.' ? 'uploads' : $info['dirname'];
        $basename = $info['filename'] ?? Str::random(8);
        $extension = strtolower($info['extension'] ?? 'jpg');

        $variants = [];

        $thumbnailRelative = $dir . '/' . $basename . '-thumb.' . $extension;
        if ($this->resize($absolutePath, public_path($thumbnailRelative), 320, 320)) {
            $variants['thumbnail'] = $thumbnailRelative;
        }

        $mediumRelative = $dir . '/' . $basename . '-medium.' . $extension;
        if ($this->resize($absolutePath, public_path($mediumRelative), 1024, 1024)) {
            $variants['medium'] = $mediumRelative;
        }

        $webpRelative = $dir . '/' . $basename . '.webp';
        if ($this->convertToWebp($absolutePath, public_path($webpRelative))) {
            $variants['webp'] = $webpRelative;
        }

        return $variants;
    }

    protected function resize(string $sourcePath, string $targetPath, int $maxWidth, int $maxHeight): bool
    {
        if (!is_file($sourcePath)) {
            return false;
        }

        [$width, $height, $type] = getimagesize($sourcePath);
        if (!$width || !$height) {
            return false;
        }

        $ratio = min($maxWidth / $width, $maxHeight / $height, 1);
        $newWidth = (int) round($width * $ratio);
        $newHeight = (int) round($height * $ratio);

        $createFunc = $this->getCreateFunction($type);
        $saveFunc = $this->getSaveFunction($type);

        if (!$createFunc || !$saveFunc) {
            return false;
        }

        $source = @$createFunc($sourcePath);
        if (!$source) {
            return false;
        }

        $this->files->ensureDirectory(dirname($targetPath));
        $canvas = imagecreatetruecolor($newWidth, $newHeight);

        if (in_array($type, [IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
        }

        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        $saveResult = @$saveFunc($canvas, $targetPath);

        imagedestroy($canvas);
        imagedestroy($source);

        return (bool) $saveResult;
    }

    protected function convertToWebp(string $sourcePath, string $targetPath): bool
    {
        if (!function_exists('imagewebp')) {
            return false;
        }

        [$width, $height, $type] = getimagesize($sourcePath);
        $createFunc = $this->getCreateFunction($type);
        if (!$createFunc) {
            return false;
        }

        $image = @$createFunc($sourcePath);
        if (!$image) {
            return false;
        }

        $this->files->ensureDirectory(dirname($targetPath));
        $result = imagewebp($image, $targetPath, 80);
        imagedestroy($image);

        return (bool) $result;
    }

    protected function getCreateFunction(int $imageType): ?callable
    {
        return match ($imageType) {
            IMAGETYPE_JPEG => 'imagecreatefromjpeg',
            IMAGETYPE_PNG => 'imagecreatefrompng',
            IMAGETYPE_GIF => 'imagecreatefromgif',
            IMAGETYPE_WEBP => function (string $path) {
                return function_exists('imagecreatefromwebp') ? imagecreatefromwebp($path) : null;
            },
            default => null,
        };
    }

    protected function getSaveFunction(int $imageType): ?callable
    {
        return match ($imageType) {
            IMAGETYPE_JPEG => function ($resource, $path) {
                return imagejpeg($resource, $path, 85);
            },
            IMAGETYPE_PNG => 'imagepng',
            IMAGETYPE_GIF => 'imagegif',
            IMAGETYPE_WEBP => function ($resource, $path) {
                return function_exists('imagewebp') ? imagewebp($resource, $path, 80) : false;
            },
            default => null,
        };
    }
}


