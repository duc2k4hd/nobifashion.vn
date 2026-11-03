<?php

namespace App\Http\Controllers\Admins\Reels;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Reel;
use App\Services\Cloudflare\CloudflareR2Service;
use App\Services\ReelsAdmin\ReelsAdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CloudflareR2AdminController extends Controller
{
    protected CloudflareR2Service $r2Service;
    protected ReelsAdminService $adminService;

    public function __construct(CloudflareR2Service $r2Service, ReelsAdminService $adminService)
    {
        $this->r2Service = $r2Service;
        $this->adminService = $adminService;
    }

    /**
     * Màn hình chính quản trị Cloudflare R2 Storage Explorer & Import (Phân trang tối ưu 15 tệp/trang)
     */
    public function index(Request $request): View
    {
        $prefix = trim((string) $request->get('prefix', 'reels/'));
        $search = trim((string) $request->get('search', ''));
        $filterStatus = $request->get('status', 'all'); // all | imported | not_imported
        $page = max(1, (int) $request->get('page', 1));
        $perPage = 15; // Giới hạn chuẩn 15 tệp/trang để tải siêu tốc kể cả khi có 1 triệu video

        // Quản lý token phân trang S3 để hỗ trợ lùi trang trước / tiến trang sau
        $tokenSessionKey = 'r2_tokens_' . md5($prefix);
        $tokenMap = session()->get($tokenSessionKey, [1 => null]);
        if ($page === 1) {
            $continuationToken = null;
            $tokenMap = [1 => null];
        } else {
            $continuationToken = $tokenMap[$page] ?? $request->get('cursor');
        }

        // 1. Thống kê dung lượng Cloudflare R2
        $metrics = $this->r2Service->getStorageMetrics();

        // 2. Lấy đúng 15 tệp trên Cloudflare R2 cho trang hiện tại
        $r2Result = $this->r2Service->listObjects($prefix, $continuationToken, $perPage);
        $rawItems = $r2Result['items'] ?? [];

        // Lưu token cho trang kế tiếp nếu còn dữ liệu
        if (!empty($r2Result['is_truncated']) && !empty($r2Result['next_token'])) {
            $tokenMap[$page + 1] = $r2Result['next_token'];
            session()->put($tokenSessionKey, $tokenMap);
        }

        // 3. Tối ưu hiệu năng: Chỉ kiểm tra đúng 15 video của trang hiện tại trong database
        $currentUrls = array_column($rawItems, 'public_url');
        $existingMap = [];
        if (!empty($currentUrls)) {
            $matchedReels = Reel::whereIn('video_url', $currentUrls)
                ->select(['id', 'title', 'slug', 'video_url', 'view_count'])
                ->get();
            foreach ($matchedReels as $r) {
                $existingMap[$r->video_url] = $r;
            }
        }

        // 4. Ghép nối trạng thái cho 15 tệp trên trang hiện tại
        $items = [];
        $pageImportedCount = 0;
        $pageNotImportedCount = 0;

        foreach ($rawItems as $item) {
            $matchedReel = $existingMap[$item['public_url']] ?? null;
            $isImported = !empty($matchedReel);

            if ($isImported) {
                $pageImportedCount++;
            } else {
                $pageNotImportedCount++;
            }

            // Bộ lọc tìm kiếm
            if (!empty($search)) {
                if (stripos($item['file_name'], $search) === false && stripos($item['key'], $search) === false) {
                    continue;
                }
            }

            if ($filterStatus === 'imported' && !$isImported) {
                continue;
            }

            if ($filterStatus === 'not_imported' && $isImported) {
                continue;
            }

            $item['is_imported'] = $isImported;
            $item['reel'] = $matchedReel;
            $items[] = $item;
        }

        $brands = Brand::select(['id', 'name'])->get();
        $hasNextPage = !empty($r2Result['is_truncated']) && !empty($r2Result['next_token']);
        $hasPrevPage = $page > 1;

        return view('admins.reels.cloudflare.index', compact(
            'metrics',
            'items',
            'r2Result',
            'prefix',
            'search',
            'filterStatus',
            'page',
            'perPage',
            'hasNextPage',
            'hasPrevPage',
            'pageImportedCount',
            'pageNotImportedCount',
            'brands'
        ));
    }

    /**
     * Import các video đã chọn từ Cloudflare R2 vào cơ sở dữ liệu Nobi Reels
     */
    public function importSelected(Request $request): RedirectResponse
    {
        $keys = $request->input('keys');
        if (empty($keys) || !is_array($keys)) {
            return back()->with('error', 'Vui lòng tích chọn ít nhất 1 video để nhập dữ liệu.');
        }

        $category = $request->input('category', 'trending');
        $defaultBrandId = $request->input('brand_id');
        $status = $request->input('status', 'published');

        $brands = Brand::all();
        $imported = 0;
        $skipped = 0;

        foreach ($keys as $key) {
            $key = trim($key);
            if (empty($key)) continue;

            $publicUrl = $this->r2Service->getPublicUrl($key);

            // Kiểm tra chống trùng lặp theo video_url hoặc key
            $exists = Reel::where('video_url', $publicUrl)
                ->orWhere('video_url', 'like', "%{$key}%")
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            // Tự động phân tích Brand từ đường dẫn (ví dụ: reels/coolmate/...)
            $brandId = $defaultBrandId ?: null;
            if (!$brandId) {
                $pathSegments = explode('/', $key);
                if (count($pathSegments) >= 2) {
                    $folderName = strtolower($pathSegments[1]);
                    $matchedBrand = $brands->first(function ($b) use ($folderName) {
                        return Str::slug($b->name) === Str::slug($folderName) || stripos($b->name, $folderName) !== false;
                    });
                    if ($matchedBrand) {
                        $brandId = $matchedBrand->id;
                    }
                }
            }

            // Tự động tạo tiêu đề đẹp từ tên file
            $title = $this->generateTitleFromKey($key);
            $slug = Str::slug($title);

            // Đảm bảo slug là duy nhất
            $originalSlug = $slug;
            $counter = 1;
            while (Reel::where('slug', $slug)->exists()) {
                $slug = "{$originalSlug}-" . $counter++;
            }

            Reel::create([
                'title' => $title,
                'slug' => $slug,
                'external_id' => 'R2_' . strtoupper(substr(md5($key), 0, 10)),
                'video_url' => $publicUrl,
                'poster_url' => asset('clients/assets/img/clothes/no-image.webp'),
                'thumbnail_url' => asset('clients/assets/img/clothes/no-image.webp'),
                'caption' => $title . ' — Phong cách thời trang cao cấp Nobi Fashion.',
                'category' => $category,
                'brand_id' => $brandId,
                'hashtags' => ['NobiFashion', 'Reels', 'OOTD', 'ThoiTrang'],
                'duration' => 15,
                'aspect_ratio' => '9:16',
                'playback_type' => 'mp4',
                'video_provider' => 'cloudflare_r2',
                'status' => $status,
                'published_at' => now(),
            ]);

            $imported++;
        }

        $this->adminService->logAudit('cloudflare_r2_import_selected', [
            'total_selected' => count($keys),
            'imported' => $imported,
            'skipped' => $skipped,
        ]);

        return back()->with('success', "Nhập dữ liệu thành công! Đã thêm {$imported} video từ Cloudflare R2 vào Nobi Reels (bỏ qua {$skipped} video đã tồn tại).");
    }

    /**
     * Tự động quét toàn bộ Bucket Cloudflare R2 và import tất cả video chưa có trong database
     */
    public function syncAll(Request $request): RedirectResponse
    {
        $prefix = trim((string) $request->input('prefix', 'reels/'));
        $category = $request->input('category', 'trending');
        $brands = Brand::all();

        $continuationToken = null;
        $totalImported = 0;
        $totalSkipped = 0;

        // Đọc toàn bộ video_url hiện có để đối chiếu nhanh
        $existingUrls = Reel::pluck('video_url')->toArray();
        $existingUrlSet = array_flip($existingUrls);

        do {
            $res = $this->r2Service->listObjects($prefix, $continuationToken, 500);
            if (!$res['success']) {
                break;
            }

            $items = $res['items'] ?? [];
            foreach ($items as $item) {
                if (!$item['is_video']) continue;

                $key = $item['key'];
                $publicUrl = $item['public_url'];

                if (isset($existingUrlSet[$publicUrl])) {
                    $totalSkipped++;
                    continue;
                }

                // Nhận diện Brand
                $brandId = null;
                $pathSegments = explode('/', $key);
                if (count($pathSegments) >= 2) {
                    $folderName = strtolower($pathSegments[1]);
                    $matchedBrand = $brands->first(fn($b) => Str::slug($b->name) === Str::slug($folderName));
                    if ($matchedBrand) $brandId = $matchedBrand->id;
                }

                $title = $this->generateTitleFromKey($key);
                $slug = Str::slug($title);
                $origSlug = $slug;
                $counter = 1;
                while (Reel::where('slug', $slug)->exists()) {
                    $slug = "{$origSlug}-" . $counter++;
                }

                Reel::create([
                    'title' => $title,
                    'slug' => $slug,
                    'external_id' => 'R2_' . strtoupper(substr(md5($key), 0, 10)),
                    'video_url' => $publicUrl,
                    'poster_url' => asset('clients/assets/img/clothes/no-image.webp'),
                    'thumbnail_url' => asset('clients/assets/img/clothes/no-image.webp'),
                    'caption' => $title . ' — Phong cách thời trang cao cấp Nobi Fashion.',
                    'category' => $category,
                    'brand_id' => $brandId,
                    'hashtags' => ['NobiFashion', 'Reels', 'OOTD', 'ThoiTrang'],
                    'duration' => 15,
                    'aspect_ratio' => '9:16',
                    'playback_type' => 'mp4',
                    'video_provider' => 'cloudflare_r2',
                    'status' => 'published',
                    'published_at' => now(),
                ]);

                $existingUrlSet[$publicUrl] = true;
                $totalImported++;
            }

            $continuationToken = $res['is_truncated'] ? $res['next_token'] : null;
        } while (!empty($continuationToken));

        $this->adminService->logAudit('cloudflare_r2_sync_all', [
            'imported' => $totalImported,
            'skipped' => $totalSkipped,
        ]);

        return back()->with('success', "Đồng bộ hoàn tất! Đã tự động thêm {$totalImported} video mới từ Cloudflare R2 vào Nobi Reels (bỏ qua {$totalSkipped} video đã có sẵn).");
    }

    /**
     * Tải tệp video từ máy lên Cloudflare R2 trực tiếp từ Admin
     */
    public function upload(Request $request): RedirectResponse
    {
        $request->validate([
            'video_file' => ['required', 'file', 'mimes:mp4,mov,webm', 'max:102400'], // tối đa 100MB
            'folder' => ['nullable', 'string', 'max:50'],
            'title' => ['nullable', 'string', 'max:255'],
            'auto_import' => ['nullable'],
        ]);

        $file = $request->file('video_file');
        $folder = trim((string) $request->input('folder', 'reels/uploads'), '/');
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $file->getClientOriginalExtension();

        $safeName = Str::slug($originalName) . '_' . time() . '.' . $extension;
        $key = "{$folder}/{$safeName}";

        $fileContent = file_get_contents($file->getRealPath());
        $mimeType = $file->getClientMimeType() ?: 'video/mp4';

        $uploadResult = $this->r2Service->uploadObject($key, $fileContent, $mimeType);

        if (!$uploadResult['success']) {
            return back()->with('error', $uploadResult['error'] ?? 'Không thể tải tệp lên Cloudflare R2');
        }

        $publicUrl = $uploadResult['public_url'];

        // Nếu admin tích chọn tự động thêm vào Reels ngay
        if ($request->has('auto_import')) {
            $title = $request->input('title') ?: $this->generateTitleFromKey($key);
            $slug = Str::slug($title);
            $origSlug = $slug;
            $counter = 1;
            while (Reel::where('slug', $slug)->exists()) {
                $slug = "{$origSlug}-" . $counter++;
            }

            Reel::create([
                'title' => $title,
                'slug' => $slug,
                'external_id' => 'R2_' . strtoupper(substr(md5($key), 0, 10)),
                'video_url' => $publicUrl,
                'poster_url' => asset('clients/assets/img/clothes/no-image.webp'),
                'thumbnail_url' => asset('clients/assets/img/clothes/no-image.webp'),
                'caption' => $title . ' — Video thời trang Nobi Fashion.',
                'category' => 'new',
                'hashtags' => ['NobiFashion', 'Reels', 'NewArrival'],
                'duration' => 15,
                'aspect_ratio' => '9:16',
                'playback_type' => 'mp4',
                'video_provider' => 'cloudflare_r2',
                'status' => 'published',
                'published_at' => now(),
            ]);
        }

        $this->adminService->logAudit('cloudflare_r2_upload_file', [
            'key' => $key,
            'size' => strlen($fileContent),
            'auto_imported' => $request->has('auto_import'),
        ]);

        return back()->with('success', "Tải lên Cloudflare R2 thành công! Đường dẫn tệp: {$publicUrl}");
    }

    /**
     * Xóa tệp từ xa trên Cloudflare R2
     */
    public function delete(Request $request): RedirectResponse
    {
        $request->validate([
            'key' => ['required', 'string'],
        ]);

        $key = $request->input('key');
        $res = $this->r2Service->deleteObject($key);

        if ($res['success']) {
            $this->adminService->logAudit('cloudflare_r2_delete_file', ['key' => $key]);
            return back()->with('success', "Đã xóa tệp {$key} trên Cloudflare R2 thành công.");
        }

        return back()->with('error', $res['error'] ?? 'Lỗi khi xóa tệp trên Cloudflare R2');
    }

    /**
     * Tạo tiêu đề tự nhiên có dấu từ tên file hoặc Key
     * Ví dụ: 2026-06-17_cool-coolmate_fan-lamine-yamal-co-ao-bong-da-world-cup-chua_76522938.mp4
     * -> "Fan Lamine Yamal Co Ao Bong Da World Cup Chua"
     */
    protected function generateTitleFromKey(string $key): string
    {
        $filename = pathinfo($key, PATHINFO_FILENAME);

        // Bỏ phần ngày tháng ở đầu nếu có (2026-06-17_...)
        $filename = preg_replace('/^\d{4}-\d{2}-\d{2}_[a-zA-Z0-9-]+_/', '', $filename);

        // Bỏ ID số ở cuối nếu có (_7652293846549662983)
        $filename = preg_replace('/_\d+$/', '', $filename);

        // Thay dấu gạch ngang, gạch dưới bằng khoảng trắng
        $cleaned = str_replace(['-', '_'], ' ', $filename);
        $cleaned = preg_replace('/\s+/', ' ', trim($cleaned));

        return mb_convert_case($cleaned, MB_CASE_TITLE, 'UTF-8');
    }
}

