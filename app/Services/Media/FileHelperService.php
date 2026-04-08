<?php

namespace App\Services\Media;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use RuntimeException;

class FileHelperService
{
    /**
     * Cache các thư mục đã fix quyền để không chạy attrib nhiều lần
     */
    private static array $checkedDirectories = [];

    /**
     * Lưu file upload vào thư mục public/uploads/...
     *
     * @return array{relative_path:string, absolute_path:string, filename:string, extension:string}
     */
    public function storeUploadedFile(UploadedFile $file, string $directory): array
    {
        $directory = trim($directory, '/');
        $finalName = $this->sanitizeOriginalFilename($file);
        $extension = strtolower(pathinfo($finalName, PATHINFO_EXTENSION) ?: ($file->getClientOriginalExtension() ?: 'jpg'));

        $relativePath = ($directory ? $directory . '/' : '') . $finalName;
        $absolutePath = public_path($relativePath);
        $destinationDir = dirname($absolutePath);

        $this->ensureDirectory($destinationDir);
        $this->ensureWritableDirectory($destinationDir);

        // Kiểm tra disk space trước khi copy
        $requiredSpace = $file->getSize() * 1.1; // 10% buffer
        $this->checkDiskSpace($destinationDir, $requiredSpace);

        // Xóa file cũ nếu tồn tại (replace)
        if (is_file($absolutePath)) {
            if (!@unlink($absolutePath)) {
                \Log::warning('Failed to delete existing file', ['path' => $absolutePath]);
            }
        }

        $file->move($destinationDir, $finalName);
        @chmod($absolutePath, 0644);

        return [
            'relative_path' => $relativePath,
            'absolute_path' => $absolutePath,
            'filename' => $finalName,
            'extension' => $extension,
        ];
    }

    private function sanitizeOriginalFilename(UploadedFile $file): string
    {
        $originalName = trim((string) $file->getClientOriginalName());
        $originalName = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $originalName);
        $originalName = basename($originalName);

        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);

        $baseName = preg_replace('/[\x00-\x1F\x7F]/u', '', (string) $baseName);
        $baseName = trim((string) $baseName, ". \t\n\r\0\x0B");

        if ($baseName === '') {
            $baseName = 'media';
        }

        $extension = trim((string) $extension);
        if ($extension === '') {
            $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        }

        $extension = preg_replace('/[^A-Za-z0-9]+/', '', $extension) ?: 'jpg';

        return $baseName . '.' . strtolower($extension);
    }

    public function ensureDirectory(string $absoluteDirectory): void
    {
        if (!is_dir($absoluteDirectory)) {
            @mkdir($absoluteDirectory, 0775, true);
        }

        if (!is_dir($absoluteDirectory)) {
            throw new RuntimeException("Không thể tạo thư mục đích '{$absoluteDirectory}'.");
        }

        $this->ensureWritableDirectory($absoluteDirectory);
    }

    public function deleteFile(?string $relativePath): void
    {
        $absolute = $this->toAbsolutePath($relativePath);
        if ($absolute && is_file($absolute)) {
            @unlink($absolute);
        }
    }

    public function deleteManagedFile(?string $relativePath, array $directories = []): bool
    {
        $normalized = $this->normalizeRelativePath($relativePath);
        if (!$normalized || !$this->isManagedMediaPath($normalized, $directories)) {
            return false;
        }

        $absolute = $this->toAbsolutePath($normalized);
        if (!$absolute || !is_file($absolute)) {
            return false;
        }

        @unlink($absolute);

        return !is_file($absolute);
    }

    public function toAbsolutePath(?string $relativePath): ?string
    {
        if (!$relativePath) {
            return null;
        }

        $trimmed = $this->normalizeRelativePath($relativePath);
        return public_path($trimmed);
    }

    public function normalizeRelativePath(?string $relativePath): ?string
    {
        if (!$relativePath) {
            return null;
        }

        $normalized = str_replace('\\', '/', trim($relativePath));
        if ($normalized === '') {
            return null;
        }

        if (Str::startsWith($normalized, ['http://', 'https://'])) {
            return $normalized;
        }

        $normalized = ltrim($normalized, '/');
        if (Str::startsWith($normalized, 'public/')) {
            $normalized = substr($normalized, 7);
        }

        return trim($normalized, '/');
    }

    public function absoluteToRelativePath(string $absolutePath): ?string
    {
        $publicRoot = str_replace('\\', '/', public_path());
        $normalized = str_replace('\\', '/', $absolutePath);

        if (!Str::startsWith($normalized, $publicRoot)) {
            return null;
        }

        return ltrim(Str::after($normalized, $publicRoot), '/');
    }

    public function isManagedMediaPath(?string $relativePath, array $directories = []): bool
    {
        $normalized = $this->normalizeRelativePath($relativePath);
        if (!$normalized || Str::startsWith($normalized, ['http://', 'https://'])) {
            return false;
        }

        $directories = $directories ?: config('media.directories', []);

        foreach ($directories as $directory) {
            $prefix = trim(str_replace('\\', '/', $directory), '/');
            if ($normalized === $prefix || Str::startsWith($normalized, $prefix . '/')) {
                return true;
            }
        }

        return false;
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

    private function ensureWritableDirectory(string $absoluteDirectory): void
    {
        // Nếu đã check quyền folder này rồi, bỏ qua
        $dirCheckKey = realpath($absoluteDirectory) ?: $absoluteDirectory;
        if (isset(self::$checkedDirectories[$dirCheckKey])) {
            return;
        }

        clearstatcache(true, $absoluteDirectory);

        if (is_writable($absoluteDirectory)) {
            self::$checkedDirectories[$dirCheckKey] = true;
            return;
        }

        // Folder không writable, thử fix quyền
        @chmod($absoluteDirectory, 0775);

        if (PHP_OS_FAMILY === 'Windows') {
            $normalized = str_replace('/', DIRECTORY_SEPARATOR, $absoluteDirectory);
            // Chỉ chạy attrib khi nested permission fail
            if (!is_writable($absoluteDirectory)) {
                @exec('attrib -R "' . $normalized . '" /S /D');
            }
        }

        clearstatcache(true, $absoluteDirectory);

        if (!is_writable($absoluteDirectory)) {
            throw new RuntimeException(
                "Thư mục '{$absoluteDirectory}' không có quyền ghi. " .
                "Hãy check permission (chmod 775) hoặc gỡ readline attribute (Windows)."
            );
        }

        self::$checkedDirectories[$dirCheckKey] = true;
    }

    private function checkDiskSpace(string $directory, int $requiredBytes): void
    {
        $freeSpace = disk_free_space($directory);

        if ($freeSpace === false) {
            // Không thể check disk space, skip (có thể file system không support)
            \Log::debug('Cannot check disk space', ['directory' => $directory]);
            return;
        }

        if ($freeSpace < $requiredBytes) {
            $required = $this->formatBytes($requiredBytes);
            $available = $this->formatBytes($freeSpace);
            throw new RuntimeException(
                "Không đủ không gian trên ổ cứng. " .
                "Cần: {$required}, Còn trống: {$available}."
            );
        }
    }
}

