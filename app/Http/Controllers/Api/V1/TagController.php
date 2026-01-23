<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use App\Services\TagService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TagController extends Controller
{
    public function __construct(
        protected TagService $tagService
    ) {
    }

    /**
     * Danh sách tags
     */
    public function index(Request $request): JsonResponse
    {
        $query = Tag::query()->filter($request->all());

        if ($request->has('entity_type')) {
            $query->where('entity_type', $request->entity_type);
        }

        if ($request->has('active_only')) {
            $query->active();
        }

        $tags = $query->orderBy('name')->paginate($request->get('per_page', 20));

        return TagResource::collection($tags)->response();
    }

    /**
     * Chi tiết tag
     */
    public function show(Tag $tag): JsonResponse
    {
        return (new TagResource($tag))->response();
    }

    /**
     * Tạo tag mới
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:tags,slug',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
            'entity_type' => 'required|string',
            'entity_id' => 'required|integer',
        ]);

        try {
            $tag = $this->tagService->create($request->all());

            return (new TagResource($tag))
                ->response()
                ->setStatusCode(201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cập nhật tag
     */
    public function update(Request $request, Tag $tag): JsonResponse
    {
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:tags,slug,' . $tag->id,
            'description' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
            'entity_type' => 'sometimes|required|string',
            'entity_id' => 'sometimes|required|integer',
        ]);

        try {
            $tag = $this->tagService->update($tag, $request->all());

            return (new TagResource($tag))->response();
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Xóa tag
     */
    public function destroy(Tag $tag): JsonResponse
    {
        try {
            $this->tagService->delete($tag);

            return response()->json([
                'success' => true,
                'message' => 'Đã xóa tag thành công',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lấy tags theo entity
     */
    public function getByEntity(string $entityType, int $entityId): JsonResponse
    {
        $tags = $this->tagService->getByEntity($entityType, $entityId);

        return response()->json([
            'success' => true,
            'data' => $tags,
        ]);
    }

    /**
     * Gợi ý tags
     */
    public function suggest(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|max:255',
            'entity_type' => 'nullable|string',
        ]);

        $suggestions = $this->tagService->suggest(
            $request->query,
            $request->entity_type,
            10
        );

        return response()->json([
            'success' => true,
            'data' => $suggestions,
        ]);
    }

    /**
     * Gắn tag cho entity
     */
    public function assign(Request $request): JsonResponse
    {
        $request->validate([
            'entity_type' => 'required|string',
            'entity_id' => 'required|integer',
            'tag_ids' => 'required|array',
            'tag_ids.*' => 'integer|exists:tags,id',
        ]);

        try {
            $tags = $this->tagService->assignToEntity(
                $request->entity_type,
                $request->entity_id,
                $request->tag_ids
            );

            return response()->json([
                'success' => true,
                'message' => 'Đã gắn tags thành công',
                'data' => TagResource::collection(collect($tags)),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bỏ tag khỏi entity
     */
    public function remove(Request $request): JsonResponse
    {
        $request->validate([
            'entity_type' => 'required|string',
            'entity_id' => 'required|integer',
            'tag_ids' => 'required|array',
            'tag_ids.*' => 'integer|exists:tags,id',
        ]);

        try {
            $removed = $this->tagService->removeFromEntity(
                $request->entity_type,
                $request->entity_id,
                $request->tag_ids
            );

            return response()->json([
                'success' => $removed,
                'message' => $removed ? 'Đã bỏ tags thành công' : 'Không có tag nào được bỏ',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
