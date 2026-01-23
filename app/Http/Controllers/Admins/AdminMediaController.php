<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Services\Media\MediaAssignmentService;
use App\Services\Media\MediaScannerService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminMediaController extends Controller
{
    public function index(MediaScannerService $scanner)
    {
        $stats = $scanner->getDashboardStats();
        $initial = $scanner->search([
            'per_page' => 24,
            'page' => 1,
        ]);

        $filters = [
            'all' => 'Tất cả',
            'product_image' => 'Ảnh sản phẩm',
            'post_thumbnail' => 'Ảnh bài viết',
            'category_image' => 'Ảnh danh mục',
            'banner_desktop' => 'Banner Desktop',
            'banner_mobile' => 'Banner Mobile',
            'profile_avatar' => 'Avatar',
            'profile_sub_avatar' => 'Avatar phụ',
        ];

        $uploadTargets = [
            'product' => 'Sản phẩm',
            'post' => 'Bài viết',
            'category' => 'Danh mục',
            'banner_desktop' => 'Banner Desktop',
            'banner_mobile' => 'Banner Mobile',
            'profile_avatar' => 'Avatar người dùng',
            'profile_sub_avatar' => 'Ảnh phụ người dùng',
        ];

        $directories = collect(config('media.directories', []));
        $folders = $directories->map(function ($path, $key) {
            $label = Str::headline(str_replace('_', ' ', $key));
            $scope = str_starts_with($path, 'clients/') ? 'Giao diện khách' : 'Admin';

            return [
                'key' => $key,
                'path' => $path,
                'label' => $label,
                'scope' => $scope,
            ];
        });

        return view('admins.media.index', [
            'stats' => $stats,
            'filters' => $filters,
            'uploadTargets' => $uploadTargets,
            'folders' => $folders,
            'typeLabels' => $scanner->getTypeLabels(),
            'folderLabels' => $scanner->getDirectoryLabels(),
            'initialMedia' => $initial->items(),
            'initialPagination' => [
                'total' => $initial->total(),
                'per_page' => $initial->perPage(),
                'current_page' => $initial->currentPage(),
                'last_page' => $initial->lastPage(),
            ],
        ]);
    }

    public function update(Request $request, string $id, MediaAssignmentService $assignment)
    {
        $validated = $request->validate([
            'source' => 'required|in:product_image,post_thumbnail,category_image,banner_desktop,banner_mobile,profile_avatar,profile_sub_avatar',
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
                'is_primary' => isset($validated['is_primary']) ? (bool) $validated['is_primary'] : null,
            ]
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => $success,
                'message' => $success ? 'Đã lưu thông tin media.' : 'Không thể cập nhật media.',
            ], $success ? 200 : 400);
        }

        return redirect()->back()->with(
            $success ? 'success' : 'error',
            $success ? 'Đã cập nhật media.' : 'Không thể cập nhật media.'
        );
    }
}


