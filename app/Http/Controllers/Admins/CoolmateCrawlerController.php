<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Services\CoolmateCrawlerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CoolmateCrawlerController extends Controller
{
    public function __construct(
        protected CoolmateCrawlerService $crawlerService
    ) {
        $this->middleware(['auth:web', 'admin']);
    }

    /**
     * Form nhập list URL danh mục (JSON endpoint hoặc HTML)
     */
    public function index(): View
    {
        return view('admins.coolmate-crawler.index');
    }

    /**
     * Gọi service crawl
     */
    public function crawl(Request $request): JsonResponse
    {
        $request->validate([
            'category_urls' => 'required|string',
        ]);

        $categoryUrls = array_filter(
            array_map('trim', explode("\n", $request->input('category_urls')))
        );

        if (empty($categoryUrls)) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng nhập ít nhất một URL danh mục (JSON endpoint hoặc HTML).',
            ], 422);
        }

        try {
            $results = $this->crawlerService->crawlCategories($categoryUrls);

            return response()->json([
                'success' => true,
                'message' => "Đã crawl thành công {$results['success']} bài viết, thất bại {$results['failed']} bài.",
                'data' => $results,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi crawl Coolmate: ' . $e->getMessage(),
            ], 500);
        }
    }
}

