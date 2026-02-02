<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Services\Media\MediaScannerService;
use Illuminate\Http\Request;

class AdminMediaSearchController extends Controller
{
    /**
     * Tìm kiếm media cho trang admin/media (AJAX).
     *
     * Route: GET /admin/media/search
     * Route name: admin.media.search
     */
    public function __invoke(Request $request, MediaScannerService $scanner)
    {
        $validated = $request->validate([
            'type' => 'nullable|string',
            'q' => 'nullable|string|max:255',
            'sort' => 'nullable|string|in:created_at,file_name',
            'direction' => 'nullable|string|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        // Gọi service để lấy danh sách media (LengthAwarePaginator)
        $paginator = $scanner->search($validated);

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }
}

