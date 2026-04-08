<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Services\Media\FileHelperService;
use App\Services\Media\ImageRegistryService;
use Illuminate\Http\Request;
use Throwable;

class AdminMediaUploadController extends Controller
{
    protected array $folders = [];

    public function __construct()
    {
        $this->folders = config('media.directories', []);
    }

    public function store(
        Request $request,
        FileHelperService $files,
        ImageRegistryService $registry
    ) {
        $validated = $request->validate([
            'folder' => 'required|in:' . implode(',', array_keys($this->folders)),
            'files' => 'required|array|min:1',
            'files.*' => 'file|extensions:jpg,jpeg,png,webp,gif,avif|max:5120',
        ]);

        $uploadedCount = 0;
        $failedCount = 0;
        $failedFiles = [];
        $results = [];

        $uploadedFiles = $request->file('files', []);
        $folder = $this->folders[$validated['folder']];

        \Log::info('Media upload started', [
            'folder' => $validated['folder'],
            'file_count' => count($uploadedFiles),
        ]);

        foreach ($uploadedFiles as $uploadedFile) {
            $filename = $uploadedFile->getClientOriginalName();

            try {
                // Kiểm tra file hợp lệ
                if (!$uploadedFile->isValid()) {
                    throw new \Exception("File '{$filename}' không hợp lệ: " . $uploadedFile->getErrorMessage());
                }

                // Store file
                $stored = $files->storeUploadedFile($uploadedFile, $folder);

                // Register image trong database
                $image = $registry->registerLooseImage(
                    $stored['relative_path'],
                    [
                        'title' => pathinfo($filename, PATHINFO_FILENAME),
                        'alt' => pathinfo($filename, PATHINFO_FILENAME),
                    ],
                    $validated['folder']
                );

                $results[] = [
                    'id' => $image->id,
                    'path' => $image->path,
                    'type' => 'library_image',
                ];

                $uploadedCount++;

                \Log::debug('Media file uploaded', [
                    'filename' => $filename,
                    'path' => $stored['relative_path'],
                    'size' => $uploadedFile->getSize(),
                    'image_id' => $image->id,
                ]);
            } catch (Throwable $exception) {
                $failedCount++;
                $failedFiles[] = [
                    'name' => $filename,
                    'error' => $exception->getMessage(),
                ];

                \Log::warning('Media file upload failed', [
                    'filename' => $filename,
                    'error' => $exception->getMessage(),
                    'exception' => get_class($exception),
                ]);

                report($exception);
            }
        }

        \Log::info('Media upload completed', [
            'uploaded' => $uploadedCount,
            'failed' => $failedCount,
            'total' => count($uploadedFiles),
        ]);

        // Nếu có ít nhất 1 file upload thành công, trả 200
        // Nếu toàn bộ fail hoặc không có file nào, trả 422
        $statusCode = $uploadedCount > 0 ? 200 : 422;

        return response()->json([
            'success' => $failedCount === 0 && $uploadedCount > 0,
            'message' => $this->buildUploadMessage($uploadedCount, $failedCount, count($uploadedFiles)),
            'uploaded_count' => $uploadedCount,
            'failed_count' => $failedCount,
            'failed_files' => $failedFiles,
            'items' => $results,
        ], $statusCode);
    }

    protected function buildUploadMessage(int $uploaded, int $failed, int $total): string
    {
        $parts = [];

        if ($uploaded > 0) {
            $parts[] = "Đã upload thành công {$uploaded} ảnh.";
        }

        if ($failed > 0) {
            $parts[] = "{$failed} ảnh upload thất bại.";
        }

        if (empty($parts)) {
            return "Không có file nào được upload.";
        }

        return implode(' ', $parts);
    }
}
