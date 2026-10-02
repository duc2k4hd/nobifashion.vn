<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Services\HtmlCompressorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class ToolsController extends Controller
{
    /**
     * Trang chủ công cụ hệ thống
     */
    public function index()
    {
        return view('admins.tools.index');
    }

    /**
     * Xuất danh sách URL ảnh trong bài viết (Hỗ trợ lọc theo danh sách ID đang chọn)
     */
    public function exportPostImages(Request $request): Response
    {
        $urls = [];
        $query = Post::withTrashed()->select('id', 'thumbnail', 'content');

        if ($request->filled('ids')) {
            $rawIds = $request->input('ids');
            $ids = is_array($rawIds)
                ? array_filter(array_map('intval', $rawIds))
                : array_filter(array_map('intval', explode(',', (string) $rawIds)));
            if (! empty($ids)) {
                $query->whereIn('id', $ids);
            }
        }

        $query->chunk(1000, function ($posts) use (&$urls) {
            foreach ($posts as $post) {
                if (! empty($post->thumbnail)) {
                    $urls[] = "[Post ID: {$post->id}] Thumbnail: ".$post->thumbnail;
                }
                if (! empty($post->content)) {
                    preg_match_all('/src="([^"]+\.(?:jpg|jpeg|png|gif|webp|svg))"/i', $post->content, $matches);
                    if (! empty($matches[1])) {
                        foreach ($matches[1] as $url) {
                            $urls[] = "[Post ID: {$post->id}] Content: ".$url;
                        }
                    }
                }
            }
        });

        $content = implode(PHP_EOL, array_unique($urls));
        $filename = 'danh_sach_link_anh_trong_posts_'.date('Y_m_d_His').'.txt';

        return response($content)
            ->header('Content-Type', 'text/plain')
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
    }

    /**
     * Ép gọn HTML nội dung bài viết để tránh tràn ô Excel và tối ưu dung lượng
     */
    public function compressPostsHtml(): JsonResponse
    {
        @ini_set('memory_limit', '512M');
        @set_time_limit(600);

        $query = Post::withTrashed()->whereNotNull('content')->where('content', '!=', '');
        $total = $query->count();

        if ($total === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Không có bài viết nào có nội dung để xử lý.',
            ]);
        }

        $processed = 0;
        $updated = 0;
        $totalCharsBefore = 0;
        $totalCharsAfter = 0;

        $query->select('id', 'content')->chunkById(200, function ($posts) use (&$processed, &$updated, &$totalCharsBefore, &$totalCharsAfter) {
            $updates = [];

            foreach ($posts as $post) {
                $processed++;
                $original = (string) $post->content;
                $originalLen = mb_strlen($original);
                $totalCharsBefore += $originalLen;

                $compressed = HtmlCompressorService::compress($original);
                $compressedLen = mb_strlen($compressed);
                $totalCharsAfter += $compressedLen;

                if ($original !== $compressed) {
                    $updated++;
                    $updates[] = [
                        'id' => $post->id,
                        'content' => $compressed,
                    ];
                }
            }

            if (! empty($updates)) {
                foreach ($updates as $up) {
                    DB::table('posts')->where('id', $up['id'])->update(['content' => $up['content']]);
                }
            }
        });

        $savedChars = $totalCharsBefore - $totalCharsAfter;
        $percent = $totalCharsBefore > 0 ? round(($savedChars / $totalCharsBefore) * 100, 2) : 0;
        $savedKb = round($savedChars / 1024, 2);

        return response()->json([
            'success' => true,
            'message' => "Đã ép gọn thành công {$updated}/{$total} bài viết!",
            'total' => $total,
            'updated' => $updated,
            'saved_chars' => $savedChars,
            'saved_kb' => $savedKb,
            'percent' => $percent,
        ]);
    }
}
