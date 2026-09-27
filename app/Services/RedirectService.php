<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Post;
use App\Models\Redirect;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RedirectService
{
    /**
     * Tìm chuyển hướng hợp lệ từ Request hiện tại
     * Trả về mảng ['url' => string, 'status_code' => int] hoặc null
     */
    public function resolveRequest(Request $request): ?array
    {
        $path = '/' . ltrim($request->path(), '/');
        $queryString = $request->getQueryString();

        $candidates = $this->buildPathCandidates($path);

        foreach ($candidates as $candidate) {
            $record = $this->findActiveRedirect($candidate);
            if (! $record) {
                continue;
            }

            $validatedDestination = $this->validateDestination($record['old_url'], $record['new_url']);
            if ($validatedDestination !== null) {
                $finalUrl = $this->attachQueryString($validatedDestination, $queryString);

                $this->incrementHit($record['id']);

                return [
                    'url' => $finalUrl,
                    'status_code' => (int) ($record['status_code'] ?: 301),
                ];
            }
        }

        return null;
    }

    /**
     * Tương thích ngược với các hàm gọi cũ: resolveValidRedirect($slug, $request)
     */
    public function resolveValidRedirect(string $slug, ?Request $request = null): ?string
    {
        $slug = trim($slug);
        if ($slug === '') {
            return null;
        }

        $candidates = $this->buildPathCandidates("/blog/{$slug}");
        $candidates[] = $slug;
        $candidates[] = "/{$slug}";

        if ($request) {
            $path = '/' . ltrim($request->path(), '/');
            $candidates = array_merge($candidates, $this->buildPathCandidates($path));
        }

        $candidates = array_unique(array_filter($candidates));
        $queryString = $request ? $request->getQueryString() : null;

        foreach ($candidates as $candidate) {
            $record = $this->findActiveRedirect($candidate);
            if (! $record) {
                continue;
            }

            $validatedDestination = $this->validateDestination($record['old_url'], $record['new_url']);
            if ($validatedDestination !== null) {
                $finalUrl = $this->attachQueryString($validatedDestination, $queryString);
                $this->incrementHit($record['id']);

                return $finalUrl;
            }
        }

        return null;
    }

    /**
     * Tra cứu một chuyển hướng đang hoạt động dựa trên đường dẫn / slug
     * Tối ưu bộ nhớ Cache O(1)
     */
    public function findActiveRedirect(string $candidate): ?array
    {
        $normalized = Redirect::normalizePath($candidate);
        $hash = md5($normalized);

        // Kiểm tra qua bộ nhớ cache mảng tích hợp
        $map = $this->getActiveRedirectsMap();
        if ($map !== null) {
            if (isset($map[$hash])) {
                return $map[$hash];
            }

            // Thử thêm hash thô
            $rawHash = md5(trim($candidate));
            if ($rawHash !== $hash && isset($map[$rawHash])) {
                return $map[$rawHash];
            }

            return null;
        }

        // Trường hợp số lượng quá lớn (> 10.000), tra cứu cache theo từng khóa O(1)
        return Cache::remember("redirect:lookup:{$hash}", 3600, function () use ($hash, $candidate) {
            $record = Redirect::active()
                ->where(function ($q) use ($hash, $candidate) {
                    $q->where('old_url_hash', $hash)
                      ->orWhere('old_url', $candidate);
                })
                ->first(['id', 'old_url', 'new_url', 'status_code']);

            if (! $record) {
                return null;
            }

            return [
                'id' => (int) $record->id,
                'old_url' => $record->old_url,
                'new_url' => $record->new_url,
                'status_code' => (int) ($record->status_code ?: 301),
            ];
        });
    }

    /**
     * Nạp toàn bộ các chuyển hướng đang kích hoạt vào mảng Cache O(1)
     * Rất nhẹ, truy cập tức thì < 0.01ms, 0 database queries
     */
    public function getActiveRedirectsMap(): ?array
    {
        // Kiểm tra xem số lượng có phù hợp để cache toàn bộ không (< 10.000 bản ghi)
        $totalActive = Cache::remember('redirects:count:active', 3600, function () {
            return DB::table('redirects')->where('is_active', 1)->count();
        });

        if ($totalActive > 10000) {
            return null; // Quá lớn, để dùng tra cứu theo từng hash
        }

        if ($totalActive === 0) {
            return [];
        }

        return Cache::remember('redirects:active_map', 3600, function () {
            $rows = DB::table('redirects')
                ->where('is_active', 1)
                ->select(['id', 'old_url', 'old_url_hash', 'new_url', 'status_code'])
                ->get();

            $map = [];
            foreach ($rows as $row) {
                $hash = $row->old_url_hash ?: md5(Redirect::normalizePath($row->old_url));
                $map[$hash] = [
                    'id' => (int) $row->id,
                    'old_url' => $row->old_url,
                    'new_url' => $row->new_url,
                    'status_code' => (int) ($row->status_code ?: 301),
                ];
            }

            return $map;
        });
    }

    /**
     * Tạo danh sách các biến thể có thể có của đường dẫn hiện tại
     */
    public function buildPathCandidates(string $path): array
    {
        $normalized = Redirect::normalizePath($path);
        $candidates = [$normalized];

        // Nếu bắt đầu bằng /blog/, thêm biến thể không có /blog/
        if (str_starts_with($normalized, '/blog/')) {
            $subPath = substr($normalized, 5); // "/ten-bai-viet"
            $candidates[] = $subPath;
            $candidates[] = ltrim($subPath, '/'); // "ten-bai-viet"
        } else {
            // Nếu không có /blog/ và chỉ là 1 cấp đường dẫn (/ten-bai-viet)
            $trimmed = ltrim($normalized, '/');
            if ($trimmed !== '' && ! str_contains($trimmed, '/')) {
                $candidates[] = "/blog/{$trimmed}";
                $candidates[] = $trimmed;
            }
        }

        $candidates[] = '/' . ltrim($path, '/');
        $candidates[] = rtrim($path, '/');

        return array_values(array_unique(array_filter($candidates)));
    }

    /**
     * Xác thực và chuẩn hóa link đích:
     * - Chống lặp A -> A
     * - Chống chuỗi lặp A -> B -> A
     * - Chuẩn hóa link bài viết, danh mục, URL tuyệt đối
     */
    public function validateDestination(string $oldUrl, string $newUrl): ?string
    {
        $oldNormalized = Redirect::normalizePath($oldUrl);
        $newNormalized = Redirect::normalizePath($newUrl);

        // 1. Chống lặp chính nó: Link mới trùng link cũ
        if ($oldNormalized === $newNormalized) {
            return null;
        }

        // 2. Chống chuỗi lặp A -> B -> A
        $secondHopHash = md5($newNormalized);
        $reverseRedirect = $this->findActiveRedirect($newNormalized);
        if ($reverseRedirect) {
            $reverseTargetNormalized = Redirect::normalizePath($reverseRedirect['new_url']);
            if ($reverseTargetNormalized === $oldNormalized) {
                return null;
            }
        }

        // 3. Nếu là URL đầy đủ có giao thức (http:// hoặc https://)
        if (filter_var($newUrl, FILTER_VALIDATE_URL)) {
            return $newUrl;
        }

        // 4. Nếu là đường dẫn nội bộ bắt đầu bằng "/"
        if (str_starts_with($newUrl, '/')) {
            return url($newUrl);
        }

        // 5. Nếu là slug thuần (không có dấu '/')
        $slug = trim($newUrl);
        // Kiểm tra xem có phải bài viết blog không
        $isPost = Cache::remember("post:exists:{$slug}", 300, function () use ($slug) {
            return Post::published()->where('slug', $slug)->exists();
        });
        if ($isPost) {
            return url("/blog/{$slug}");
        }

        // Kiểm tra xem có phải danh mục sản phẩm không
        $isCategory = Cache::remember("category:exists:{$slug}", 300, function () use ($slug) {
            return Category::active()->where('slug', $slug)->exists();
        });
        if ($isCategory) {
            return url("/{$slug}");
        }

        return url('/' . ltrim($newUrl, '/'));
    }

    /**
     * Gắn thêm Query String từ request cũ vào link mới nếu có
     */
    public function attachQueryString(string $url, ?string $queryString): string
    {
        $queryString = trim((string) $queryString);
        if ($queryString === '') {
            return $url;
        }

        $separator = str_contains($url, '?') ? '&' : '?';

        return $url . $separator . $queryString;
    }

    /**
     * Tăng số lượt kích hoạt redirect mà không ảnh hưởng hiệu năng
     */
    public function incrementHit(int $redirectId): void
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
