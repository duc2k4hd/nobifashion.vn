<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Services\Media\FileHelperService;
use App\Services\Media\MediaAssignmentService;
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

            return [
                'key' => $key,
                'path' => $path,
                'label' => $label,
                'scope' => $scope,
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
        MediaAssignmentService $assignment,
        FileHelperService $files
    ) {
        $validated = $request->validate([
            'items' => 'required|array|min:1|max:200',
            'items.*.source' => 'required|string',
            'items.*.id' => 'nullable|string',
            'items.*.path' => 'nullable|string',
        ]);

        $deletedCount = 0;
        $preservedFilesCount = 0;
        $failed = [];
        $failureMessages = [];

        foreach ($validated['items'] as $itemPayload) {
            $source = $itemPayload['source'];
            if (!in_array($source, $this->deletableSources, true)) {
                $failed[] = $itemPayload;
                continue;
            }

            $item = $scanner->findItem(
                $source,
                $itemPayload['id'] ?? null,
                $itemPayload['path'] ?? null
            );

            if (!$item) {
                $failed[] = $itemPayload;
                continue;
            }

            if ($source === 'filesystem_file') {
                $success = $files->deleteManagedFile(
                    $item['relative_path'] ?? null,
                    config('media.directories', [])
                );
            } else {
                $deletePhysical = !($item['is_shared'] ?? false);
                try {
                    $success = $assignment->delete(
                        $source,
                        (string) $item['delete_id'],
                        $deletePhysical
                    );
                } catch (\DomainException $exception) {
                    $success = false;
                    $failureMessages[] = $exception->getMessage();
                }

                if ($success && !$deletePhysical) {
                    $preservedFilesCount++;
                }
            }

            if ($success) {
                $deletedCount++;
            } else {
                $failed[] = $itemPayload;
            }
        }

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
            'failed_count' => count($failed),
        ], $deletedCount > 0 ? 200 : 400);
    }
}
