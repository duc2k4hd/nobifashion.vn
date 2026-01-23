<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BannerRequest;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class BannerController extends Controller
{
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
            ->paginate(20)
            ->appends($request->query());

        $positions = config('banners.positions', []);
        $positionBadges = config('banners.position_badges', []);

        return view('admins.banners.index', compact('banners', 'positions', 'positionBadges'));
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

        return redirect()->route('admin.banners.edit', $banner)
            ->with('success', 'Cập nhật banner thành công.');
    }

    public function destroy(Banner $banner)
    {
        $this->deleteImages($banner);
        $banner->delete();

        return back()->with('success', 'Đã xoá banner.');
    }

    public function toggle(Banner $banner)
    {
        $banner->update(['is_active' => !$banner->is_active]);
        return back()->with('success', 'Đã cập nhật trạng thái banner.');
    }

    private function preparePayload(BannerRequest $request, ?Banner $banner = null): array
    {
        $data = $request->validated();
        $data['image_desktop'] = $this->handleImage($request->file('image_desktop'), $banner?->image_desktop);
        $data['image_mobile'] = $this->handleImage($request->file('image_mobile'), $banner?->image_mobile);
        $data['is_active'] = $request->boolean('is_active', true);

        // Tự động set order nếu không có (chỉ khi tạo mới)
        if (!$banner && (!isset($data['order']) || $data['order'] === null)) {
            $data['order'] = Banner::getNextOrderForPosition($data['position']);
        } elseif (isset($data['order']) && $data['order'] === null) {
            // Nếu order là null khi update, giữ nguyên giá trị cũ
            unset($data['order']);
        }

        return $data;
    }

    private function handleImage($file, ?string $current = null): ?string
    {
        if (!$file) {
            return $current;
        }

        $directory = public_path('clients/assets/img/banners');
        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $filename = 'banner-' . time() . '-' . uniqid() . '.' . $file->getClientOriginalExtension();
        $file->move($directory, $filename);

        if ($current) {
            $this->deleteImageFile($current);
        }

        return $filename;
    }

    private function deleteImages(Banner $banner): void
    {
        $this->deleteImageFile($banner->image_desktop);
        $this->deleteImageFile($banner->image_mobile);
    }

    private function deleteImageFile(?string $filename): void
    {
        if (!$filename) {
            return;
        }

        $path = public_path('clients/assets/img/banners/' . $filename);
        if (File::exists($path)) {
            File::delete($path);
        }
    }
}


