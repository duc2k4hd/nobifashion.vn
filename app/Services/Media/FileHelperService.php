<?php

namespace App\Services\Media;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class FileHelperService
{
    /**
     * Lưu file upload vào thư mục public/uploads/...
     *
     * @return array{relative_path:string, absolute_path:string, filename:string, extension:string}
     */
    public function storeUploadedFile(UploadedFile $file, string $directory): array
    {
        $directory = trim($directory, '/');
        $sanitizedName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $finalName = sprintf(
            '%s-%s-%s.%s',
            $sanitizedName ?: 'media',
            now()->format('YmdHis'),
            Str::random(6),
            $extension
        );

        $relativePath = ($directory ? $directory . '/' : '') . $finalName;
        $absolutePath = public_path($relativePath);

        $this->ensureDirectory(dirname($absolutePath));
        $file->move(dirname($absolutePath), $finalName);
        @chmod($absolutePath, 0644);

        return [
            'relative_path' => $relativePath,
            'absolute_path' => $absolutePath,
            'filename' => $finalName,
            'extension' => $extension,
        ];
    }

    public function ensureDirectory(string $absoluteDirectory): void
    {
        if (!is_dir($absoluteDirectory)) {
            mkdir($absoluteDirectory, 0775, true);
        }
    }

    public function deleteFile(?string $relativePath): void
    {
        $absolute = $this->toAbsolutePath($relativePath);
        if ($absolute && is_file($absolute)) {
            @unlink($absolute);
        }
    }

    public function toAbsolutePath(?string $relativePath): ?string
    {
        if (!$relativePath) {
            return null;
        }

        $trimmed = ltrim($relativePath, '/');
        return public_path($trimmed);
    }

    public function fileExists(?string $relativePath): bool
    {
        $absolute = $this->toAbsolutePath($relativePath);
        return $absolute ? is_file($absolute) : false;
    }

    public function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}


