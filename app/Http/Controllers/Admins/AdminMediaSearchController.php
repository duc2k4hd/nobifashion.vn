<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Services\Media\MediaScannerService;
use Illuminate\Http\Request;

class AdminMediaSearchController extends Controller
{
    public function __invoke(Request $request, MediaScannerService $scanner)
    {
        $validated = $request->validate([
            'type' => 'nullable|string',
            'q' => 'nullable|string|max:255',
            'sort' => 'nullable|in:created_at,file_name,entity_id',
            'direction' => 'nullable|in:asc,desc',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $results = $scanner->search($validated);

        return response()->json([
            'data' => $results->items(),
            'meta' => [
                'total' => $results->total(),
                'per_page' => $results->perPage(),
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
            ],
        ]);
    }
}


