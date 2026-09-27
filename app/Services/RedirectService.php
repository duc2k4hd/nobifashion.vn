<?php

namespace App\Services;

use App\Models\Post;
use App\Models\Redirect;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RedirectService
{
    /**
     * Tìm chuyển hướng 301 hợp lệ cho một bài viết blog hoặc đường dẫn
     * Đảm bảo:
     * 1. Tra cứu siêu tốc bằng Cache + Hash Index O(1)
     * 2. Kiểm tra nếu link đích KHÔNG TỒN TẠI -> Trả về null (để ra 404 ngay, tránh vòng lặp SEO)
     * 3. Chống vòng lặp vô tận (Loop Prevention)
     */
    public function resolveValidRedirect(string $slug, ?Request $request = null): ?string
    {
        $slug = trim($slug);
        if ($slug === '') {
            return null;
        }

        // Tạo danh sách các biến thể có thể có của link cũ
        $candidates = [
            "/blog/{$slug}",
            "blog/{$slug}",
            $slug,
            "/{$slug}",
        ];

        if ($request) {
            $candidates[] = '/' . ltrim($request->path(), '/');
        }

        $candidates = array_unique(array_filter($candidates));

        // Kiểm tra từng biến thể theo thứ tự ưu tiên
        foreach ($candidates as $candidate) {
            $normalized = Redirect::normalizePath($candidate);
            $hash = md5($normalized);

            // Cache 24 giờ cho kết quả tìm kiếm chuyển hướng
            $redirectData = Cache::remember("redirect:lookup:{$hash}", 86400, function () use ($hash, $candidate) {
                $record = Redirect::active()
                    ->where(function ($q) use ($hash, $candidate) {
                        $q->where('old_url_hash', $hash)
                          ->orWhere('old_url', $candidate);
                    })
                    ->first(['id', 'old_url', 'new_url', 'status_code']);

                if (!$record) {
                    return null;
                }

                return [
                    'id' => $record->id,
                    'old_url' => $record->old_url,
                    'new_url' => $record->new_url,
                    'status_code' => $record->status_code ?: 301,
                ];
            });

            if (!$redirectData) {
                continue;
            }

            // Có cấu hình redirect, kiểm tra tính hợp lệ của link đích
            $validatedDestination = $this->validateDestination($redirectData['old_url'], $redirectData['new_url']);

            if ($validatedDestination !== null) {
                // Tăng số lượt hit mà không làm chậm request (cập nhật nhanh)
                $this->incrementHit($redirectData['id']);

                return $validatedDestination;
            }

            // Nếu link đích KHÔNG hợp lệ / KHÔNG tồn tại / bị loop -> Trả về null luôn để trả về 404
            return null;
        }

        return null;
    }

    /**
     * Xác thực link đích:
     * - Tránh chuyển hướng đến chính nó (A -> A)
     * - Tránh vòng lặp (A -> B -> A)
     * - Kiểm tra bài viết đích có tồn tại trong posts không (nếu là link blog)
     */
    protected function validateDestination(string $oldUrl, string $newUrl): ?string
    {
        $oldNormalized = Redirect::normalizePath($oldUrl);
        $newNormalized = Redirect::normalizePath($newUrl);

        // 1. Chống lặp chính nó: Link mới trùng link cũ
        if ($oldNormalized === $newNormalized) {
            return null;
        }

        // 2. Chống chuỗi lặp: Kiểm tra xem link mới có tiếp tục bị redirect ngược lại không
        $secondHopHash = md5($newNormalized);
        $hasReverseLoop = Cache::remember("redirect:loop:{$secondHopHash}", 3600, function () use ($secondHopHash) {
            return Redirect::active()->where('old_url_hash', $secondHopHash)->exists();
        });

        if ($hasReverseLoop) {
            // Có khả năng tạo vòng lặp redirect chain -> Bỏ qua để tránh phạt SEO
            return null;
        }

        // 3. Phân tích nếu link mới là bài viết blog: /blog/{target_slug} hoặc {target_slug}
        $targetSlug = null;
        if (preg_match('~^/blog/([^/?#]+)~i', $newNormalized, $matches)) {
            $targetSlug = $matches[1];
        } elseif (!str_starts_with($newNormalized, '/') && !str_contains($newNormalized, '://')) {
            $targetSlug = $newNormalized;
        }

        if ($targetSlug) {
            // Kiểm tra bài viết đích trong bảng posts CÓ TỒN TẠI VÀ ĐANG XUẤT BẢN HAY KHÔNG
            $targetExists = Cache::remember("post:exists:{$targetSlug}", 300, function () use ($targetSlug) {
                return Post::published()->where('slug', $targetSlug)->exists();
            });

            if (!$targetExists) {
                // Link đích không tồn tại trong posts -> Trả về null để ra lỗi 404 ngay lập tức!
                return null;
            }

            // Link đích tồn tại, trả về URL chuẩn
            return url('/blog/' . $targetSlug);
        }

        // 4. Nếu là URL đầy đủ (https://...) hoặc link nội bộ khác (/san-pham, /...)
        if (filter_var($newUrl, FILTER_VALIDATE_URL)) {
            return $newUrl;
        }

        if (str_starts_with($newUrl, '/')) {
            return url($newUrl);
        }

        return url('/' . ltrim($newUrl, '/'));
    }

    /**
     * Tăng số lượt kích hoạt redirect mà không ảnh hưởng hiệu năng
     */
    protected function incrementHit(int $redirectId): void
    {
        try {
            DB::table('redirects')
                ->where('id', $redirectId)
                ->increment('hits', 1, [
                    'last_accessed_at' => now(),
                ]);
        } catch (\Throwable) {
            // Bỏ qua lỗi tăng hit để không bao giờ làm gián đoạn request người dùng
        }
    }
}
