<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class ToolsController extends Controller
{
    public function index()
    {
        return view('admins.tools.index');
    }

    public function scanPostImages()
    {
        $usedImages = [];

        // Tránh N+1 và tràn bộ nhớ: lấy data chunk, nhớ lấy cả bài đang trong thùng rác
        Post::withTrashed()->select('thumbnail', 'content')->chunk(1000, function ($posts) use (&$usedImages) {
            foreach ($posts as $post) {
                if ($post->thumbnail) {
                    $usedImages[basename($post->thumbnail)] = true;
                }

                if ($post->content) {
                    // Regex tìm các url ảnh
                    preg_match_all('/src="([^"]+\.(?:jpg|jpeg|png|gif|webp|svg))"/i', $post->content, $matches);
                    if (!empty($matches[1])) {
                        foreach ($matches[1] as $url) {
                            $usedImages[basename($url)] = true;
                        }
                    }
                }
            }
        });

        // Giữ lại các ảnh được đánh dấu là ảnh đại diện trong bảng images (is_primary = 1)
        // ĐIỀU KIỆN TIÊN QUYẾT: Bài viết đó phải thực sự tồn tại trong CSDL (kể cả trong thùng rác)
        \App\Models\Image::where('entity_type', 'post')
            ->where('is_primary', true)
            ->whereExists(function ($query) {
                $query->select(\Illuminate\Support\Facades\DB::raw(1))
                      ->from('posts')
                      ->whereColumn('posts.id', 'images.entity_id');
            })
            ->chunk(1000, function ($images) use (&$usedImages) {
                foreach ($images as $img) {
                    if ($img->path) $usedImages[basename($img->path)] = true;
                    if ($img->url) $usedImages[basename($img->url)] = true;
                    if ($img->name) $usedImages[basename($img->name)] = true;
                }
            });

        // Kiểm tra thư mục ảnh posts
        $directory = public_path('clients/assets/img/posts');
        if (!is_dir($directory)) {
            return response()->json([
                'success' => true,
                'files_to_delete' => []
            ]);
        }

        $filesToDelete = [];
        $totalScanned = 0;

        // Dùng RecursiveDirectoryIterator thay cho File::allFiles để trị thư mục > 30.000 files
        $dirIterator = new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator = new \RecursiveIteratorIterator($dirIterator);

        foreach ($iterator as $file) {
            $totalScanned++;
            
            // Xóa file rỗng hoặc không phải file (thư mục)
            if (!$file->isFile()) continue;

            $filename = $file->getFilename();
            // Bỏ qua các tệp tin hệ thống
            if ($filename === '.gitkeep' || $filename === 'index.php') {
                continue;
            }

            // Nếu file trên ổ cứng KHÔNG TỒN TẠI trong tập $usedImages -> nó là rác
            if (!isset($usedImages[$filename])) {
                // Lấy relative path
                $relativePath = str_replace($directory . DIRECTORY_SEPARATOR, '', $file->getPathname());
                $filesToDelete[] = $relativePath;
            }
        }

        return response()->json([
            'success' => true,
            'files_to_delete' => $filesToDelete,
            'debug' => [
                'total_scanned_files_on_disk' => $totalScanned,
                'total_used_images_protected' => count($usedImages),
            ]
        ]);
    }

    public function deletePostImages(Request $request)
    {
        $files = $request->input('files');
        if (empty($files) || !is_array($files)) {
            return response()->json(['success' => false, 'message' => 'Danh sách file không hợp lệ']);
        }

        $directory = public_path('clients/assets/img/posts');
        $deletedCount = 0;
        
        $safeFilenames = [];

        foreach ($files as $relativePath) {
            // Ngăn path traversal vulnerability (chặn sử dụng ký tự lùi cấp thư mục)
            if (str_contains($relativePath, '..')) {
                continue;
            }
            
            $safeFilenames[] = basename($relativePath);
            
            $filePath = $directory . DIRECTORY_SEPARATOR . $relativePath;

            if (File::exists($filePath) && is_file($filePath)) {
                File::delete($filePath);
                $deletedCount++;
            }
        }
        
        // Xóa song song trong DB (bảng images, entity_type = 'post') 
        // Xóa lô (batch) query để tránh N+1
        if (!empty($safeFilenames)) {
            \App\Models\Image::where('entity_type', 'post')
                ->where(function ($q) use ($safeFilenames) {
                    foreach ($safeFilenames as $name) {
                        $q->orWhere('path', 'like', '%' . $name . '%')
                          ->orWhere('url', 'like', '%' . $name . '%')
                          ->orWhere('name', 'like', '%' . $name . '%');
                    }
                })->delete();
        }

        return response()->json([
            'success' => true,
            'deleted_count' => $deletedCount
        ]);
    }

    public function exportPostImages()
    {
        $urls = [];
        \App\Models\Post::withTrashed()->select('id', 'thumbnail', 'content')->chunk(1000, function ($posts) use (&$urls) {
            foreach ($posts as $post) {
                if (!empty($post->thumbnail)) {
                    $urls[] = "[Post ID: {$post->id}] Thumbnail: " . $post->thumbnail;
                }
                if (!empty($post->content)) {
                    preg_match_all('/src="([^"]+\.(?:jpg|jpeg|png|gif|webp|svg))"/i', $post->content, $matches);
                    if (!empty($matches[1])) {
                        foreach ($matches[1] as $url) {
                            $urls[] = "[Post ID: {$post->id}] Content: " . $url;
                        }
                    }
                }
            }
        });

        $content = implode(PHP_EOL, array_unique($urls));
        $filename = 'danh_sach_link_anh_trong_posts_' . date('Y_m_d_His') . '.txt';

        return response($content)
            ->header('Content-Type', 'text/plain')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }
}
