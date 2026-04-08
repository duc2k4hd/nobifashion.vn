<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Services\Media\FileHelperService;
use App\Services\Media\MediaAssignmentService;
use App\Services\Media\MediaScannerService;
use Illuminate\Http\Request;

class AdminMediaDeleteController extends Controller
{
    public function __invoke(
        Request $request,
        string $id,
        MediaAssignmentService $assignment,
        MediaScannerService $scanner,
        FileHelperService $files
    ) {
        $validated = $request->validate([
            'source' => 'required|in:product_image,post_thumbnail,category_image,banner_desktop,banner_mobile,profile_avatar,profile_sub_avatar,library_image',
        ]);

        $item = $scanner->findItem($validated['source'], $id);
        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy media.',
            ], 404);
        }

        $deletePhysical = !($item['is_shared'] ?? false);
        try {
            $success = $assignment->delete($validated['source'], $id, $deletePhysical);
        } catch (\DomainException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => $success,
            'message' => $success
                ? ($deletePhysical ? 'Đã xóa media.' : 'Đã xóa bản ghi media, file vật lý được giữ lại vì đang dùng chung.')
                : 'Không thể xóa media.',
        ], $success ? 200 : 400);
    }
}
