<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BrandRequest;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BrandController extends Controller
{
    public function index(Request $request)
    {
        $query = Brand::query()->withCount('products');

        if ($keyword = trim((string) $request->get('keyword'))) {
            $query->where(function ($builder) use ($keyword) {
                $builder->where('name', 'like', '%' . $keyword . '%')
                    ->orWhere('slug', 'like', '%' . $keyword . '%');
            });
        }

        if ($status = $request->get('status')) {
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $brands = $query
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20)
            ->appends($request->query());

        return view('admins.brands.index', compact('brands'));
    }

    public function create()
    {
        return view('admins.brands.form', [
            'brand' => new Brand(),
        ]);
    }

    public function store(BrandRequest $request)
    {
        $data = $request->validated();
        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);

        if ($request->hasFile('logo')) {
            $data['logo'] = $this->uploadLogo($request->file('logo'));
        }

        Brand::create($data);

        return redirect()
            ->route('admin.brands.index')
            ->with('success', 'Tạo hãng thành công.');
    }

    public function edit(Brand $brand)
    {
        return view('admins.brands.form', compact('brand'));
    }

    public function update(BrandRequest $request, Brand $brand)
    {
        $data = $request->validated();
        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);

        if ($request->hasFile('logo')) {
            $this->deleteLogoFile($brand->logo);
            $data['logo'] = $this->uploadLogo($request->file('logo'));
        }

        $brand->update($data);

        return redirect()
            ->route('admin.brands.index')
            ->with('success', 'Cập nhật hãng thành công.');
    }

    public function destroy(Brand $brand)
    {
        $logo = $brand->logo;

        DB::transaction(function () use ($brand) {
            $brand->delete();
        });

        $this->deleteLogoFile($logo);

        return redirect()
            ->route('admin.brands.index')
            ->with('success', 'Đã xóa hãng thành công. Các sản phẩm liên quan sẽ tự bỏ gán hãng.');
    }

    public function toggleStatus(Brand $brand)
    {
        $brand->update([
            'is_active' => ! $brand->is_active,
        ]);

        return back()->with('success', 'Đã cập nhật trạng thái hãng.');
    }

    public function bulkAction(Request $request)
    {
        $request->validate([
            'selected' => ['required', 'array'],
            'selected.*' => ['integer', 'exists:brands,id'],
            'bulk_action' => ['required', 'in:hide,show,delete'],
        ]);

        $ids = $request->input('selected', []);
        $action = $request->input('bulk_action');
        $brands = Brand::whereIn('id', $ids)->get();

        if ($action === 'hide') {
            Brand::whereIn('id', $ids)->update(['is_active' => false]);

            return back()->with('success', 'Đã ẩn ' . count($ids) . ' hãng.');
        }

        if ($action === 'show') {
            Brand::whereIn('id', $ids)->update(['is_active' => true]);

            return back()->with('success', 'Đã hiển thị ' . count($ids) . ' hãng.');
        }

        if ($action === 'delete') {
            $logos = $brands->pluck('logo')->filter()->values()->all();

            DB::transaction(function () use ($ids) {
                Brand::whereIn('id', $ids)->delete();
            });

            foreach ($logos as $logo) {
                $this->deleteLogoFile($logo);
            }

            return back()->with('success', 'Đã xóa ' . count($ids) . ' hãng. Các sản phẩm liên quan sẽ tự bỏ gán hãng.');
        }

        return back()->with('error', 'Hành động không hợp lệ.');
    }

    private function uploadLogo($file): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $filename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
            . '-' . now()->format('YmdHis')
            . '-' . Str::random(6)
            . '.' . $extension;
        $directory = public_path(trim((string) config('media.directories.brands', 'clients/assets/img/brands'), '/'));

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $file->move($directory, $filename);
        @chmod($directory . DIRECTORY_SEPARATOR . $filename, 0644);

        return $filename;
    }

    private function deleteLogoFile(?string $filename): void
    {
        if (! $filename) {
            return;
        }

        $path = public_path(trim((string) config('media.directories.brands', 'clients/assets/img/brands'), '/') . '/' . $filename);
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
