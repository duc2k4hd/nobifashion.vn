<?php

namespace App\Services\Media;

use App\Models\Image;
use App\Models\Post;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PostMediaSyncService
{
    protected array $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'svg'];

    public function __construct(
        protected FileHelperService $files,
        protected ImageRegistryService $registry
    ) {
    }

    /**
     * Lấy thống kê tổng quan trước khi chạy đồng bộ
     */
    public function getSyncOverview(): array
    {
        $totalPosts = Post::count();
        $postsWithContent = Post::where(function ($q) {
            $q->whereNotNull('content')
              ->orWhereNotNull('thumbnail');
        })->count();

        $totalImages = Image::count();

        $postImagesCount = Image::where(function ($q) {
            $q->where('context', 'post')
              ->orWhere('entity_type', 'post')
              ->orWhere('path', 'like', '%posts%');
        })->count();

        $assignedPostImages = Image::where('entity_type', 'post')
            ->whereNotNull('entity_id')
            ->count();

        $unassignedPostImages = max(0, $postImagesCount - $assignedPostImages);

        // Đếm nhanh số file vật lý trong thư mục posts bằng FilesystemIterator (không tốn RAM)
        $postsDir = public_path('clients/assets/img/posts');
        $physicalFilesCount = 0;
        if (is_dir($postsDir)) {
            $fi = new \FilesystemIterator($postsDir, \FilesystemIterator::SKIP_DOTS);
            $physicalFilesCount = iterator_count($fi);
        }

        return [
            'total_posts' => $totalPosts,
            'posts_with_content' => $postsWithContent,
            'total_images' => $totalImages,
            'post_images_in_db' => $postImagesCount,
            'assigned_post_images' => $assignedPostImages,
            'unassigned_post_images' => $unassignedPostImages,
            'physical_files_count' => $physicalFilesCount,
        ];
    }

    /**
     * BƯỚC 1: Quét và nạp các file vật lý trong thư mục clients/assets/img/posts vào DB nếu chưa có
     * Chia batch an toàn, dùng INSERT IGNORE hoặc kiểm tra trước để chống duplicate và chống tràn RAM
     */
    public function indexPhysicalFiles(): array
    {
        // KHÔNG tự ý tạo bản ghi mới vào DB
        return [
            'success' => true,
            'message' => 'Bỏ qua quét file vật lý theo yêu cầu (không tự ý tạo bản ghi).',
            'indexed_count' => 0,
            'total_files' => 0,
            'skipped_count' => 0,
        ];
    }


    /**
     * BƯỚC 2: Quét batch bài viết, parse ảnh từ thẻ <img> trong content & thumbnail, gán vào images table
     *
     * @param int $offset Vị trí bắt đầu
     * @param int $limit Số lượng bài viết mỗi batch (mặc định 100)
     * @param array $options Tùy chọn:
     *                       - 'auto_register_missing' => true/false (tự động tạo record nếu phát hiện ảnh trong bài chưa có trong media)
     *                       - 'update_meta' => true/false (cập nhật alt, title từ thẻ img nếu đang rỗng)
     */
    public function syncPostsChunk(int $offset = 0, int $limit = 100, array $options = []): array
    {
        $autoRegister = $options['auto_register_missing'] ?? true;
        $updateMeta = $options['update_meta'] ?? true;

        $posts = Post::select(['id', 'title', 'thumbnail', 'thumbnail_alt_text', 'content'])
            ->orderBy('id', 'asc')
            ->skip($offset)
            ->take($limit)
            ->get();

        $processedPostsCount = $posts->count();
        if ($processedPostsCount === 0) {
            return [
                'processed_posts' => 0,
                'images_detected' => 0,
                'images_assigned' => 0,
                'images_created' => 0,
                'next_offset' => $offset,
                'finished' => true,
                'message' => 'Đã quét hết tất cả bài viết.',
            ];
        }

        $allExtractedImages = [];
        $totalDetected = 0;

        foreach ($posts as $post) {
            $extractedForPost = $this->extractImagesFromPost($post);
            $totalDetected += count($extractedForPost);

            foreach ($extractedForPost as $img) {
                // Key phân biệt để lookup nhanh
                $allExtractedImages[] = $img;
            }
        }

        if (empty($allExtractedImages)) {
            return [
                'processed_posts' => $processedPostsCount,
                'images_detected' => 0,
                'images_assigned' => 0,
                'images_created' => 0,
                'next_offset' => $offset + $limit,
                'finished' => $processedPostsCount < $limit,
                'message' => "Đã xử lý {$processedPostsCount} bài viết (không có ảnh).",
            ];
        }

        // Gom danh sách tên file và đường dẫn để query DB siêu nhanh bằng 1 câu SELECT
        $names = [];
        $paths = [];
        foreach ($allExtractedImages as $item) {
            $names[$item['filename']] = true;
            if ($item['clean_path']) {
                $paths[$item['clean_path']] = true;
            }
        }

        $nameKeys = array_keys($names);
        $pathKeys = array_keys($paths);

        // Truy vấn tất cả ảnh trong DB khớp với tên hoặc đường dẫn trong batch này
        $existingRecords = DB::table('images')
            ->where(function ($q) use ($nameKeys, $pathKeys) {
                $q->whereIn('name', $nameKeys);
                if (!empty($pathKeys)) {
                    $q->orWhereIn('path', $pathKeys);
                }
            })
            ->select(['id', 'name', 'path', 'url', 'entity_type', 'entity_id', 'role', 'context', 'alt', 'title'])
            ->get();

        // Index theo name và path để tra cứu O(1)
        $recordsByName = [];
        $recordsByPath = [];
        foreach ($existingRecords as $rec) {
            if ($rec->name) {
                $recordsByName[strtolower($rec->name)][] = $rec;
            }
            if ($rec->path) {
                $recordsByPath[strtolower($rec->path)][] = $rec;
            }
        }

        $assignedCount = 0;
        $createdCount = 0;
        $updatesByPost = []; // [postId => [imageIds...]]
        $metaUpdates = [];   // [imageId => ['alt' => ..., 'title' => ...]]
        $unmatchedItems = [];

        foreach ($allExtractedImages as $item) {
            $matchedRecord = null;
            $lowerPath = strtolower($item['clean_path'] ?? '');
            $lowerName = strtolower($item['filename']);

            // Ưu tiên 1: Khớp đường dẫn chính xác (e.g. clients/assets/img/posts/anh-1.jpg)
            if ($lowerPath && isset($recordsByPath[$lowerPath])) {
                $matchedRecord = $recordsByPath[$lowerPath][0];
            }
            // Ưu tiên 2: Khớp theo tên file
            elseif (isset($recordsByName[$lowerName])) {
                // Ưu tiên record có context là post hoặc path chứa 'posts'
                foreach ($recordsByName[$lowerName] as $candidate) {
                    if ($candidate->context === 'post' || str_contains($candidate->path ?: '', 'posts')) {
                        $matchedRecord = $candidate;
                        break;
                    }
                }
                // Nếu chưa có, lấy bản ghi đầu tiên
                $matchedRecord ??= $recordsByName[$lowerName][0];
            }

            if ($matchedRecord) {
                $postId = $item['post_id'];
                $role = $item['role'] ?? 'content';

                // Kiểm tra xem record đã được gán đúng chưa
                $needAssignment = ($matchedRecord->entity_type !== 'post')
                    || ((int) $matchedRecord->entity_id !== (int) $postId)
                    || ($matchedRecord->context !== 'post')
                    || ($matchedRecord->role !== $role);

                if ($needAssignment) {
                    $updatesByPost[$postId][] = [
                        'image_id' => $matchedRecord->id,
                        'role' => $role,
                    ];
                    $assignedCount++;
                }

                // Cập nhật alt và title nếu chưa có
                if ($updateMeta) {
                    $newAlt = !empty($matchedRecord->alt) ? null : ($item['alt'] ?: null);
                    $newTitle = !empty($matchedRecord->title) ? null : ($item['title'] ?: $item['post_title']);

                    if ($newAlt || $newTitle) {
                        $metaUpdates[$matchedRecord->id] = array_filter([
                            'alt' => $newAlt,
                            'title' => $newTitle,
                        ]);
                    }
                }
            } else {
                $unmatchedItems[] = $item;
            }
        }

        // Thực thi cập nhật gán bài viết theo từng post (rất ít queries, chạy siêu tốc)
        if (!empty($updatesByPost)) {
            foreach ($updatesByPost as $postId => $entries) {
                $idsByRole = [];
                foreach ($entries as $e) {
                    $idsByRole[$e['role']][] = $e['image_id'];
                }

                foreach ($idsByRole as $role => $ids) {
                    DB::table('images')
                        ->whereIn('id', $ids)
                        ->update([
                            'entity_type' => 'post',
                            'entity_id' => $postId,
                            'context' => 'post',
                            'role' => $role,
                            'updated_at' => now(),
                        ]);
                }
            }
        }

        // Thực thi cập nhật alt, title nếu có
        if (!empty($metaUpdates)) {
            foreach ($metaUpdates as $imgId => $data) {
                DB::table('images')
                    ->where('id', $imgId)
                    ->update(array_merge($data, ['updated_at' => now()]));
            }
        }

        // TUYỆT ĐỐI KHÔNG TẠO BẢN GHI MỚI VÀO BẢNG IMAGES:
        // Nếu ảnh trong bài viết không có trong bảng media thì BỎ QUA HOÀN TOÀN!
        // Chỉ gán đối tượng cho ảnh thật đã tồn tại trong media.
        unset($allExtractedImages, $existingRecords, $recordsByName, $recordsByPath, $posts);

        return [
            'processed_posts' => $processedPostsCount,
            'images_detected' => $totalDetected,
            'images_assigned' => $assignedCount,
            'images_created' => 0,
            'next_offset' => $offset + $limit,
            'finished' => $processedPostsCount < $limit,
            'message' => "Đã quét {$processedPostsCount} bài viết: phát hiện {$totalDetected} ảnh, gán thành công {$assignedCount} ảnh có sẵn trong Media.",
        ];
    }

    /**
     * Dọn dẹp tất cả các bản ghi ảnh thiếu file vật lý trong bảng images
     * (xóa triệt để các hàng rác được tạo tự động mà không có file thực tế trên ổ cứng)
     */
    public function cleanupGhostImages(int $limit = 5000): array
    {
        $candidates = DB::table('images')
            ->where(function ($q) {
                $q->where('context', 'post')
                  ->orWhere('entity_type', 'post')
                  ->orWhere('role', 'content');
            })
            ->select(['id', 'path', 'url', 'size'])
            ->limit($limit)
            ->get();

        $idsToDelete = [];
        foreach ($candidates as $cand) {
            $rawPath = $cand->path ?: $cand->url;
            if (!$rawPath) {
                $idsToDelete[] = $cand->id;
                continue;
            }

            if (str_starts_with($rawPath, 'http://') || str_starts_with($rawPath, 'https://')) {
                continue;
            }

            $cleanPath = ltrim(str_replace('\\', '/', $rawPath), '/');
            $absPath = public_path($cleanPath);

            if (!is_file($absPath)) {
                $idsToDelete[] = $cand->id;
            }
        }

        $deletedCount = 0;
        if (!empty($idsToDelete)) {
            foreach (array_chunk($idsToDelete, 500) as $chunk) {
                $deletedCount += DB::table('images')->whereIn('id', $chunk)->delete();
            }
        }

        return [
            'success' => true,
            'scanned' => $candidates->count(),
            'deleted' => $deletedCount,
            'has_more' => $candidates->count() >= $limit,
            'message' => "Đã dọn dẹp {$deletedCount} bản ghi ảnh thiếu file vật lý.",
        ];
    }

    /**
     * Trích xuất toàn bộ ảnh từ 1 bài viết (content HTML & thumbnail)
     */
    public function extractImagesFromPost(Post $post): array
    {
        $extracted = [];
        $seenFilenames = [];

        // 1. Kiểm tra ảnh đại diện (thumbnail)
        if (!empty($post->thumbnail)) {
            $thumbInfo = $this->parseImageSource($post->thumbnail);
            if ($thumbInfo) {
                $thumbInfo['post_id'] = $post->id;
                $thumbInfo['post_title'] = $post->title;
                $thumbInfo['role'] = 'thumbnail';
                $thumbInfo['alt'] = $post->thumbnail_alt_text ?: $post->title;
                $thumbInfo['title'] = $post->title;

                $extracted[] = $thumbInfo;
                $seenFilenames[strtolower($thumbInfo['filename'])] = true;
            }
        }

        // 2. Phân tích nội dung content HTML tìm thẻ <img>
        $content = $post->content;
        if (!empty($content) && str_contains($content, '<img')) {
            // Regex lấy thẻ img đầy đủ
            if (preg_match_all('/<img[^>]+src=[\'"]([^\'"]+)[\'"][^>]*>/i', $content, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $tag = $match[0];
                    $rawSrc = $match[1];

                    $imgInfo = $this->parseImageSource($rawSrc);
                    if (!$imgInfo) {
                        continue;
                    }

                    $fnKey = strtolower($imgInfo['filename']);
                    // Tránh trùng lặp nếu 1 ảnh được chèn nhiều lần trong cùng 1 bài
                    if (isset($seenFilenames[$fnKey])) {
                        continue;
                    }
                    $seenFilenames[$fnKey] = true;

                    // Trích xuất alt="..."
                    $alt = null;
                    if (preg_match('/alt=[\'"]([^\'"]*)[\'"]/i', $tag, $mAlt)) {
                        $alt = trim(html_entity_decode($mAlt[1], ENT_QUOTES, 'UTF-8'));
                    }

                    // Trích xuất title="..."
                    $title = null;
                    if (preg_match('/title=[\'"]([^\'"]*)[\'"]/i', $tag, $mTitle)) {
                        $title = trim(html_entity_decode($mTitle[1], ENT_QUOTES, 'UTF-8'));
                    }

                    $imgInfo['post_id'] = $post->id;
                    $imgInfo['post_title'] = $post->title;
                    $imgInfo['role'] = 'content';
                    $imgInfo['alt'] = $alt;
                    $imgInfo['title'] = $title;

                    $extracted[] = $imgInfo;
                }
            }
        }

        return $extracted;
    }

    /**
     * Parse chuỗi URL hoặc đường dẫn ảnh thành thông tin filename & clean path
     */
    protected function parseImageSource(string $src): ?array
    {
        $rawSrc = trim($src);
        if ($rawSrc === '') {
            return null;
        }

        // Loại bỏ query string và fragment
        $pathOnly = parse_url($rawSrc, PHP_URL_PATH);
        if (!$pathOnly) {
            $pathOnly = $rawSrc;
        }

        $filename = basename($pathOnly);
        $filename = rawurldecode($filename); // Giải mã %20 thành khoảng trắng nếu có

        if ($filename === '' || $filename === '.' || $filename === '/') {
            return null;
        }

        // Kiểm tra đuôi file có phải ảnh hợp lệ không
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (!in_array($ext, $this->allowedExtensions, true)) {
            return null;
        }

        // Chuẩn hóa relative path
        $cleanPath = ltrim(str_replace('\\', '/', $pathOnly), '/');
        // Nếu path bắt đầu bằng domain hoặc scheme, lấy phần path sau domain
        if (str_contains($cleanPath, 'clients/assets/img/posts/')) {
            $cleanPath = 'clients/assets/img/posts/' . $filename;
        } elseif (str_contains($cleanPath, 'clients/assets/img/')) {
            $pos = strpos($cleanPath, 'clients/assets/img/');
            $cleanPath = substr($cleanPath, $pos);
        } else {
            $cleanPath = 'clients/assets/img/posts/' . $filename;
        }

        return [
            'raw_src' => $rawSrc,
            'filename' => $filename,
            'clean_path' => $cleanPath,
            'extension' => $ext,
        ];
    }
}
