<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SeoAnalyzeRequest;
use App\Models\Post;
use App\Services\SeoService;
use Illuminate\Http\JsonResponse;

class SeoController extends Controller
{
    public function __construct(protected SeoService $seoService)
    {
        $this->middleware(['auth:web', 'admin']);
    }

    public function analyze(SeoAnalyzeRequest $request): JsonResponse
    {
        $post = new Post($request->only([
            'title',
            'content',
            'excerpt',
            'meta_title',
            'meta_description',
            'meta_keywords',
            'thumbnail',
            'thumbnail_alt_text',
            'tag_ids',
        ]));

        $seoScore = $this->seoService->evaluateSeoScore($post);
        $analysis = $this->seoService->analyzeContent($request->validated());

        return response()->json([
            'success' => true,
            'data' => array_merge($seoScore, $analysis),
        ]);
    }
}


