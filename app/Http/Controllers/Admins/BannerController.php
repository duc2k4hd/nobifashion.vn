<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BannerRequest;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class BannerController extends Controller
{
    /**
     * Xóa cache trang chủ chứa danh sách banners
     */
    public static function clearBannerCache(): void
    {
        Cache::forget('home.page.payload.v8');
    }

    public function index(Request $request)
    {
        $query = Banner::query();

        if ($keyword = $request->get('keyword')) {
            $query->where('title', 'like', "%{$keyword}%");
        }

        if ($position = $request->get('position')) {
            $query->where('position', $position);
        }

        if (($status = $request->get('status')) !== null && $status !== '') {
            $query->where('is_active', (bool) $status);
        }

        $banners = $query->orderBy('order', 'asc')
            ->orderByDesc('start_at')
            ->orderByDesc('id')
            ->paginate(12)
            ->appends($request->query());

        $positions = config('banners.positions', []);
        $positionBadges = config('banners.position_badges', []);

        $stats = [
            'total' => Banner::count(),
            'active' => Banner::where('is_active', true)->count(),
            'inactive' => Banner::where('is_active', false)->count(),
            'home' => Banner::where('position', 'home')->count(),
            'home_banner' => Banner::where('position', 'home_banner')->count(),
            'shop' => Banner::where('position', 'shop')->count(),
        ];

        return view('admins.banners.index', compact('banners', 'positions', 'positionBadges', 'stats'));
    }

    public function create()
    {
        $banner = new Banner();
        $positions = config('banners.positions', []);
        return view('admins.banners.create', compact('banner', 'positions'));
    }

    public function store(BannerRequest $request)
    {
        $data = $this->preparePayload($request);
        Banner::create($data);
        self::clearBannerCache();

        return redirect()->route('admin.banners.index')
            ->with('success', 'Tạo banner thành công.');
    }

    public function edit(Banner $banner)
    {
        $positions = config('banners.positions', []);
        return view('admins.banners.edit', compact('banner', 'positions'));
    }

    public function update(BannerRequest $request, Banner $banner)
    {
        $data = $this->preparePayload($request, $banner);
        $banner->update($data);
        self::clearBannerCache();

        return redirect()->route('admin.banners.edit', $banner)
            ->with('success', 'Cập nhật banner thành công.');
    }

    public function destroy(Banner $banner)
    {
        $this->deleteImages($banner);
        $banner->delete();
        self::clearBannerCache();

        return back()->with('success', 'Đã xoá banner.');
    }

    public function toggle(Banner $banner)
    {
        $banner->update(['is_active' => !$banner->is_active]);
        self::clearBannerCache();

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'is_active' => (bool) $banner->is_active,
                'message' => $banner->is_active ? 'Đã bật hiển thị banner.' : 'Đã tạm tắt banner.',
            ]);
        }

        return back()->with('success', 'Đã cập nhật trạng thái banner.');
    }

    public function reorder(Request $request)
    {
        $orders = $request->input('orders');
        $ids = $request->input('ids');

        if (!is_array($orders) && !is_array($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu sắp xếp không hợp lệ.',
            ], 422);
        }

        DB::transaction(function () use ($orders, $ids) {
            if (is_array($orders)) {
                foreach ($orders as $item) {
                    if (isset($item['id'], $item['order'])) {
                        Banner::where('id', $item['id'])->update(['order' => (int) $item['order']]);
                    }
                }
            } elseif (is_array($ids)) {
                foreach ($ids as $index => $id) {
                    Banner::where('id', $id)->update(['order' => $index + 1]);
                }
            }
        });

        // Xóa cache trang chủ để cập nhật thứ tự mới ngay lập tức
        self::clearBannerCache();

        return response()->json([
            'success' => true,
            'message' => 'Đã lưu thứ tự banner thành công.',
        ]);
    }

    private function preparePayload(BannerRequest $request, ?Banner $banner = null): array
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        $desktopFile = $request->file('image_desktop');
        $mobileFile = $request->file('image_mobile');

        // 1. Nếu có tải lên ảnh Desktop mới
        if ($desktopFile) {
            $desktopFilename = $this->handleImage($desktopFile, $banner?->image_desktop, 'banner-desktop');
            $data['image_desktop'] = basename($desktopFilename);

            // 2. Nếu người dùng chọn tải ảnh Mobile riêng biệt
            if ($mobileFile) {
                $mobileFilename = $this->handleImage($mobileFile, $banner?->image_mobile, 'banner-mobile');
                $data['image_mobile'] = basename($mobileFilename);
            } else {
                // Nếu KHÔNG chọn ảnh Mobile -> Tự động thu nhỏ từ ảnh Desktop
                $autoMobileFilename = $this->createMobileImageFromDesktop($data['image_desktop']);
                if ($autoMobileFilename) {
                    if ($banner?->image_mobile && $banner->image_mobile !== $banner->image_desktop) {
                        $this->deleteImageFile($banner->image_mobile);
                    }
                    $data['image_mobile'] = basename($autoMobileFilename);
                } elseif ($banner) {
                    $data['image_mobile'] = basename($banner->image_mobile);
                }
            }
        } else {
            // Không thay đổi ảnh Desktop (trường hợp Edit)
            $data['image_desktop'] = $banner?->image_desktop ? basename($banner->image_desktop) : null;

            if ($mobileFile) {
                // Người dùng chỉ cập nhật riêng ảnh Mobile
                $mobileFilename = $this->handleImage($mobileFile, $banner?->image_mobile, 'banner-mobile');
                $data['image_mobile'] = basename($mobileFilename);
            } else {
                // Giữ nguyên ảnh Mobile hiện tại
                $data['image_mobile'] = $banner?->image_mobile ? basename($banner->image_mobile) : null;
            }
        }

        // Tự động set order nếu không có (chỉ khi tạo mới)
        if (!$banner && (!isset($data['order']) || $data['order'] === null)) {
            $data['order'] = Banner::getNextOrderForPosition($data['position']);
        } elseif (isset($data['order']) && $data['order'] === null) {
            // Nếu order là null khi update, giữ nguyên giá trị cũ
            unset($data['order']);
        }

        return $data;
    }

    /**
     * Tự động tạo ảnh Mobile từ ảnh Desktop bằng cách thu nhỏ kích thước (chiều rộng tối đa 768px)
     */
    private function createMobileImageFromDesktop(string $desktopFilename): ?string
    {
        $dir = public_path('clients/assets/img/banners');
        $cleanDesktop = basename($desktopFilename);
        $sourcePath = $dir . DIRECTORY_SEPARATOR . $cleanDesktop;

        if (!File::exists($sourcePath)) {
            return null;
        }

        $pathInfo = pathinfo($cleanDesktop);
        $ext = strtolower($pathInfo['extension'] ?? 'webp');
        $mobileFilename = 'banner-mobile-' . time() . '-' . uniqid() . '.' . $ext;
        $targetPath = $dir . DIRECTORY_SEPARATOR . $mobileFilename;

        $targetWidth = 768; // Chiều rộng chuẩn tối ưu hiển thị Mobile
        $quality = 85;

        $size = @getimagesize($sourcePath);
        if (!$size) {
            @copy($sourcePath, $targetPath);
            @chmod($targetPath, 0644);
            return $mobileFilename;
        }

        $origW = $size[0];
        $origH = $size[1];
        $type = $size[2] ?? 0;

        // Nếu ảnh desktop đã nhỏ hơn hoặc bằng 768px thì chỉ cần sao chép
        if ($origW <= $targetWidth) {
            @copy($sourcePath, $targetPath);
            @chmod($targetPath, 0644);
            return $mobileFilename;
        }

        $newW = $targetWidth;
        $newH = (int) round($origH * ($newW / $origW));

        $img = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($sourcePath),
            IMAGETYPE_PNG => @imagecreatefrompng($sourcePath),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($sourcePath) : null,
            IMAGETYPE_AVIF => function_exists('imagecreatefromavif') ? @imagecreatefromavif($sourcePath) : null,
            default => null,
        };

        if (!$img) {
            @copy($sourcePath, $targetPath);
            @chmod($targetPath, 0644);
            return $mobileFilename;
        }

        $canvas = imagecreatetruecolor($newW, $newH);

        // Bảo toàn độ trong suốt cho PNG, WEBP, AVIF
        if (in_array($type, [IMAGETYPE_PNG, IMAGETYPE_WEBP, IMAGETYPE_AVIF])) {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $transparent = imagecolorallocatealpha($canvas, 255, 255, 255, 127);
            imagefilledrectangle($canvas, 0, 0, $newW, $newH, $transparent);
        }

        imagecopyresampled($canvas, $img, 0, 0, 0, 0, $newW, $newH, $origW, $origH);

        $saved = false;
        switch ($type) {
            case IMAGETYPE_JPEG:
                $saved = @imagejpeg($canvas, $targetPath, $quality);
                break;
            case IMAGETYPE_PNG:
                $saved = @imagepng($canvas, $targetPath, 7);
                break;
            case IMAGETYPE_WEBP:
                $saved = function_exists('imagewebp') ? @imagewebp($canvas, $targetPath, $quality) : false;
                break;
            case IMAGETYPE_AVIF:
                $saved = function_exists('imageavif') ? @imageavif($canvas, $targetPath, $quality) : false;
                break;
        }

        imagedestroy($canvas);
        imagedestroy($img);

        if (!$saved || !File::exists($targetPath)) {
            @copy($sourcePath, $targetPath);
        }

        @chmod($targetPath, 0644);
        return $mobileFilename;
    }

    private function handleImage($file, ?string $current = null, string $prefix = 'banner'): ?string
    {
        if (!$file) {
            return $current ? basename($current) : null;
        }

        $directory = public_path('clients/assets/img/banners');
        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $extension = $file->getClientOriginalExtension() ?: 'webp';
        $filename = $prefix . '-' . time() . '-' . uniqid() . '.' . $extension;
        $file->move($directory, $filename);
        @chmod($directory . DIRECTORY_SEPARATOR . $filename, 0644);

        if ($current) {
            $this->deleteImageFile($current);
        }

        return basename($filename);
    }

    private function deleteImages(Banner $banner): void
    {
        $this->deleteImageFile($banner->image_desktop);
        if ($banner->image_mobile && $banner->image_mobile !== $banner->image_desktop) {
            $this->deleteImageFile($banner->image_mobile);
        }
    }

    private function deleteImageFile(?string $filename): void
    {
        if (!$filename) {
            return;
        }

        $cleanFilename = basename($filename);
        $path = public_path('clients/assets/img/banners/' . $cleanFilename);
        if (File::exists($path)) {
            File::delete($path);
        }
    }
}


