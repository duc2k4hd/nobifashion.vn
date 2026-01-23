<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Services\Media\MediaAssignmentService;
use Illuminate\Http\Request;

class AdminMediaDeleteController extends Controller
{
    public function __invoke(Request $request, string $id, MediaAssignmentService $assignment)
    {
        $validated = $request->validate([
            'source' => 'required|in:product_image,post_thumbnail,category_image,banner_desktop,banner_mobile,profile_avatar,profile_sub_avatar',
        ]);

        $success = $assignment->delete($validated['source'], $id);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Đã xoá media.' : 'Không thể xoá media.',
        ], $success ? 200 : 400);
    }
}


