<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Services\CoolmateCrawlerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CoolmateCrawlerController extends Controller
{
    public function __construct(
        protected CoolmateCrawlerService $crawlerService
    ) {
        $this->middleware(['auth:web', 'admin']);
    }

    /**
     * Form nhập danh sách URL bài viết Coolmate.
     */
    public function index(): View
    {
        return view('admins.coolmate-crawler.index');
    }

    /**
     * Crawl bài viết, tải ảnh vào temp và xuất CSV.
     */
    public function crawl(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'post_urls' => 'required|string',
            'recrawl_existing' => 'sometimes|boolean',
            'download_main_image' => 'sometimes|boolean',
        ]);

        $postUrls = array_values(array_filter(
            array_map(
                'trim',
                preg_split('/\r\n|\r|\n/', $payload['post_urls']) ?: []
            ),
            static fn (string $url): bool => $url !== ''
        ));

        if (empty($postUrls)) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Vui lòng nhập ít nhất một URL bài viết Coolmate.',
            ], 422);
        }

        try {
            $results = $this->crawlerService->crawlPostsToCsv(
                $postUrls,
                (bool) ($payload['recrawl_existing'] ?? false),
                (bool) ($payload['download_main_image'] ?? false)
            );

            if (
                $results['success'] === 0
                && $results['failed'] === 0
                && $results['skipped'] > 0
            ) {
                return $this->jsonResponse([
                    'success' => true,
                    'message' => "Không có bài mới; đã bỏ qua {$results['skipped']} URL từng crawl hoặc bị trùng.",
                    'data' => $results,
                ]);
            }

            if ($results['success'] === 0 || empty($results['file_name'])) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => "Không crawl được bài viết nào. Thất bại {$results['failed']} URL.",
                    'data' => $results,
                ], 422);
            }

            $results['download_url'] = route(
                'admin.coolmate-crawler.download',
                ['filename' => $results['file_name']]
            );
            $message = "Đã crawl {$results['success']} bài, tải {$results['image_downloaded_count']} ảnh"
                ." và tạo file CSV; thất bại {$results['failed']} URL";

            if ($results['skipped'] > 0) {
                $message .= ", bỏ qua {$results['skipped']} URL trùng hoặc đã crawl";
            }

            return $this->jsonResponse([
                'success' => true,
                'message' => $message.'.',
                'data' => $results,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return $this->jsonResponse([
                'success' => false,
                'message' => 'Lỗi khi crawl Coolmate: '.$e->getMessage(),
            ], 500);
        }
    }

    public function download(string $filename): BinaryFileResponse
    {
        $path = $this->crawlerService->resolveExportPath($filename);

        abort_unless($path, 404);

        return response()->download($path, basename($path));
    }

    private function jsonResponse(array $data, int $status = 200): JsonResponse
    {
        return response()->json(
            $data,
            $status,
            [],
            JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
        );
    }
}
