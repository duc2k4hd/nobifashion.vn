<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PostAutosaveRequest;
use App\Http\Requests\Admin\PostStoreRequest;
use App\Http\Requests\Admin\PostUpdateRequest;
use App\Models\Account;
use App\Models\Image;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\PostRevision;
use App\Models\Tag;
use App\Services\Admin\ProgressiveSearchService;
use App\Services\PostService;
use App\Services\SeoService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class PostController extends Controller
{
    public function __construct(
        protected PostService $postService,
        protected SeoService $seoService,
        protected ProgressiveSearchService $progressiveSearchService,
    ) {
        $this->middleware(['auth:web', 'admin']);
    }

    public function index(Request $request): View
    {
        $categoryFilter = $request->input('category_id');
        $isFilteringCategory = $request->has('category_id') && $categoryFilter !== null && $categoryFilter !== '';

        // Nếu lọc bài đã xóa mềm thì dùng onlyTrashed(), ngược lại query bình thường
        if ($request->input('status') === 'trashed') {
            $query = Post::onlyTrashed()
                ->with(['author.profile', 'category'])
                ->when($isFilteringCategory, function ($q) use ($categoryFilter) {
                    if ($categoryFilter === 'none' || $categoryFilter === '0' || $categoryFilter === 'uncategorized') {
                        $q->where(function ($sub) {
                            $sub->whereNull('category_id')
                                ->orWhere('category_id', 0)
                                ->orWhereDoesntHave('category');
                        });
                    } else {
                        $q->where('category_id', (int) $categoryFilter);
                    }
                })
                ->when($request->filled('author_id'), fn ($q) => $q->where('created_by', $request->integer('author_id')))
                ->when($request->filled('tag_id'), function ($q) use ($request) {
                    $tagId = $request->integer('tag_id');
                    $q->whereHas('tags', fn ($tagQuery) => $tagQuery->where('tags.id', $tagId));
                })
                ->when($request->filled('without_thumbnail'), fn ($q) => $q->whereNull('thumbnail'))
                ->when($request->filled('date_from'), fn ($q) => $q->whereDate('published_at', '>=', $request->date('date_from')))
                ->when($request->filled('date_to'), fn ($q) => $q->whereDate('published_at', '<=', $request->date('date_to')));
        } else {
            $query = Post::query()
                ->with(['author.profile', 'category'])
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
                ->when($isFilteringCategory, function ($q) use ($categoryFilter) {
                    if ($categoryFilter === 'none' || $categoryFilter === '0' || $categoryFilter === 'uncategorized') {
                        $q->where(function ($sub) {
                            $sub->whereNull('category_id')
                                ->orWhere('category_id', 0)
                                ->orWhereDoesntHave('category');
                        });
                    } else {
                        $q->where('category_id', (int) $categoryFilter);
                    }
                })
                ->when($request->filled('author_id'), fn ($q) => $q->where('created_by', $request->integer('author_id')))
                ->when($request->filled('tag_id'), function ($q) use ($request) {
                    $tagId = $request->integer('tag_id');
                    // Tìm posts có tag với entity_type = Post::class
                    $q->whereHas('tags', function ($tagQuery) use ($tagId) {
                        $tagQuery->where('tags.id', $tagId);
                    });
                })
                ->when($request->filled('is_featured'), fn ($q) => $q->where('is_featured', $request->boolean('is_featured')))
                ->when($request->filled('without_thumbnail'), fn ($q) => $q->whereNull('thumbnail'))
                ->when($request->filled('date_from'), fn ($q) => $q->whereDate('published_at', '>=', $request->date('date_from')))
                ->when($request->filled('date_to'), fn ($q) => $q->whereDate('published_at', '<=', $request->date('date_to')));
        }

        $searchMeta = $this->progressiveSearchService->apply(
            $query,
            $request->input('search'),
            ['posts.title'],
            ['posts.slug']
        );

        // Xử lý sắp xếp theo Lượt xem (View AZ: thấp -> cao, View ZA: cao -> thấp) hoặc thời gian
        $sort = $request->input('sort');
        if (in_array($sort, ['view_asc', 'views_asc', 'view_az', 'views_az'])) {
            $query->orderBy('views', 'asc')->orderByDesc('id');
        } elseif (in_array($sort, ['view_desc', 'views_desc', 'view_za', 'views_za'])) {
            $query->orderByDesc('views')->orderByDesc('id');
        } elseif ($sort === 'oldest') {
            $query->orderBy(DB::raw('COALESCE(published_at, created_at)'), 'asc')->orderBy('id', 'asc');
        } else {
            $query->orderByDesc(DB::raw('COALESCE(published_at, created_at)'))->orderByDesc('id');
        }

        $perPage = $request->input('limit', 50);
        if (!in_array((int)$perPage, [50, 100, 300, 1000])) {
            $perPage = 50;
        }

        $posts = $query->paginate((int)$perPage)->withQueryString();

        return view('admins.posts.index', [
            'posts' => $posts,
            'filters' => $request->all(),
            'searchMeta' => $searchMeta,
            'categories' => PostCategory::ordered()->get(),
            'tags' => Tag::where('entity_type', Post::class)->select('id', 'name')->distinct('name')->orderBy('name')->get()->unique('name')->values(),
            'authors' => Account::orderBy('name')->get(['id', 'name', 'email']),
            'statusOptions' => [
                'draft' => 'Nháp',
                'pending' => 'Chờ duyệt',
                'published' => 'Đã xuất bản',
                'archived' => 'Lưu trữ',
            ],
        ]);
    }

    public function create(): View
    {
        $post = new Post();
        $post->setRelation('revisions', collect());
        $post->setRelation('tags', collect());

        $postMorph = $post->getMorphClass();
        $postOnlyTags = Tag::whereIn('entity_type', [$postMorph, Post::class])
            ->select('id', 'name')
            ->orderBy('name')
            ->get()
            ->unique('name')
            ->values();

        $selectedTagNames = old('tag_ids')
            ? Tag::whereIn('id', (array) old('tag_ids'))->pluck('name')->all()
            : [];

        return view('admins.posts.create', [
            'post' => $post,
            'categories' => PostCategory::ordered()->get(['id', 'name', 'sort_order']),
            'tags' => $postOnlyTags,
            'selectedTagNames' => $selectedTagNames,
            'postTags' => collect(),
            'mediaImages' => [],
        ]);
    }

    public function store(PostStoreRequest $request): RedirectResponse
    {
        $post = $this->postService->create($request->validated(), $request->user('web'));

        return redirect()
            ->route('admin.posts.edit', $post)
            ->with('success', 'Đã tạo bài viết.');
    }

    public function edit(Post $post): View
    {
        $postMorph = $post->getMorphClass();
        $post->load([
            'revisions' => fn ($q) => $q->with('editor:id,name')->latest()->limit(10),
            'author.profile',
            'category:id,name',
            'tags',
        ]);

        $postOnlyTags = Tag::whereIn('entity_type', [$postMorph, Post::class])
            ->select('id', 'name')
            ->orderBy('name')
            ->get()
            ->unique('name')
            ->values();

        $selectedTagNames = old('tag_ids')
            ? Tag::whereIn('id', (array) old('tag_ids'))->pluck('name')->all()
            : $post->tags->pluck('name')->all();

        return view('admins.posts.edit', [
            'post' => $post,
            'categories' => PostCategory::ordered()->get(['id', 'name', 'sort_order']),
            'tags' => $postOnlyTags,
            'selectedTagNames' => $selectedTagNames,
            'postTags' => $post->tags,
            'authors' => Account::orderBy('name')->get(['id', 'name', 'email']),
            'seoInsights' => $this->seoService->evaluateSeoScore($post),
            'mediaImages' => [],
        ]);
    }

    public function update(PostUpdateRequest $request, Post $post): RedirectResponse
    {
        $post = $this->postService->update($post, $request->validated(), $request->user('web'));

        return redirect()
            ->route('admin.posts.edit', $post)
            ->with('success', 'Đã cập nhật bài viết.');
    }

    public function destroy(Post $post): RedirectResponse
    {
        $post->delete();

        return back()->with('success', 'Đã xóa bài viết.');
    }

    public function bulkDestroy(Request $request)
    {
        $ids = $request->input('ids');
        if (empty($ids) || !is_array($ids)) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Chưa chọn bài viết nào.']);
            }
            return back()->with('error', 'Chưa chọn bài viết nào.');
        }

        if ($request->boolean('force_clean')) {
            // Xóa bulk cực nhanh qua DB Query Builder, ko trigger model events để giữ lại ảnh
            \App\Models\Comment::where('commentable_type', Post::class)->whereIn('commentable_id', $ids)->delete();
            \App\Models\Tag::where('entity_type', Post::class)->whereIn('entity_id', $ids)->delete();
            \App\Models\PostRevision::whereIn('post_id', $ids)->delete();
            $count = Post::withTrashed()->whereIn('id', $ids)->forceDelete();

            if ($request->expectsJson()) {
                return response()->json(['success' => true, 'count' => $count]);
            }
            return back()->with('success', "Đã xóa sạch {$count} bài viết thành công.");
        }

        $isTrashed = $request->boolean('is_trashed');

        if ($isTrashed) {
            // Bài đã ở trong thùng rác -> xóa vĩnh viễn (force delete)
            $count = Post::withTrashed()->whereIn('id', $ids)->forceDelete();
        } else {
            // Bài bình thường -> xóa mềm
            $count = Post::whereIn('id', $ids)->delete();
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'count' => $count]);
        }

        return back()->with('success', "Đã xóa {$count} bài viết thành công.");
    }

    /**
     * Xóa bài viết từ danh sách IDs kèm xóa ảnh đại diện và ảnh trong content
     * - Chỉ xóa file vật lý nếu không có bài viết nào khác cùng sử dụng ảnh đó
     * - Cực nhanh: batch query, không load model events
     */
    public function destroyFromTxt(Request $request): JsonResponse
    {
        $ids = $request->input('ids');
        if (empty($ids) || !is_array($ids)) {
            return response()->json(['success' => false, 'message' => 'Không có ID hợp lệ.'], 422);
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (empty($ids)) {
            return response()->json(['success' => false, 'message' => 'Danh sách ID rỗng.'], 422);
        }

        // Chỉ xử lý theo batch nhỏ để tránh timeout
        $batchSize = 200;
        $idsChunk = array_slice($ids, 0, $batchSize);

        try {
            // 1. Lấy toàn bộ thumbnail paths và content của các bài viết sẽ bị xóa
            $posts = DB::table('posts')
                ->whereIn('id', $idsChunk)
                ->select(['id', 'thumbnail', 'content'])
                ->get();

            // 2. Thu thập tất cả ảnh thumbnail từ posts sẽ xóa
            $thumbnailPaths = $posts
                ->pluck('thumbnail')
                ->filter()
                ->map(fn ($t) => 'clients/assets/img/posts/' . ltrim($t, '/'))
                ->unique()
                ->values()
                ->toArray();

            // 3. Thu thập tất cả ảnh trong content (img src)
            $contentImagePaths = [];
            foreach ($posts as $post) {
                if (empty($post->content)) continue;
                // Tìm src trong thẻ img
                preg_match_all('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $post->content, $matches);
                foreach ($matches[1] ?? [] as $src) {
                    $src = trim($src);
                    if (empty($src) || str_starts_with($src, 'data:')) continue;
                    // Chuẩn hoá về relative path
                    if (preg_match('~(?:clients/assets/img/|uploads/)(.+)~i', $src, $m)) {
                        $prefix = str_contains($src, 'clients/assets/img/') ? 'clients/assets/img/' : 'uploads/';
                        $relative = rtrim(explode('?', $prefix . $m[1])[0], '/');
                        $contentImagePaths[] = $relative;
                    } elseif (!str_starts_with($src, 'http')) {
                        $contentImagePaths[] = ltrim(explode('?', $src)[0], '/');
                    }
                }
            }

            // 4. Hợp nhất danh sách ảnh cần kiểm tra
            $allImagePaths = array_unique(array_merge($thumbnailPaths, $contentImagePaths));

            // 5. Kiểm tra ảnh nào đang được bài viết KHÁC sử dụng
            //    (thumbnail của các post KHÔNG trong danh sách xóa)
            $sharedThumbnails = [];
            if (!empty($allImagePaths)) {
                // Lấy basenames để so sánh
                $basenames = array_unique(array_map('basename', $allImagePaths));

                // Kiểm tra thumbnail trong posts khác
                $otherUsedThumbnails = DB::table('posts')
                    ->whereNotIn('id', $idsChunk)
                    ->whereNotNull('thumbnail')
                    ->whereIn('thumbnail', array_map('basename', $thumbnailPaths))
                    ->pluck('thumbnail')
                    ->map(fn ($t) => basename($t))
                    ->flip()
                    ->toArray();

                // Kiểm tra trong bảng images (content images có thể đã được gán)
                $otherUsedInImages = DB::table('images')
                    ->where('entity_type', 'post')
                    ->whereNotIn('entity_id', $idsChunk)
                    ->whereIn('url', $basenames)
                    ->pluck('url')
                    ->map(fn ($u) => basename($u))
                    ->flip()
                    ->toArray();

                $sharedThumbnails = array_merge($otherUsedThumbnails, $otherUsedInImages);
            }

            // 6. Xóa file vật lý (chỉ khi không được bài khác dùng)
            $deletedFiles = 0;
            $skippedFiles = 0;
            foreach ($allImagePaths as $relPath) {
                $bn = basename($relPath);
                if (isset($sharedThumbnails[$bn])) {
                    $skippedFiles++;
                    continue;
                }
                $absPath = public_path($relPath);
                if (is_file($absPath)) {
                    @unlink($absPath);
                    $deletedFiles++;
                }
            }

            // 7. Xóa các image records trong bảng images liên kết với posts sẽ xóa
            DB::table('images')
                ->where('entity_type', 'post')
                ->whereIn('entity_id', $idsChunk)
                ->delete();

            // 8. Xóa dữ liệu liên quan và bài viết cực nhanh (không dùng model events)
            DB::table('comments')
                ->where('commentable_type', Post::class)
                ->whereIn('commentable_id', $idsChunk)
                ->delete();

            // Tags bài viết được lưu trong bảng tags với entity_type/entity_id
            DB::table('tags')
                ->where('entity_type', Post::class)
                ->whereIn('entity_id', $idsChunk)
                ->delete();

            DB::table('post_revisions')
                ->whereIn('post_id', $idsChunk)
                ->delete();

            $deletedPosts = DB::table('posts')
                ->whereIn('id', $idsChunk)
                ->delete();

            return response()->json([
                'success'        => true,
                'count'          => $deletedPosts,
                'deleted_files'  => $deletedFiles,
                'skipped_files'  => $skippedFiles,
            ]);

        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Lỗi xử lý: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function restore(int $postId): RedirectResponse
    {
        $post = Post::withTrashed()->findOrFail($postId);
        $post->restore();

        return back()->with('success', 'Đã khôi phục bài viết.');
    }

    public function publish(Post $post, Request $request): RedirectResponse
    {
        $schedule = $request->input('published_at');
        $scheduleAt = null;

        if ($schedule) {
            try {
                $scheduleAt = Carbon::parse($schedule);
            } catch (\Throwable $e) {
                $scheduleAt = null;
            }
        }

        $this->postService->update($post, [
            'status' => 'published',
            'published_at' => $scheduleAt?->toDateTimeString(),
        ], $request->user('web'));

        return back()->with('success', 'Đã cập nhật trạng thái bài viết.');
    }

    public function archive(Post $post, Request $request): RedirectResponse
    {
        $this->postService->update($post, ['status' => 'archived'], $request->user('web'));

        return back()->with('success', 'Đã lưu trữ bài viết.');
    }

    public function duplicate(Post $post, Request $request): RedirectResponse
    {
        $clone = $this->postService->duplicate($post, $request->user('web'));

        return redirect()->route('admin.posts.edit', $clone)
            ->with('success', 'Đã nhân bản bài viết.');
    }

    public function feature(Post $post): RedirectResponse
    {
        $post->update(['is_featured' => true]);

        return back()->with('success', 'Đã bật nổi bật.');
    }

    public function unfeature(Post $post): RedirectResponse
    {
        $post->update(['is_featured' => false]);

        return back()->with('success', 'Đã tắt nổi bật.');
    }

    public function revisions(Post $post): JsonResponse
    {
        return response()->json([
            'data' => $post->revisions()->latest()->limit(20)->get(),
        ]);
    }

    public function autosave(PostAutosaveRequest $request, Post $post): JsonResponse
    {
        $revision = $this->postService->autosave($post, $request->validated(), $request->user('web'));

        return response()->json([
            'success' => true,
            'revision_id' => $revision->id,
            'saved_at' => $revision->created_at,
        ]);
    }

    public function restoreRevision(Post $post, int $revisionId, Request $request): RedirectResponse
    {
        $revision = PostRevision::where('post_id', $post->id)->findOrFail($revisionId);

        $this->postService->restoreRevision($post, $revision, $request->user('web'));

        return redirect()
            ->route('admin.posts.edit', $post)
            ->with('success', 'Đã khôi phục phiên bản bản thảo.');
    }

    private function getMediaImages(): array
    {
        return [];
    }
}


