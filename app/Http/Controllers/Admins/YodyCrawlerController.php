<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Services\YodyCrawlerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class YodyCrawlerController extends Controller
{
    public function __construct(
        protected YodyCrawlerService $crawlerService
    ) {
        $this->middleware(['auth:web', 'admin']);
    }

    public function index(Request $request): View
    {
        return view('admins.yody-crawler.index', [
            'tempLibrary' => $this->crawlerService->getTempLibrarySummary([
                'q' => $request->query('q'),
                'page' => $request->query('page'),
                'per_page' => $request->query('per_page'),
            ]),
        ]);
    }

    public function crawl(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'product_urls' => 'required|string',
            'primary_category_slug' => 'nullable|string|max:255',
            'category_slugs' => 'nullable|string|max:1000',
            'tag_names' => 'nullable|string|max:1000',
            'is_featured' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'use_yody_category_as_tag' => 'nullable|boolean',
        ]);

        $productUrls = array_filter(
            array_map('trim', preg_split('/\r\n|\r|\n/', $payload['product_urls'] ?? '') ?: [])
        );

        if (empty($productUrls)) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng nhập ít nhất một URL sản phẩm Yody.',
            ], 422);
        }

        try {
            $results = $this->crawlerService->crawlProductsToImportFile($productUrls, $payload);

            if ($results['success'] === 0 && ($results['skipped'] ?? 0) > 0 && $results['failed'] === 0) {
                return response()->json([
                    'success' => true,
                    'message' => "Không có sản phẩm mới. Đã bỏ qua {$results['skipped']} URL vì SKU đã tồn tại trong thư mục tạm Yody.",
                    'data' => $results,
                ]);
            }

            if ($results['success'] === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không crawl được sản phẩm nào từ Yody.',
                    'data' => $results,
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => "Đã crawl {$results['success']} sản phẩm Yody và lưu dữ liệu vào thư mục tạm.",
                'data' => $results,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi crawl Yody: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function download(string $filename): BinaryFileResponse
    {
        $safeFileName = basename($filename);
        $path = storage_path('app/tmp/yody/' . $safeFileName);

        abort_unless(is_file($path), 404);

        return response()->download($path, $safeFileName);
    }

    public function previewImage(string $path): BinaryFileResponse
    {
        $resolvedPath = $this->crawlerService->resolveTempRelativePath($path);

        abort_unless($resolvedPath, 404);

        return response()->file($resolvedPath);
    }

    public function deleteProduct(string $sku): RedirectResponse
    {
        $result = $this->crawlerService->deleteTempProduct($sku);

        return redirect()
            ->route('admin.yody-crawler.index')
            ->with('status', "Đã xóa dữ liệu tạm của SKU {$result['sku']} ({$result['deleted_image_count']} ảnh).")
            ->with('status_type', 'success');
    }

    public function clearTemp(): RedirectResponse
    {
        $result = $this->crawlerService->clearTempLibrary();

        return redirect()
            ->route('admin.yody-crawler.index')
            ->with('status', "Đã dọn thư mục tạm Yody: {$result['deleted_products']} SKU, {$result['deleted_images']} ảnh, {$result['deleted_excel_files']} file Excel.")
            ->with('status_type', 'success');
    }
}
