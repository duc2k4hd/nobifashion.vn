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
        if (!file_exists(public_path('clients/assets/img/categories'))) {
            mkdir(public_path('clients/assets/img/categories'), 0777, true);
        }
        $query = Category::query()->with('parent', 'children');

        if ($keyword = $request->get('keyword')) {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', '%' . $keyword . '%')
                    ->orWhere('slug', 'like', '%' . $keyword . '%')
                    ->orWhere('description', 'like', '%' . $keyword . '%');
            });
        }

        if ($status = $request->get('status')) {
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $sort = $request->get('sort', 'sort_order');
        $direction = $request->get('direction', 'asc');

        if (in_array($sort, ['name', 'sort_order', 'created_at', 'updated_at'])) {
            $query->orderBy($sort, $direction === 'desc' ? 'desc' : 'asc');
        }

        // Sắp xếp theo parent_id trước để nhóm cha-con
        $query->orderBy('parent_id', 'asc');

        $perPageOptions = [20, 50, 100];
        $perPage = (int) $request->get('per_page', 20);
        $perPage = in_array($perPage, $perPageOptions) ? $perPage : 20;

        $categories = $query
            ->paginate($perPage)
            ->appends($request->query());

        // Thêm stats từng danh mục
        $categories->each(function ($cat) {
            $cat->product_count = $cat->primaryProducts()->count();
            $cat->child_count = $cat->children->count();
        });

        return view('admins.categories.index', compact('categories', 'perPageOptions', 'perPage'));
    }

    public function create()
    {
        $category = new Category();
        $parents = $this->getParentCategories();

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
        $parents = $this->getParentCategories($category->id);

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

    /**
     * Delete category (soft delete if has products, hard delete if empty)
     */
    public function destroy(Category $category)
    {
        $productCount = $category->primaryProducts()->count();
        $childCount = Category::where('parent_id', $category->id)->count();

        if ($productCount > 0 || $childCount > 0) {
            return back()->with('error', 
                'Không thể xóa danh mục này vì còn ' . $productCount . ' sản phẩm' . 
                ($childCount > 0 ? ' và ' . $childCount . ' danh mục con' : '') . '. ' .
                'Vui lòng xóa hoặc di chuyển chúng trước.'
            );
        }

        // Xóa ảnh
        $this->deleteImageFile($category->image);

        // Xóa danh mục
        $categoryName = $category->name;
        $category->delete();

        return back()->with('success', "Đã xóa danh mục '$categoryName'.");
    }

    /**
     * API: Update sort order (drag & drop)
     */
    public function updateSort(Request $request)
    {
        $request->validate([
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'integer', 'exists:categories,id'],
            'items.*.sort_order' => ['required', 'integer', 'min:0'],
        ]);

        foreach ($request->input('items', []) as $item) {
            Category::where('id', $item['id'])
                ->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã cập nhật thứ tự danh mục.',
        ]);
    }

    /**
     * API: Inline update status toggle
     */
    public function quickToggleStatus(Request $request, Category $category)
    {
        $category->update(['is_active' => !$category->is_active]);

        return response()->json([
            'success' => true,
            'is_active' => $category->is_active,
            'message' => 'Đã cập nhật trạng thái.',
        ]);
    }

    public function bulkAction(Request $request)
    {
        $request->validate([
            'selected' => ['required', 'array'],
            'selected.*' => ['integer', 'exists:categories,id'],
            'bulk_action' => ['required', 'in:hide,show,delete'],
        ]);

        $ids = $request->input('selected', []);
        $action = $request->input('bulk_action');
        $categories = Category::whereIn('id', $ids)->get();

        if ($action === 'hide') {
            Category::whereIn('id', $ids)->update(['is_active' => false]);
            return back()->with('success', 'Đã ẩn ' . count($ids) . ' danh mục.');
        }

        if ($action === 'show') {
            Category::whereIn('id', $ids)->update(['is_active' => true]);
            return back()->with('success', 'Đã hiển thị ' . count($ids) . ' danh mục.');
        }

        if ($action === 'delete') {
            $deletedCount = 0;
            $skippedCount = 0;
            $errorMessage = [];

            foreach ($categories as $cat) {
                $productCount = $cat->primaryProducts()->count();
                $childCount = Category::where('parent_id', $cat->id)->count();

                if ($productCount > 0 || $childCount > 0) {
                    $skippedCount++;
                    $errorMessage[] = "'{$cat->name}' (có {$productCount} sản phẩm, {$childCount} danh mục con)";
                } else {
                    $this->deleteImageFile($cat->image);
                    $cat->delete();
                    $deletedCount++;
                }
            }

            $message = "Đã xóa $deletedCount danh mục.";
            if ($skippedCount > 0) {
                $message .= " Bỏ qua $skippedCount danh mục (không thể xóa): " . implode(', ', $errorMessage);
            }

            $type = $skippedCount > 0 ? 'warning' : 'success';
            return back()->with($type, $message);
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

    private function getParentCategories(?int $excludeId = null)
    {
        $query = Category::query()->with('parent');

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->get()
            ->sortBy(
                fn (Category $parent) => mb_strtolower($parent->fullPath(), 'UTF-8'),
                SORT_NATURAL
            )
            ->values();
    }

    /**
     * API: Get all parent categories (for quick edit modal)
     */
    public function getParents()
    {
        $parents = Category::select('id', 'name', 'parent_id')
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'fullPath' => $category->fullPath(),
                ];
            });

        return response()->json($parents);
    }

    /**
     * API: Quick inline update for all basic fields (name, slug, description, parent, sort_order, status, image, seo)
     */
    public function quickUpdate(Request $request, Category $category)
    {
        try {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'slug' => ['required', 'string', 'max:255', 'unique:categories,slug,' . $category->id],
                'sort_order' => ['nullable', 'integer', 'min:0'],
                'is_active' => ['nullable', 'in:0,1,true,false'],
                'meta_title' => ['nullable', 'string', 'max:255'],
                'meta_description' => ['nullable', 'string', 'max:500'],
                'meta_keywords' => ['nullable', 'string', 'max:500'],
                'image' => ['nullable', 'file', 'max:10240'],
            ]);

            $data = [
                'name' => $validated['name'],
                'slug' => $validated['slug'],
                'sort_order' => $validated['sort_order'] ?? 0,
                'is_active' => in_array($validated['is_active'] ?? '0', ['1', 'true', true]),
                'meta_title' => $validated['meta_title'] ?? null,
                'meta_description' => $validated['meta_description'] ?? null,
                'meta_keywords' => $validated['meta_keywords'] ?? null,
            ];

            // Handle image upload
            if ($request->hasFile('image')) {
                $this->deleteImageFile($category->image);
                $data['image'] = $this->uploadImage($request->file('image'));
            }

            $category->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Đã cập nhật danh mục.',
                'category' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'sort_order' => $category->sort_order,
                    'is_active' => $category->is_active,
                    'meta_title' => $category->meta_title,
                    'meta_description' => $category->meta_description,
                    'meta_keywords' => $category->meta_keywords,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi validation',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Quick update error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'category_id' => $category->id,
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage(),
            ], 500);
        }
    }
}

