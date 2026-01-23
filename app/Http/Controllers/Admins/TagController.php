<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TagStoreRequest;
use App\Http\Requests\Admin\TagUpdateRequest;
use App\Models\Tag;
use App\Models\Product;
use App\Models\Post;
use App\Services\TagService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TagController extends Controller
{
    public function __construct(
        protected TagService $tagService
    ) {
    }

    /**
     * Danh sách tags
     */
    public function index(Request $request): View
    {
        $query = Tag::with('entity')
            ->filter($request->all())
            ->orderByDesc('created_at');

        $tags = $query->paginate(20)->withQueryString();

        // Lấy danh sách entity types
        $entityTypes = [
            Product::class => 'Sản phẩm',
            Post::class => 'Bài viết',
        ];

        return view('admins.tags.index', [
            'tags' => $tags,
            'filters' => $request->all(),
            'entityTypes' => $entityTypes,
        ]);
    }

    /**
     * Form tạo tag
     */
    public function create(): View
    {
        $entityTypes = [
            'product' => 'Sản phẩm',
            'post' => 'Bài viết',
        ];

        return view('admins.tags.create', [
            'tag' => new Tag(),
            'entityTypes' => $entityTypes,
        ]);
    }

    /**
     * Lưu tag mới
     */
    public function store(TagStoreRequest $request): RedirectResponse
    {
        try {
            $data = $request->validated();
            
            // Normalize entity_type
            if ($data['entity_type'] === 'product') {
                $data['entity_type'] = Product::class;
            } elseif ($data['entity_type'] === 'post') {
                $data['entity_type'] = Post::class;
            }

            $tag = $this->tagService->create($data);

            return redirect()
                ->route('admin.tags.index')
                ->with('success', 'Đã tạo tag thành công');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Không thể tạo tag: ' . $e->getMessage());
        }
    }

    /**
     * Form chỉnh sửa tag
     */
    public function edit(Tag $tag): View
    {
        $entityTypes = [
            'product' => 'Sản phẩm',
            'post' => 'Bài viết',
        ];

        // Normalize entity_type để hiển thị
        $tag->entity_type_display = $tag->entity_type;
        if ($tag->entity_type === Product::class) {
            $tag->entity_type_display = 'product';
        } elseif ($tag->entity_type === Post::class) {
            $tag->entity_type_display = 'post';
        }

        return view('admins.tags.edit', [
            'tag' => $tag,
            'entityTypes' => $entityTypes,
        ]);
    }

    /**
     * Cập nhật tag
     */
    public function update(TagUpdateRequest $request, Tag $tag): RedirectResponse
    {
        try {
            $data = $request->validated();
            
            // Normalize entity_type
            if (isset($data['entity_type'])) {
                if ($data['entity_type'] === 'product') {
                    $data['entity_type'] = Product::class;
                } elseif ($data['entity_type'] === 'post') {
                    $data['entity_type'] = Post::class;
                }
            }

            $this->tagService->update($tag, $data);

            return redirect()
                ->route('admin.tags.index')
                ->with('success', 'Đã cập nhật tag thành công');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Không thể cập nhật tag: ' . $e->getMessage());
        }
    }

    /**
     * Xóa tag
     */
    public function destroy(Tag $tag): RedirectResponse
    {
        try {
            if ($tag->usage_count > 0) {
                return back()
                    ->with('error', 'Không thể xóa tag đang được sử dụng. Vui lòng chuyển sang inactive.');
            }

            $this->tagService->delete($tag);

            return redirect()
                ->route('admin.tags.index')
                ->with('success', 'Đã xóa tag thành công');
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Không thể xóa tag: ' . $e->getMessage());
        }
    }

    /**
     * Xóa hàng loạt
     */
    public function destroyMultiple(Request $request): RedirectResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:tags,id',
        ]);

        try {
            $deleted = $this->tagService->deleteMultiple($request->ids);

            return redirect()
                ->route('admin.tags.index')
                ->with('success', "Đã xóa {$deleted} tag thành công");
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Không thể xóa tags: ' . $e->getMessage());
        }
    }

    /**
     * Gộp tags
     */
    public function merge(Request $request): RedirectResponse
    {
        $request->validate([
            'source_id' => 'required|integer|exists:tags,id',
            'target_id' => 'required|integer|exists:tags,id',
        ]);

        try {
            $sourceTag = Tag::findOrFail($request->source_id);
            $targetTag = Tag::findOrFail($request->target_id);

            $this->tagService->merge($sourceTag, $targetTag);

            return redirect()
                ->route('admin.tags.index')
                ->with('success', 'Đã gộp tags thành công');
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Không thể gộp tags: ' . $e->getMessage());
        }
    }

    /**
     * Gợi ý tags (AJAX)
     */
    public function suggest(Request $request)
    {
        $request->validate([
            'keyword' => 'required|string|max:255',
            'entity_type' => 'nullable|string',
        ]);

        $suggestions = $this->tagService->suggest(
            $request->keyword,
            $request->entity_type,
            10
        );

        return response()->json($suggestions);
    }

    /**
     * Gợi ý tags từ content (AJAX)
     */
    public function suggestFromContent(Request $request)
    {
        $request->validate([
            'content' => 'required|string',
            'entity_type' => 'nullable|string',
        ]);

        $suggestions = $this->tagService->suggestFromContent(
            $request->content,
            $request->entity_type,
            5
        );

        return response()->json($suggestions);
    }

    /**
     * Lấy danh sách entities để autocomplete
     */
    public function getEntities(Request $request)
    {
        $request->validate([
            'entity_type' => 'required|string',
            'keyword' => 'nullable|string|max:255',
        ]);

        $entityType = $request->entity_type;
        if ($entityType === 'product') {
            $entityType = Product::class;
        } elseif ($entityType === 'post') {
            $entityType = Post::class;
        }

        $query = null;
        if ($entityType === Product::class) {
            $query = Product::query();
            if ($request->keyword) {
                $query->where('name', 'like', "%{$request->keyword}%")
                      ->orWhere('sku', 'like', "%{$request->keyword}%");
            }
            $entities = $query->limit(20)->get(['id', 'name', 'sku']);
        } elseif ($entityType === Post::class) {
            $query = Post::query();
            if ($request->keyword) {
                $query->where('title', 'like', "%{$request->keyword}%");
            }
            $entities = $query->limit(20)->get(['id', 'title as name']);
        } else {
            $entities = collect();
        }

        return response()->json($entities);
    }
}

