<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Models\MediaLibraryFile;
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
        $this->middleware(['auth:web', 'admin']);
    }

    /**
     * Lấy danh sách ảnh – query DB thay vì scan filesystem
     */
    public function index(Request $request)
    {
        $context = $request->input('context', 'product');
        $page    = max(1, (int) $request->input('page', 1));
        $perPage = min(200, max(1, (int) $request->input('per_page', 100)));
        $search  = trim((string) $request->input('search', ''));

        $query = MediaLibraryFile::forContext($context)
            ->orderByDesc('created_at');

        if ($search !== '') {
            $query->where('name', 'like', '%' . $search . '%');
        }

        $total   = $query->count();
        $offset  = ($page - 1) * $perPage;
        $files   = $query->skip($offset)->take($perPage)->get();
        $hasMore = ($offset + $perPage) < $total;

        $data = $files->map(fn ($f) => [
            'id'         => (string) $f->id,
            'name'       => $f->name,
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
     * Upload ảnh mới và lưu metadata vào DB
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

            // Dùng DIRECTORY_SEPARATOR để tránh mixed-slash trên Windows
            $uploadPath = str_replace('/', DIRECTORY_SEPARATOR, $this->basePath . '/' . $folder);

            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            $fullPath = $uploadPath . DIRECTORY_SEPARATOR . $fileName;
            $relative = 'clients/assets/img/' . $folder . '/' . $fileName;

            // PHP native: không kiểm tra is_writable() như Symfony, hoạt động tốt trên Windows
            $tmpPath = $file->getRealPath();
            if (!rename($tmpPath, $fullPath)) {
                // Fallback: copy rồi xóa file tạm
                if (!copy($tmpPath, $fullPath)) {
                    throw new \RuntimeException("Không thể lưu file vào: $fullPath");
                }
                @unlink($tmpPath);
            }

            // Mime type
            try {
                $mimeType = mime_content_type($fullPath);
            } catch (\Throwable) {
                $mimeType = 'application/octet-stream';
            }

            // Dimensions (bỏ qua avif vì getimagesize() không hỗ trợ)
            $dimensions = null;
            if ($extension !== 'avif') {
                $info = @getimagesize($fullPath);
                if ($info) {
                    $dimensions = ['width' => $info[0], 'height' => $info[1]];
                }
            }

            // Lưu vào DB
            $record = MediaLibraryFile::create([
                'name'             => $fileName,
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
                    'name'       => $record->name,
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
     * Xóa ảnh
     */
    public function destroy(Request $request, $id)
    {
        $path = $request->input('path');
        if (!$path) {
            return response()->json(['success' => false, 'message' => 'Path không được để trống'], 400);
        }

        $fullPath = public_path($path);
        if (File::exists($fullPath)) {
            File::delete($fullPath);
        }

        // Xóa record trong DB theo path
        MediaLibraryFile::where('path', $path)->delete();

        return response()->json(['success' => true, 'message' => 'Đã xóa ảnh thành công']);
    }

    public function show($id)
    {
        return response()->json(['success' => false, 'message' => 'Not implemented']);
    }
}
