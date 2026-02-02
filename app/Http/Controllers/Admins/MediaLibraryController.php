<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaLibraryController extends Controller
{
    protected $basePath;
    protected $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];

    public function __construct()
    {
        $this->basePath = public_path('clients/assets/img');
        $this->middleware(['auth:web', 'admin']);
    }

    /**
     * Lấy danh sách ảnh trong media library với phân trang
     */
    public function index(Request $request)
    {
        $context = $request->input('context', 'product'); // product hoặc post
        $page = (int) $request->input('page', 1);
        $perPage = (int) $request->input('per_page', 10);
        $search = $request->input('search', '');
        $type = $request->input('type', 'all'); // all, image, document

        // Xác định thư mục dựa trên context
        if ($context === 'post') {
            $baseDirectory = public_path('clients/assets/img/posts');
        } else {
            // product hoặc mặc định
            $baseDirectory = public_path('clients/assets/img/clothes');
        }

        if (!is_dir($baseDirectory)) {
            return response()->json([
                'success' => true,
                'data' => [],
                'total' => 0,
                'page' => $page,
                'per_page' => $perPage,
                'has_more' => false,
            ]);
        }

        $allFiles = [];
        $iterator = File::allFiles($baseDirectory);
        
        foreach ($iterator as $file) {
            $extension = strtolower($file->getExtension());
            
            // Filter by type
            if ($type === 'image' && !in_array($extension, $this->allowedExtensions)) {
                continue;
            }

            // Filter by search
            if ($search && !Str::contains(strtolower($file->getFilename()), strtolower($search))) {
                continue;
            }

            $relative = str_replace(public_path(), '', $file->getRealPath());
            $relative = str_replace('\\', '/', $relative);
            $relative = ltrim($relative, '/');

            try {
                $mimeType = mime_content_type($file->getRealPath());
            } catch (\Exception $e) {
                $mimeType = 'application/octet-stream';
            }

            $allFiles[] = [
                'id' => md5($relative),
                'name' => $file->getFilename(),
                'url' => asset($relative),
                'path' => $relative,
                'size' => $file->getSize(),
                'mime_type' => $mimeType,
                'extension' => $extension,
                'modified' => filemtime($file->getRealPath()),
                'dimensions' => $this->getImageDimensions($file->getRealPath()),
            ];
        }

        // Sort by modified date (newest first)
        usort($allFiles, function ($a, $b) {
            return $b['modified'] <=> $a['modified'];
        });

        $total = count($allFiles);
        $offset = ($page - 1) * $perPage;
        $paginatedFiles = array_slice($allFiles, $offset, $perPage);
        $hasMore = ($offset + $perPage) < $total;

        // DEBUG INFO
        Log::info('MediaLibrary Debug', [
            'context' => $context,
            'page' => $page,
            'per_page' => $perPage,
            'total_files' => $total,
            'offset' => $offset,
            'paginated_count' => count($paginatedFiles),
            'has_more' => $hasMore,
            'has_more_calc' => ($offset + $perPage) . ' < ' . $total . ' = ' . (($offset + $perPage) < $total ? 'true' : 'false'),
        ]);

        return response()->json([
            'success' => true,
            'data' => $paginatedFiles,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'has_more' => $hasMore,
            'debug' => [
                'offset' => $offset,
                'offset_plus_perpage' => $offset + $perPage,
                'total' => $total,
                'has_more_calc' => ($offset + $perPage) < $total,
            ],
        ]);
    }

    /**
     * Upload ảnh mới
     */
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|image|max:10240', // 10MB max
            'context' => 'nullable|string|in:product,post',
        ]);

        $file = $request->file('file');
        $context = $request->input('context', 'product');
        
        // Xác định thư mục dựa trên context
        if ($context === 'post') {
            $folder = 'posts';
        } else {
            $folder = 'clothes';
        }
        
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $fileName = Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) . '-' . time() . '.' . $extension;

        $uploadPath = $this->basePath . '/' . $folder;
        if (!File::isDirectory($uploadPath)) {
            File::makeDirectory($uploadPath, 0755, true);
        }

        $file->move($uploadPath, $fileName);
        $relative = 'clients/assets/img/' . $folder . '/' . $fileName;
        $fullPath = public_path($relative);

        try {
            $mimeType = mime_content_type($fullPath);
        } catch (\Exception $e) {
            $mimeType = 'application/octet-stream';
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => md5($relative),
                'name' => $fileName,
                'url' => asset($relative),
                'path' => $relative,
                'size' => filesize($fullPath),
                'mime_type' => $mimeType,
                'extension' => $extension,
                'modified' => filemtime($fullPath),
                'dimensions' => $this->getImageDimensions($fullPath),
            ],
        ]);
    }

    /**
     * Xóa ảnh
     */
    public function destroy(Request $request, $id)
    {
        $path = $request->input('path');
        if (!$path) {
            return response()->json([
                'success' => false,
                'message' => 'Path không được để trống',
            ], 400);
        }

        $fullPath = public_path($path);
        if (File::exists($fullPath)) {
            File::delete($fullPath);
            return response()->json([
                'success' => true,
                'message' => 'Đã xóa ảnh thành công',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Không tìm thấy file',
        ], 404);
    }

    /**
     * Lấy thông tin chi tiết ảnh
     */
    public function show($id)
    {
        // Implementation nếu cần
        return response()->json(['success' => false, 'message' => 'Not implemented']);
    }

    /**
     * Lấy kích thước ảnh
     */
    private function getImageDimensions($filePath)
    {
        if (!in_array(strtolower(pathinfo($filePath, PATHINFO_EXTENSION)), $this->allowedExtensions)) {
            return null;
        }

        try {
            $imageInfo = getimagesize($filePath);
            if ($imageInfo) {
                return [
                    'width' => $imageInfo[0],
                    'height' => $imageInfo[1],
                ];
            }
        } catch (\Exception $e) {
            // Ignore errors
        }

        return null;
    }
}
