<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Services\Media\FileHelperService;
use App\Services\Media\MediaAssignmentService;
use App\Services\Media\MediaOptimizerService;
use Illuminate\Http\Request;

class AdminMediaUploadController extends Controller
{
    protected array $folders = [];

    protected array $targets = [
        'product',
        'post',
        'category',
        'banner_desktop',
        'banner_mobile',
        'profile_avatar',
        'profile_sub_avatar',
    ];

    public function __construct()
    {
        $this->folders = config('media.directories', []);
    }

    public function store(
        Request $request,
        FileHelperService $files,
        MediaOptimizerService $optimizer,
        MediaAssignmentService $assignment
    ) {
        $validated = $request->validate([
            'folder' => 'required|in:' . implode(',', array_keys($this->folders)),
            'target_type' => 'required|in:' . implode(',', $this->targets),
            'target_id' => 'required|integer|min:1',
            'title' => 'nullable|string|max:255',
            'alt' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_primary' => 'nullable|boolean',
            'files' => 'required|array|min:1',
            'files.*' => 'file|mimes:jpg,jpeg,png,webp,gif|max:5120',
        ]);

        $results = [];
        foreach ($request->file('files', []) as $uploadedFile) {
            $stored = $files->storeUploadedFile($uploadedFile, $this->folders[$validated['folder']]);
            $variants = $optimizer->generateVariants($stored['absolute_path'], $stored['relative_path']);
            $paths = array_merge(['original' => $stored['relative_path']], $variants);
            $meta = [
                'title' => $validated['title'] ?? pathinfo($stored['filename'], PATHINFO_FILENAME),
                'alt' => $validated['alt'] ?? null,
                'description' => $validated['description'] ?? null,
                'is_primary' => (bool) ($validated['is_primary'] ?? false),
            ];

            $results[] = $assignment->assignUploadedFile(
                $validated['target_type'],
                (int) $validated['target_id'],
                $paths,
                $meta
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã upload và gán ' . count($results) . ' ảnh.',
            'items' => $results,
        ]);
    }
}


