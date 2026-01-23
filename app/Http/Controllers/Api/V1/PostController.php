<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PostStoreRequest;
use App\Http\Requests\Admin\PostUpdateRequest;
use App\Http\Resources\PostListResource;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Services\PostService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class PostController extends Controller
{
    public function __construct(protected PostService $postService)
    {
    }

    public function index(Request $request)
    {
        $query = Post::published()->with(['author', 'category']);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }

        if ($request->filled('tag_id')) {
            $query->whereJsonContains('tag_ids', (int) $request->input('tag_id'));
        }

        if ($request->filled('search')) {
            $keyword = $request->input('search');
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                    ->orWhere('content', 'like', "%{$keyword}%");
            });
        }

        $perPage = min(50, max(5, (int) $request->input('per_page', 10)));
        $posts = $query->paginate($perPage)->withQueryString();

        return PostListResource::collection($posts);
    }

    public function show(Request $request, string $slug)
    {
        $post = Post::with(['author', 'category'])->where('slug', $slug)->firstOrFail();
        abort_if(!$post->isPublished(), 404);

        $this->postService->incrementViews($post, $request);

        return new PostResource($post);
    }

    public function store(PostStoreRequest $request)
    {
        $account = $this->guardedUser();
        $post = $this->postService->create($request->validated(), $account);

        return (new PostResource($post))->response()->setStatusCode(201);
    }

    public function update(PostUpdateRequest $request, Post $post)
    {
        $account = $this->guardedUser();
        $post = $this->postService->update($post, $request->validated(), $account);

        return new PostResource($post);
    }

    public function destroy(Post $post)
    {
        $post->delete();

        return response()->json([
            'success' => true,
            'message' => 'Post deleted.',
        ]);
    }

    protected function guardedUser()
    {
        $user = Auth::guard('sanctum')->user() ?? Auth::user();

        if (!$user) {
            throw ValidationException::withMessages([
                'auth' => 'Authenticated account required.',
            ]);
        }

        return $user;
    }
}


