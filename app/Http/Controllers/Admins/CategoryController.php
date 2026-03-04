<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Category::query()->with('parent');

        if ($keyword = $request->get('keyword')) {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', '%' . $keyword . '%')
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

        $categories = $query
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20)
            ->appends($request->query());

        return view('admins.categories.index', compact('categories'));
    }

    public function create()
    {
        $category = new Category();
        $parents = Category::orderBy('sort_order')->orderBy('name')->get();

        return view('admins.categories.form', compact('category', 'parents'));
    }

    public function store(CategoryRequest $request)
    {
        $data = $request->validated();

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        if ($request->hasFile('image')) {
            $data['image'] = $this->uploadImage($request->file('image'));
        }

        Category::create($data);

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Tạo danh mục thành công.');
    }

    public function edit(Category $category)
    {
        $parents = Category::where('id', '!=', $category->id)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admins.categories.form', compact('category', 'parents'));
    }

    public function update(CategoryRequest $request, Category $category)
    {
        $data = $request->validated();

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        if ($request->hasFile('image')) {
            $this->deleteImageFile($category->image);
            $data['image'] = $this->uploadImage($request->file('image'));
        }

        $category->update($data);

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Cập nhật danh mục thành công.');
    }

    public function toggleStatus(Category $category)
    {
        $category->update(['is_active' => !$category->is_active]);

        return back()->with('success', 'Đã cập nhật trạng thái danh mục.');
    }

    public function bulkAction(Request $request)
    {
        $request->validate([
            'selected' => ['required', 'array'],
            'selected.*' => ['integer', 'exists:categories,id'],
            'bulk_action' => ['required', 'in:hide,show'],
        ]);

        $ids = $request->input('selected', []);
        $action = $request->input('bulk_action');

        if ($action === 'hide') {
            Category::whereIn('id', $ids)->update(['is_active' => false]);
            return back()->with('success', 'Đã ẩn ' . count($ids) . ' danh mục.');
        }

        if ($action === 'show') {
            Category::whereIn('id', $ids)->update(['is_active' => true]);
            return back()->with('success', 'Đã hiển thị ' . count($ids) . ' danh mục.');
        }

        return back()->with('error', 'Hành động không hợp lệ.');
    }

    private function uploadImage($file): string
    {
        $filename = Str::random(20) . '.' . $file->getClientOriginalExtension();
        $destination = public_path('clients/assets/img/categories');

        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $file->move($destination, $filename);
        @chmod($destination . DIRECTORY_SEPARATOR . $filename, 0644);

        return $filename;
    }

    private function deleteImageFile(?string $filename): void
    {
        if (!$filename) {
            return;
        }

        $path = public_path('clients/assets/img/categories/' . $filename);
        if (file_exists($path)) {
            @unlink($path);
        }
    }
}


