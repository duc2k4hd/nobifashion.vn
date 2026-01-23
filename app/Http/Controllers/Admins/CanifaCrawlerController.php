<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Services\CanifaCrawlerService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class CanifaCrawlerController extends Controller
{
    public function __construct(
        protected CanifaCrawlerService $crawlerService
    ) {
        $this->middleware(['auth:web', 'admin']);
    }

    /**
     * Hiển thị form nhập list danh mục
     */
    public function index(): View
    {
        return view('admins.canifa-crawler.index');
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

    /**
     * Tool tạo danh sách URL từ URL có số trang
     */
    public function generateUrls(Request $request): JsonResponse
    {
        $request->validate([
            'url' => 'required|string',
        ]);

        $inputUrl = trim($request->input('url'));
        
        try {
            // Decode URL để xử lý các ký tự đặc biệt
            $decodedUrl = urldecode($inputUrl);
            
            // Parse URL để lấy base URL và số trang
            $parsedUrl = parse_url($decodedUrl);
            if (!$parsedUrl || !isset($parsedUrl['scheme']) || !isset($parsedUrl['host'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'URL không hợp lệ.',
                ], 422);
            }
            
            $path = $parsedUrl['path'] ?? '';
            
            // Tìm số trang trong URL
            // Trường hợp 1: /page/37
            // Trường hợp 2: /page/ * 37 (có khoảng trắng và dấu *)
            // Trường hợp 3: /page/%20*%2037 (URL encoded)
            $maxPage = null;
            
            if (preg_match('/\/page\/(\d+)/', $path, $matches)) {
                $maxPage = (int) $matches[1];
            } elseif (preg_match('/\/page\/\s*\*\s*(\d+)/', $decodedUrl, $matches)) {
                $maxPage = (int) $matches[1];
            } elseif (preg_match('/\/page\/%20\*%20(\d+)/i', $inputUrl, $matches)) {
                $maxPage = (int) $matches[1];
            } elseif (preg_match('/\/page\/.*?(\d+)/', $path, $matches)) {
                // Fallback: lấy số cuối cùng sau /page/
                $maxPage = (int) $matches[1];
            }
            
            if (!$maxPage || $maxPage < 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'URL không chứa số trang hợp lệ hoặc số trang phải >= 2.',
                ], 422);
            }

            // Tạo base URL (loại bỏ phần /page/...)
            $basePath = preg_replace('/\/page\/.*$/', '', $path);
            $baseUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . $basePath;
            
            // Tạo danh sách URL
            $urls = [$baseUrl]; // URL gốc (không có /page/)
            
            // Thêm các URL từ page/2 đến page/maxPage
            for ($i = 2; $i <= $maxPage; $i++) {
                $urls[] = $baseUrl . '/page/' . $i;
            }

            return response()->json([
                'success' => true,
                'urls' => $urls,
                'count' => count($urls),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xử lý URL: ' . $e->getMessage(),
            ], 500);
        }
    }
}
