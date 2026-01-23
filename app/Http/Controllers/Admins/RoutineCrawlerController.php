<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Services\RoutineCrawlerService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class RoutineCrawlerController extends Controller
{
    public function __construct(
        protected RoutineCrawlerService $crawlerService
    ) {
        $this->middleware(['auth:web', 'admin']);
    }

    /**
     * Hiển thị form nhập list danh mục
     */
    public function index(): View
    {
        return view('admins.routine-crawler.index');
    }

    /**
     * Xử lý crawl dữ liệu
     */
    public function crawl(Request $request): JsonResponse
    {
        $request->validate([
            'category_urls' => 'required|string',
        ]);

        // Parse danh sách URL từ textarea (mỗi dòng một URL)
        $categoryUrls = array_filter(
            array_map('trim', explode("\n", $request->input('category_urls')))
        );

        if (empty($categoryUrls)) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng nhập ít nhất một URL danh mục.',
            ], 422);
        }

        try {
            $results = $this->crawlerService->crawlCategories($categoryUrls);

            return response()->json([
                'success' => true,
                'message' => "Đã crawl thành công {$results['success']} bài viết, thất bại {$results['failed']} bài.",
                'data' => $results,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi crawl: ' . $e->getMessage(),
            ], 500);
        }
    }
}
