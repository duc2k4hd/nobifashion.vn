<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MediaLibraryController extends Controller
{
    protected $basePath;
    protected $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'avif'];

    public function __construct()
    {
        $this->basePath = public_path('clients/assets/img');
        $this->middleware(['admin']);
    }

    /**
     * Lấy danh sách ảnh từ bảng images
     */
    public function index(Request $request)
    {
        $context = $request->input('context', 'product');
        $page    = max(1, (int) $request->input('page', 1));
        $perPage = min(200, max(1, (int) $request->input('per_page', 100)));
        $search  = trim((string) $request->input('search', ''));

        // Query từ bảng images
        $query = Image::forContext($context)
            ->orderByDesc('created_at');

        if ($search !== '') {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('title', 'like', '%' . $search . '%');
            });
        }

        $total   = $query->count();
        $offset  = ($page - 1) * $perPage;
        $files   = $query->skip($offset)->take($perPage)->get();
        $hasMore = ($offset + $perPage) < $total;

        $data = $files->map(fn ($f) => [
            'id'         => (string) $f->id,
            'name'       => $f->name ?? basename($f->path),
            'title'      => $f->title,
            'alt'        => $f->alt,
            'url'        => $f->url,
            'path'       => $f->path,
            'size'       => $f->size,
            'mime_type'  => $f->mime_type,
            'extension'  => $f->extension,
            'modified'   => $f->file_modified_at ? $f->file_modified_at->timestamp : $f->created_at->timestamp,
            'dimensions' => $f->dimensions,
        ])->values()->all();

        return response()->json([
            'success'  => true,
            'data'     => $data,
            'total'    => $total,
            'page'     => $page,
            'per_page' => $perPage,
            'has_more' => $hasMore,
        ]);
    }

    /**
     * Upload ảnh mới vào bảng images (product_id = null)
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'file'    => 'required|mimes:jpg,jpeg,png,gif,webp,avif|max:10240',
                'context' => 'nullable|string|in:product,post',
            ]);

            $file    = $request->file('file');
            $context = $request->input('context', 'product');
            $folder  = $context === 'post' ? 'posts' : 'clothes';

            $originalName = $file->getClientOriginalName();
            $extension    = strtolower($file->getClientOriginalExtension());
            $fileName     = Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) . '-' . time() . '.' . $extension;

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

            try {
                $mimeType = mime_content_type($fullPath);
            } catch (\Throwable) {
                $mimeType = 'application/octet-stream';
            }

            $dimensions = null;
            if ($extension !== 'avif' && $extension !== 'svg') {
                $info = @getimagesize($fullPath);
                if ($info) {
                    $dimensions = ['width' => $info[0], 'height' => $info[1]];
                }
            }

            // Lưu trực tiếp vào bảng images, product_id để null
            $record = Image::create([
                'product_id'       => null, 
                'title'            => $originalName,
                'alt'              => $originalName,
                'path'             => $relative,
                'url'              => asset($relative),
                'extension'        => $extension,
                'mime_type'        => $mimeType,
                'context'          => $context,
                'size'             => filesize($fullPath),
                'width'            => $dimensions['width'] ?? null,
                'height'           => $dimensions['height'] ?? null,
                'file_modified_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'data'    => [
                    'id'         => (string) $record->id,
                    'name'       => $fileName,
                    'title'      => $record->title,
                    'alt'        => $record->alt,
                    'url'        => $record->url,
                    'path'       => $record->path,
                    'size'       => $record->size,
                    'mime_type'  => $record->mime_type,
                    'extension'  => $record->extension,
                    'modified'   => $record->file_modified_at->timestamp,
                    'dimensions' => $dimensions,
                ],
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('MediaLibrary upload error: ' . $e->getMessage(), [
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Lỗi server: ' . $e->getMessage(),
            ], 500);
        }
    }



    /**
     * Xóa ảnh khỏi bảng images và ổ cứng
     */
    public function destroy(Request $request, $id)
    {
        $image = Image::find($id);
        
        if (!$image) {
            // Fallback tìm theo path nếu không có ID
            $path = $request->input('path');
            if ($path) {
                $image = Image::where('path', $path)->first();
            }
        }

        if (!$image) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy ảnh'], 404);
        }

        $fullPath = public_path($image->path);
        if (File::exists($fullPath)) {
            File::delete($fullPath);
        }

        $image->delete();

        return response()->json(['success' => true, 'message' => 'Đã xóa ảnh thành công']);
    }

    /**
     * Cập nhật tiêu đề và văn bản thay thế
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'nullable|string|max:255',
            'alt'   => 'nullable|string|max:255',
        ]);

        $image = Image::find($id);
        if (!$image) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy ảnh'], 404);
        }

        $image->update([
            'title' => $request->input('title'),
            'alt'   => $request->input('alt'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật thông tin ảnh thành công'
        ]);
    }

    public function show($id)
    {
        $image = Image::find($id);
        if (!$image) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy ảnh'], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'id'         => (string) $image->id,
                'title'      => $image->title,
                'alt'        => $image->alt,
                'url'        => $image->url,
                'path'       => $image->path,
                'size'       => $image->size,
                'dimensions' => $image->dimensions,
                'product_id' => $image->product_id,
            ]
        ]);
    }
}
