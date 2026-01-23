<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PostAutosaveRequest;
use App\Http\Requests\Admin\PostStoreRequest;
use App\Http\Requests\Admin\PostUpdateRequest;
use App\Models\Account;
use App\Models\Category;
use App\Models\Post;
use App\Models\PostRevision;
use App\Models\Tag;
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
    ) {
        $this->middleware(['auth:web', 'admin']);
    }

    public function index(Request $request): View
    {
        $query = Post::query()
            ->with(['author', 'category'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->integer('category_id')))
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
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('published_at', '<=', $request->date('date_to')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $keyword = $request->input('search');
                $q->where(function ($sub) use ($keyword) {
                    $sub->where('title', 'like', "%{$keyword}%")
                        ->orWhere('slug', 'like', "%{$keyword}%");
                });
            })
            ->orderByDesc(DB::raw('COALESCE(published_at, created_at)'));

        $posts = $query->paginate(20)->withQueryString();

        return view('admins.posts.index', [
            'posts' => $posts,
            'filters' => $request->all(),
            'categories' => Category::orderBy('name')->get(),
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

        // Chỉ lấy tags của posts (entity_type = Post::class), không lấy tags của products
        $postOnlyTags = Tag::where('entity_type', Post::class)
            ->select('id', 'name')
            ->distinct('name')
            ->orderBy('name')
            ->get()
            ->unique('name')
            ->values();

        return view('admins.posts.create', [
            'post' => $post,
            'categories' => Category::orderBy('name')->get(),
            'tags' => $postOnlyTags, // Chỉ tags của posts
            'postTags' => collect(), // Chưa có tags khi tạo mới
            'mediaImages' => $this->getMediaImages(),
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
        $post->load([
            'revisions' => fn ($q) => $q->latest()->limit(10),
            'author',
            'category',
        ]);

        // Load tags từ relationship (entity_type = Post::class)
        $postTags = $post->tags()->get();
        
        // Chỉ lấy tags của posts (entity_type = Post::class), không lấy tags của products
        $postOnlyTags = Tag::where('entity_type', Post::class)
            ->select('id', 'name')
            ->distinct('name')
            ->orderBy('name')
            ->get()
            ->unique('name')
            ->values();
        
        return view('admins.posts.edit', [
            'post' => $post,
            'categories' => Category::orderBy('name')->get(),
            'tags' => $postOnlyTags, // Chỉ tags của posts
            'postTags' => $postTags, // Tags đã gắn với post này
            'authors' => Account::orderBy('name')->get(['id', 'name', 'email']),
            'seoInsights' => $this->seoService->evaluateSeoScore($post),
            'mediaImages' => $this->getMediaImages(),
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

        return redirect()->route('admin.posts.index')
            ->with('success', 'Đã xóa bài viết.');
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
        $directories = [
            public_path('clients/assets/img'),
        ];

        $files = [];
        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                continue;
            }

            foreach (File::allFiles($dir) as $file) {
                $relative = str_replace(public_path(), '', $file->getRealPath());
                $relative = str_replace('\\', '/', $relative);
                $relative = ltrim($relative, '/');

                $files[] = [
                    'name' => $file->getFilename(),
                    'url' => asset($relative),
                    'path' => $relative,
                ];
            }
        }

        return $files;
    }
}


