<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Services\Media\MediaAssignmentService;
use App\Services\Media\MediaCleanupService;
use App\Services\Media\MediaScannerService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminMediaController extends Controller
{
    protected array $deletableSources = [
        'product_image',
        'post_thumbnail',
        'category_image',
        'banner_desktop',
        'banner_mobile',
        'profile_avatar',
        'profile_sub_avatar',
        'library_image',
        'filesystem_file',
    ];

    public function index(MediaScannerService $scanner)
    {
        $stats = $scanner->getDashboardStats();
        $initial = $scanner->search([
            'per_page' => 50,
            'page' => 1,
        ]);

        $directories = collect(config('media.directories', []));
        $folders = $directories->map(function ($path, $key) {
            $label = Str::headline(str_replace('_', ' ', $key));
            $scope = str_starts_with($path, 'clients/') ? 'Frontend' : 'Admin';
            
            $realPath = public_path($path);
            $fileCount = 0;
            if (is_dir($realPath)) {
                // Sử dụng FilesystemIterator để đếm file trực tiếp ở tầng C/OS siêu nhanh
                // Tránh dùng File::files() vì nó nạp toàn bộ SplFileInfo của tất cả files vào RAM gây chậm web.
                $fi = new \FilesystemIterator($realPath, \FilesystemIterator::SKIP_DOTS);
                $fileCount = iterator_count($fi);
            }

            return [
                'key' => $key,
                'path' => $path,
                'label' => $label,
                'scope' => $scope,
                'file_count' => $fileCount,
            ];
        })->values();

        $typeFilters = ['all' => 'Tất cả loại'] + $scanner->getTypeLabels();
        $statusFilters = $scanner->getStatusLabels();

        $uploadTargets = [
            'product' => 'Sản phẩm',
            'post' => 'Bài viết',
            'category' => 'Danh mục',
            'banner_desktop' => 'Banner desktop',
            'banner_mobile' => 'Banner mobile',
            'profile_avatar' => 'Avatar người dùng',
            'profile_sub_avatar' => 'Ảnh phụ người dùng',
        ];

        return view('admins.media.index', [
            'stats' => $stats,
            'folders' => $folders,
            'folderLabels' => $scanner->getDirectoryLabels(),
            'typeFilters' => $typeFilters,
            'statusFilters' => $statusFilters,
            'uploadTargets' => $uploadTargets,
            'initialMedia' => $initial->items(),
            'initialPagination' => [
                'total' => $initial->total(),
                'per_page' => $initial->perPage(),
                'current_page' => $initial->currentPage(),
                'last_page' => $initial->lastPage(),
                'from' => $initial->firstItem(),
                'to' => $initial->lastItem(),
            ],
        ]);
    }

    public function update(Request $request, string $id, MediaAssignmentService $assignment)
    {
        $validated = $request->validate([
            'source' => 'required|in:product_image,post_thumbnail,category_image,banner_desktop,banner_mobile,profile_avatar,profile_sub_avatar,library_image',
            'title' => 'nullable|string|max:255',
            'alt' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_primary' => 'nullable|boolean',
        ]);

        $success = $assignment->updateMeta(
            $validated['source'],
            $id,
            [
                'title' => $validated['title'] ?? null,
                'alt' => $validated['alt'] ?? null,
                'description' => $validated['description'] ?? null,
                'is_primary' => array_key_exists('is_primary', $validated)
                    ? (bool) $validated['is_primary']
                    : null,
            ]
        );

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Đã lưu thông tin media.' : 'Không thể cập nhật media.',
        ], $success ? 200 : 400);
    }

    public function bulkDelete(
        Request $request,
        MediaScannerService $scanner,
        MediaAssignmentService $assignment
    ) {
        $maxDeleteItems = max(1, (int) config('media.request_limits.delete_items_per_request', 200));

        $validated = $request->validate([
            'items' => 'required|array|min:1|max:' . $maxDeleteItems,
            'items.*.source' => 'required|string',
            'items.*.id' => 'nullable|string',
            'items.*.path' => 'nullable|string',
        ]);

        $result = $assignment->deleteBatch($validated['items']);
        $deletedCount = $result['deleted_count'];
        $preservedFilesCount = $result['preserved_files_count'];
        $failedCount = $result['failed_count'];
        $failureMessages = $result['failure_messages'];

        $message = $deletedCount > 0
            ? "Đã xử lý {$deletedCount} mục media."
            : 'Không xóa được mục media nào.';

        if ($preservedFilesCount > 0) {
            $message .= " {$preservedFilesCount} file vật lý được giữ lại vì đang dùng chung.";
        }
        if (!empty($failureMessages)) {
            $message .= ' ' . $failureMessages[0];
        }

        return response()->json([
            'success' => $deletedCount > 0,
            'message' => $message,
            'deleted_count' => $deletedCount,
            'preserved_files_count' => $preservedFilesCount,
            'failed_count' => $failedCount,
        ], $deletedCount > 0 ? 200 : 400);
    }

    /**
     * Xóa nhanh hàng loạt media theo Scope (chia chunk tránh tốn RAM và timeout server)
     */
    public function fastDeleteScope(Request $request, MediaAssignmentService $assignment)
    {
        $validated = $request->validate([
            'scope' => 'required|string',
            'batch_size' => 'nullable|integer|min:50|max:2000',
        ]);

        $batchSize = $validated['batch_size'] ?? 1000;
        $result = $assignment->deleteScopeChunk($validated['scope'], $batchSize);

        return response()->json([
            'success' => true,
            'processed' => $result['processed'],
            'preserved_files_count' => $result['preserved_files_count'],
            'remaining' => $result['remaining'],
            'finished' => $result['finished'],
            'message' => "Đã xử lý {$result['processed']} ảnh. Còn lại {$result['remaining']} ảnh.",
        ]);
    }

    public function cleanup(Request $request, MediaCleanupService $cleanup)
    {
        $validated = $request->validate([
            'dry_run' => 'nullable|boolean',
        ]);

        $summary = ! empty($validated['dry_run'])
            ? $cleanup->preview()
            : $cleanup->cleanup();

        return response()->json($summary);
    }
}
