<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Models\Image;
use App\Services\Media\ImageRegistryService;
use App\Services\Media\MediaAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MediaLibraryController extends Controller
{
    protected string $basePath;

    public function __construct()
    {
        $this->basePath = public_path('clients/assets/img');
        $this->middleware(['admin']);
    }

    public function index(Request $request)
    {
        $context = $request->input('context', 'product');
        $page = max(1, (int) $request->input('page', 1));
        $perPage = min(200, max(1, (int) $request->input('per_page', 100)));
        $search = trim((string) $request->input('search', ''));

        $query = Image::forContext($context)->latest('created_at');

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('title', 'like', '%' . $search . '%')
                    ->orWhere('alt', 'like', '%' . $search . '%')
                    ->orWhere('path', 'like', '%' . $search . '%');
            });
        }

        $total = $query->count();
        $offset = ($page - 1) * $perPage;
        $files = $query->skip($offset)->take($perPage)->get();
        $hasMore = ($offset + $perPage) < $total;

        $data = $files->map(fn (Image $image) => [
            'id' => (string) $image->id,
            'name' => $image->name,
            'title' => $image->title,
            'alt' => $image->alt,
            'url' => basename((string) $this->relativePathForImage($image)),
            'path' => $this->relativePathForImage($image),
            'size' => $image->size,
            'mime_type' => $image->mime_type,
            'extension' => $image->extension,
            'modified' => $image->file_modified_at?->timestamp ?? optional($image->created_at)->timestamp,
            'dimensions' => $image->dimensions,
        ])->values()->all();

        return response()->json([
            'success' => true,
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'has_more' => $hasMore,
        ]);
    }

    public function store(Request $request, ImageRegistryService $registry)
    {
        try {
            $request->validate([
                'file' => 'required|extensions:jpg,jpeg,png,gif,webp,avif|max:10240',
                'context' => 'nullable|string|in:product,post',
            ]);

            $file = $request->file('file');
            $context = $request->input('context', 'product');
            $folder = $context === 'post' ? 'posts' : 'clothes';

            $originalName = $file->getClientOriginalName();
            $extension = strtolower($file->getClientOriginalExtension());
            $fileName = Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) . '-' . time() . '.' . $extension;

            $uploadPath = str_replace('/', DIRECTORY_SEPARATOR, $this->basePath . '/' . $folder);
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            $fullPath = $uploadPath . DIRECTORY_SEPARATOR . $fileName;
            $relative = 'clients/assets/img/' . $folder . '/' . $fileName;
            $tmpPath = $file->getRealPath();

            if (!rename($tmpPath, $fullPath)) {
                if (!copy($tmpPath, $fullPath)) {
                    throw new \RuntimeException("Không thể lưu file vào: $fullPath");
                }

                @unlink($tmpPath);
            }

            @chmod($fullPath, 0644);

            $record = $registry->registerLooseImage(
                $relative,
                [
                    'title' => pathinfo($originalName, PATHINFO_FILENAME),
                    'alt' => pathinfo($originalName, PATHINFO_FILENAME),
                    'context' => $context,
                ],
                $folder
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => (string) $record->id,
                    'name' => $record->name,
                    'title' => $record->title,
                    'alt' => $record->alt,
                    'url' => basename((string) $this->relativePathForImage($record)),
                    'path' => $this->relativePathForImage($record),
                    'size' => $record->size,
                    'mime_type' => $record->mime_type,
                    'extension' => $record->extension,
                    'modified' => $record->file_modified_at?->timestamp ?? now()->timestamp,
                    'dimensions' => $record->dimensions,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('MediaLibrary upload error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Lỗi server: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Request $request, $id, MediaAssignmentService $assignment)
    {
        $image = Image::find($id);
        if (!$image) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy ảnh'], 404);
        }

        [$source, $sourceId] = $this->resolveSourcePair($image);
        try {
            $success = $assignment->delete($source, (string) $sourceId, true);
        } catch (\DomainException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Đã xóa ảnh thành công' : 'Không thể xóa ảnh',
        ], $success ? 200 : 400);
    }

    public function bulkDelete(Request $request, MediaAssignmentService $assignment)
    {
        $ids = $request->input('ids', []);

        if (empty($ids) || !is_array($ids)) {
            return response()->json(['success' => false, 'message' => 'Danh sách ID không hợp lệ'], 400);
        }

        $images = Image::whereIn('id', $ids)->get();
        $count = 0;
        $failureMessages = [];

        foreach ($images as $image) {
            [$source, $sourceId] = $this->resolveSourcePair($image);

            try {
                $success = $assignment->delete($source, (string) $sourceId, true);
            } catch (\DomainException $exception) {
                $success = false;
                $failureMessages[] = $exception->getMessage();
            }

            if ($success) {
                $count++;
            }
        }

        if ($count === 0 && !empty($failureMessages)) {
            return response()->json([
                'success' => false,
                'message' => $failureMessages[0],
                'deleted_count' => 0,
            ], 422);
        }

        return response()->json([
            'success' => $count > 0,
            'message' => "Đã xóa thành công {$count} ảnh",
            'deleted_count' => $count,
        ]);
    }

    public function update(Request $request, $id, MediaAssignmentService $assignment)
    {
        $request->validate([
            'title' => 'nullable|string|max:255',
            'alt' => 'nullable|string|max:255',
        ]);

        $image = Image::find($id);
        if (!$image) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy ảnh'], 404);
        }

        [$source, $sourceId] = $this->resolveSourcePair($image);
        $success = $assignment->updateMeta($source, (string) $sourceId, [
            'title' => $request->input('title'),
            'alt' => $request->input('alt'),
        ]);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Cập nhật thông tin ảnh thành công' : 'Không thể cập nhật ảnh',
        ], $success ? 200 : 400);
    }

    public function show($id)
    {
        $image = Image::find($id);
        if (!$image) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy ảnh'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => (string) $image->id,
                'title' => $image->title,
                'alt' => $image->alt,
                'url' => basename((string) $this->relativePathForImage($image)),
                'path' => $this->relativePathForImage($image),
                'size' => $image->size,
                'dimensions' => $image->dimensions,
                'product_id' => $image->product_id,
                'entity_type' => $image->entity_type,
                'entity_id' => $image->entity_id,
                'role' => $image->role,
            ],
        ]);
    }

    protected function resolveSourcePair(Image $image): array
    {
        return match (true) {
            $image->entity_type === 'product' || $image->product_id !== null => ['product_image', $image->id],
            $image->entity_type === 'post' => ['post_thumbnail', $image->entity_id],
            $image->entity_type === 'category' => ['category_image', $image->entity_id],
            $image->entity_type === 'banner' && $image->role === 'mobile' => ['banner_mobile', $image->entity_id],
            $image->entity_type === 'banner' => ['banner_desktop', $image->entity_id],
            $image->entity_type === 'profile' && $image->role === 'sub_avatar' => ['profile_sub_avatar', $image->entity_id],
            $image->entity_type === 'profile' => ['profile_avatar', $image->entity_id],
            default => ['library_image', $image->id],
        };
    }

    protected function relativePathForImage(Image $image): ?string
    {
        return $image->path ?: $image->url;
    }

    protected function publicUrlForImage(Image $image): ?string
    {
        $path = $this->relativePathForImage($image);
        if (!$path) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        return asset($path);
    }
}
