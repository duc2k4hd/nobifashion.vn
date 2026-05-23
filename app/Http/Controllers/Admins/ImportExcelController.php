<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Jobs\ExportProductsJob;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Image;
use App\Models\Product;
use App\Models\ProductFaq;
use App\Models\ProductHowTo;
use App\Models\ProductVariant;
use App\Models\Tag;
use App\Services\Media\FileHelperService;
use App\Support\ProductWorkbookSchema;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Settings;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Cell;

class ImportExcelController extends Controller
{
    protected array $imageColumnLengths = [];

    /**
     * Hiển thị form upload Excel
     */
    public function index()
    {
        return view('admins.products.import-excel');
    }

    /**
     * Export sản phẩm ra Excel - CHẠY NỀN (Job) để tránh timeout và treo browser.
     * Tối ưu: RAM gần như 0, hỗ trợ 50k-100k dòng, không treo browser.
     * Xuất 1 sheet "products" với các cột cơ bản, đủ dùng cho thao tác hàng loạt.
     * 
     * Flow: Dispatch Job → Frontend poll progress → Download khi xong
     */
    public function export(Request $request)
    {
        $wantsAsyncResponse = $request->expectsJson() || $request->ajax() || $request->wantsJson();

        if (! $wantsAsyncResponse) {
            return $this->exportStreamOld($request);
        }

        if (! class_exists(ExportProductsJob::class)) {
            Log::warning('ExportProductsJob is missing. Falling back to sync export response.', [
                'route' => $request->path(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Chế độ export nền hiện chưa khả dụng vì thiếu job ExportProductsJob.',
            ], 500);
        }

        $request->validate([
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'integer|exists:categories,id',
            'brand_ids' => 'nullable|array',
            'brand_ids.*' => 'integer|exists:brands,id',
        ]);

        try {
            // Đếm tổng số sản phẩm theo filter hiện tại
            $totalProducts = $this->buildFilterQuery($request)->count();
            $maxAllowed = 100000; // Tăng lên 100k

            if ($totalProducts > $maxAllowed) {
                return response()->json([
                    'success' => false,
                    'message' => "Hiện tại chỉ cho phép xuất tối đa {$maxAllowed} sản phẩm. Vui lòng thu hẹp bộ lọc (hiện có {$totalProducts} sản phẩm).",
                ], 400);
            }

            if ($totalProducts === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không có sản phẩm nào phù hợp với bộ lọc.',
                ], 400);
            }

            // Tạo session ID cho export
            $sessionId = 'export_' . time() . '_' . uniqid();

            // Lưu thông tin export vào cache
            $categoryIds = $request->input('category_ids', []);
            $brandIds = $request->input('brand_ids', []);

            Cache::put("export_{$sessionId}", [
                'category_ids' => $categoryIds,
                'brand_ids' => $brandIds,
                'total_products' => $totalProducts,
                'processed' => 0,
                'progress' => 0,
                'status' => 'queued',
                'created_at' => now()->toDateTimeString(),
            ], now()->addHours(2));

            // Dispatch job để export nền
            ExportProductsJob::dispatch($sessionId, $categoryIds, $brandIds, $totalProducts);

            return response()->json([
                'success' => true,
                'session_id' => $sessionId,
                'total_products' => $totalProducts,
                'message' => 'Đã bắt đầu xuất sản phẩm. Vui lòng đợi...',
            ]);

        } catch (\Exception $e) {
            Log::error('Export start error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi bắt đầu xuất: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * DEPRECATED: Hàm export cũ dùng streamDownload (đã thay bằng Job).
     * Giữ lại để backward compatibility nếu cần.
     */
    public function exportStreamOld(Request $request)
    {
        // Đếm tổng số sản phẩm theo filter hiện tại
        $totalProducts = $this->buildFilterQuery($request)->count();
        $maxAllowed = 100000;

        if ($totalProducts > $maxAllowed) {
            return redirect()
                ->back()
                ->with('error', "Hiện tại chỉ cho phép xuất tối đa {$maxAllowed} sản phẩm. Vui lòng thu hẹp bộ lọc (hiện có {$totalProducts} sản phẩm).");
        }

        $fileName = 'products_export_'.now()->format('Y-m-d_H-i-s').'.xlsx';

        return response()->streamDownload(function () use ($request) {
            set_time_limit(0);
            ini_set('memory_limit', '512M');

            $cacheDir = sys_get_temp_dir() . '/phpspreadsheet_cache_' . uniqid();
            if (! is_dir($cacheDir)) {
                @mkdir($cacheDir, 0755, true);
            }

            try {
                if (method_exists(Settings::class, 'setCacheStorageMethod')) {
                    $cacheClass = 'PhpOffice\\PhpSpreadsheet\\Cell\\CachedObjectStorageFactory';
                    if (class_exists($cacheClass) && defined("{$cacheClass}::cache_to_discISAM")) {
                        Settings::setCacheStorageMethod(
                            constant("{$cacheClass}::cache_to_discISAM"),
                            ['dir' => $cacheDir]
                        );
                    } else {
                        Settings::setCacheStorageMethod('cache_to_discISAM', ['dir' => $cacheDir]);
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Export stream: cannot enable disc cache', [
                    'error' => $e->getMessage(),
                ]);
            }

            $categoryMap = Category::pluck('slug', 'id')->toArray();
            $brandMap = Brand::pluck('slug', 'id')->toArray();
            $tagMap = Tag::pluck('name', 'id')->toArray();
            $chunkSize = 200;

            $spreadsheet = new Spreadsheet();

            try {
                $productsSheet = $spreadsheet->getActiveSheet();
                $productsSheet->setTitle(ProductWorkbookSchema::SHEET_PRODUCTS);
                $productHeaders = ProductWorkbookSchema::productHeaders();
                $productsSheet->fromArray($productHeaders, null, 'A1');

                $productRow = 2;
                $this->buildFilterQuery($request)
                    ->select([
                        'id',
                        'sku',
                        'name',
                        'slug',
                        'description',
                        'short_description',
                        'price',
                        'sale_price',
                        'cost_price',
                        'stock_quantity',
                        'meta_title',
                        'meta_description',
                        'meta_keywords',
                        'meta_canonical',
                        'primary_category_id',
                        'brand_id',
                        'category_ids',
                        'tag_ids',
                        'is_featured',
                        'has_variants',
                        'created_by',
                        'is_active',
                        'link_shopee',
                    ])
                    ->chunkById($chunkSize, function ($products) use (&$productRow, $productsSheet, $productHeaders, $categoryMap, $brandMap, $tagMap) {
                        foreach ($products as $product) {
                            $this->writeExplicitSheetRow(
                                $productsSheet,
                                $productRow,
                                $productHeaders,
                                $this->buildProductExportRow($product, $categoryMap, $brandMap, $tagMap)
                            );
                            $productRow++;
                        }

                        unset($products);
                        gc_collect_cycles();
                    });

                $imagesSheet = $spreadsheet->createSheet();
                $imagesSheet->setTitle(ProductWorkbookSchema::SHEET_IMAGES);
                $imagesSheet->fromArray(ProductWorkbookSchema::imageHeaders(), null, 'A1');
                $imageRow = 2;

                $this->buildFilterQuery($request)
                    ->select(['id', 'sku'])
                    ->chunkById($chunkSize, function ($products) use ($imagesSheet, &$imageRow) {
                        $productsById = $products->keyBy('id');
                        $productIds = $productsById->keys()->all();

                        if ($productIds !== []) {
                            $images = Image::query()
                                ->whereIn('product_id', $productIds)
                                ->orderBy('product_id')
                                ->orderBy('order')
                                ->orderBy('id')
                                ->get();

                            foreach ($images as $image) {
                                $sku = $productsById[$image->product_id]->sku ?? '';
                                if ($sku === '') {
                                    continue;
                                }

                                $imagesSheet->fromArray([
                                    $this->buildImageExportRow($sku, $image),
                                ], null, 'A'.$imageRow);
                                $imageRow++;
                            }

                            unset($images);
                        }

                        unset($products, $productsById, $productIds);
                        gc_collect_cycles();
                    });

                $faqsSheet = $spreadsheet->createSheet();
                $faqsSheet->setTitle(ProductWorkbookSchema::SHEET_FAQS);
                $faqsSheet->fromArray(ProductWorkbookSchema::faqHeaders(), null, 'A1');
                $faqRow = 2;

                $this->buildFilterQuery($request)
                    ->select(['id', 'sku'])
                    ->chunkById($chunkSize, function ($products) use ($faqsSheet, &$faqRow) {
                        $productsById = $products->keyBy('id');
                        $productIds = $productsById->keys()->all();

                        if ($productIds !== []) {
                            $faqs = ProductFaq::query()
                                ->whereIn('product_id', $productIds)
                                ->orderBy('product_id')
                                ->orderBy('order')
                                ->orderBy('id')
                                ->get();

                            foreach ($faqs as $faq) {
                                $sku = $productsById[$faq->product_id]->sku ?? '';
                                if ($sku === '') {
                                    continue;
                                }

                                $faqsSheet->fromArray([[
                                    $sku,
                                    $faq->question,
                                    $faq->answer,
                                    $faq->order,
                                ]], null, 'A'.$faqRow);
                                $faqRow++;
                            }

                            unset($faqs);
                        }

                        unset($products, $productsById, $productIds);
                        gc_collect_cycles();
                    });

                $howTosSheet = $spreadsheet->createSheet();
                $howTosSheet->setTitle(ProductWorkbookSchema::SHEET_HOW_TOS);
                $howTosSheet->fromArray(ProductWorkbookSchema::howToHeaders(), null, 'A1');
                $howToRow = 2;

                $this->buildFilterQuery($request)
                    ->select(['id', 'sku'])
                    ->chunkById($chunkSize, function ($products) use ($howTosSheet, &$howToRow) {
                        $productsById = $products->keyBy('id');
                        $productIds = $productsById->keys()->all();

                        if ($productIds !== []) {
                            $howTos = ProductHowTo::query()
                                ->whereIn('product_id', $productIds)
                                ->orderBy('product_id')
                                ->orderBy('id')
                                ->get();

                            foreach ($howTos as $howTo) {
                                $sku = $productsById[$howTo->product_id]->sku ?? '';
                                if ($sku === '') {
                                    continue;
                                }

                                $howTosSheet->fromArray([[
                                    $sku,
                                    $howTo->title,
                                    $howTo->description,
                                    ! empty($howTo->steps) ? json_encode($howTo->steps, JSON_UNESCAPED_UNICODE) : '',
                                    ! empty($howTo->supplies) ? json_encode($howTo->supplies, JSON_UNESCAPED_UNICODE) : '',
                                    $howTo->is_active ? 1 : 0,
                                ]], null, 'A'.$howToRow);
                                $howToRow++;
                            }

                            unset($howTos);
                        }

                        unset($products, $productsById, $productIds);
                        gc_collect_cycles();
                    });

                $variantsSheet = $spreadsheet->createSheet();
                $variantsSheet->setTitle(ProductWorkbookSchema::SHEET_VARIANTS);
                $variantsSheet->fromArray(ProductWorkbookSchema::variantHeaders(), null, 'A1');
                $variantRow = 2;

                $this->buildFilterQuery($request)
                    ->select(['id', 'sku'])
                    ->chunkById($chunkSize, function ($products) use ($variantsSheet, &$variantRow) {
                        $productsById = $products->keyBy('id');
                        $productIds = $productsById->keys()->all();

                        if ($productIds !== []) {
                            $variants = ProductVariant::query()
                                ->whereIn('product_id', $productIds)
                                ->orderBy('product_id')
                                ->orderBy('id')
                                ->get();

                            foreach ($variants as $variant) {
                                $sku = $productsById[$variant->product_id]->sku ?? '';
                                if ($sku === '') {
                                    continue;
                                }

                                $variantsSheet->fromArray([
                                    $this->buildVariantExportRow($sku, $variant),
                                ], null, 'A'.$variantRow);
                                $variantRow++;
                            }

                            unset($variants);
                        }

                        unset($products, $productsById, $productIds);
                        gc_collect_cycles();
                    });

                $spreadsheet->setActiveSheetIndex(0);

                $writer = new Xlsx($spreadsheet);
                if (method_exists($writer, 'setPreCalculateFormulas')) {
                    $writer->setPreCalculateFormulas(false);
                }

                $writer->save('php://output');
            } finally {
                if (isset($spreadsheet)) {
                    $spreadsheet->disconnectWorksheets();
                    unset($spreadsheet);
                }

                if (isset($cacheDir) && is_dir($cacheDir)) {
                    foreach (glob($cacheDir . '/*') ?: [] as $file) {
                        @unlink($file);
                    }
                    @rmdir($cacheDir);
                }

                gc_collect_cycles();
            }
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-store, no-cache',
            'X-Accel-Buffering' => 'no', // Tắt buffering cho Nginx
        ]);
    }

    /**
     * Xử lý import Excel
     */
    public function import(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls|max:51200', // max 50MB
        ]);

        $errors = [];

        try {
            $file = $request->file('excel_file');
            $spreadsheet = IOFactory::load($file->getRealPath());

            DB::beginTransaction();

            // Import Products (Sheet 1)
            $this->importProducts($spreadsheet, $errors);

            // Import Images (Sheet 2)
            $this->importImages($spreadsheet, $errors);

            // Import FAQs (Sheet 3)
            $this->importFaqs($spreadsheet, $errors);

            // Import How-Tos (Sheet 4)
            $this->importHowTos($spreadsheet, $errors);

            // Import Variants (Sheet 5)
            $this->importVariants($spreadsheet, $errors);

            DB::commit();

            // Sau khi import thành công, xóa cache tất cả sản phẩm để dữ liệu luôn mới
            $this->clearAllProductCaches();

            $logFile = $this->writeErrorLog($errors, $file->getClientOriginalName());

            $message = 'Import thành công!';
            if (! empty($errors)) {
                $message .= ' Có '.count($errors).' lỗi đã được ghi vào file log.';
            }

            return redirect()->back()
                ->with('success', $message)
                ->with('log_file', $logFile);

        } catch (\Exception $e) {
            DB::rollBack();
            $errors[] = [
                'type' => 'SYSTEM_ERROR',
                'sku' => 'N/A',
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile()),
            ];
            $logFile = $this->writeErrorLog($errors, $request->file('excel_file')->getClientOriginalName());

            return redirect()->back()
                ->with('error', 'Lỗi import: '.$e->getMessage())
                ->with('log_file', $logFile);
        }
    }

    /**
     * Build Products Sheet
     */
    private function buildProductsSheet(Spreadsheet $spreadsheet, $products, array $categoryMap, array $brandMap, array $tagMap, $images)
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(ProductWorkbookSchema::SHEET_PRODUCTS);

        $headers = ProductWorkbookSchema::productHeaders();
        $sheet->fromArray($headers, null, 'A1');

        $row = 2;
        foreach ($products as $product) {
            $sheet->fromArray([
                $this->buildProductExportRow($product, $categoryMap, $brandMap, $tagMap),
            ], null, 'A'.$row);
            $row++;
        }
    }

    /**
     * Build Images Sheet
     */
    private function buildImagesSheet(Spreadsheet $spreadsheet, $products, $images)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle(ProductWorkbookSchema::SHEET_IMAGES);

        $headers = ProductWorkbookSchema::imageHeaders();
        $sheet->fromArray($headers, null, 'A1');

        $row = 2;
        foreach ($products as $product) {
            $productImages = $product->relationLoaded('images')
                ? $product->images
                : $images->where('product_id', $product->id)->sortBy([
                    ['order', 'asc'],
                    ['id', 'asc'],
                ]);

            foreach ($productImages as $image) {
                $sheet->fromArray([
                    $this->buildImageExportRow($product->sku ?? '', $image),
                ], null, 'A'.$row);
                $row++;
            }
        }
    }

    /**
     * Build FAQs Sheet
     */
    private function buildFaqsSheet(Spreadsheet $spreadsheet, $products)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle(ProductWorkbookSchema::SHEET_FAQS);

        $headers = ProductWorkbookSchema::faqHeaders();
        $sheet->fromArray($headers, null, 'A1');

        $row = 2;
        foreach ($products as $product) {
            foreach ($product->faqs as $faq) {
                $sheet->fromArray([
                    $product->sku,
                    $faq->question,
                    $faq->answer,
                    $faq->order,
                ], null, 'A'.$row);
                $row++;
            }
        }
    }

    /**
     * Build How-Tos Sheet
     */
    private function buildHowTosSheet(Spreadsheet $spreadsheet, $products)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle(ProductWorkbookSchema::SHEET_HOW_TOS);

        $headers = ProductWorkbookSchema::howToHeaders();
        $sheet->fromArray($headers, null, 'A1');

        $row = 2;
        foreach ($products as $product) {
            foreach ($product->howTos as $howTo) {
                $sheet->fromArray([
                    $product->sku,
                    $howTo->title,
                    $howTo->description,
                    ! empty($howTo->steps) ? json_encode($howTo->steps, JSON_UNESCAPED_UNICODE) : '',
                    ! empty($howTo->supplies) ? json_encode($howTo->supplies, JSON_UNESCAPED_UNICODE) : '',
                    $howTo->is_active ? 1 : 0,
                ], null, 'A'.$row);
                $row++;
            }
        }
    }

    /**
     * Build Variants Sheet
     */
    private function buildVariantsSheet(Spreadsheet $spreadsheet, $products): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle(ProductWorkbookSchema::SHEET_VARIANTS);

        $headers = ProductWorkbookSchema::variantHeaders();

        $sheet->fromArray($headers, null, 'A1');

        $row = 2;
        foreach ($products as $product) {
            if (! $product->variants || $product->variants->isEmpty()) {
                continue;
            }

            foreach ($product->variants as $variant) {
                $sheet->fromArray([
                    $this->buildVariantExportRow($product->sku, $variant),
                ], null, 'A'.$row);
                $row++;
            }
        }
    }

    private function findSheetByAliases($spreadsheet, array $aliases)
    {
        foreach ($aliases as $alias) {
            $sheet = $spreadsheet->getSheetByName($alias);
            if ($sheet) {
                return $sheet;
            }
        }

        return null;
    }

    private function loadProductsOnlySpreadsheet(string $filePath): Spreadsheet
    {
        $reader = IOFactory::createReaderForFile($filePath);

        if (method_exists($reader, 'setReadDataOnly')) {
            $reader->setReadDataOnly(true);
        }

        if (method_exists($reader, 'setLoadSheetsOnly')) {
            $reader->setLoadSheetsOnly(ProductWorkbookSchema::productSheetAliases());
        }

        return $reader->load($filePath);
    }

    private function buildHeaderIndex(array $headers): array
    {
        $headerIndex = [];

        foreach ($headers as $index => $header) {
            $normalized = strtolower(trim((string) $header));
            if ($normalized === '') {
                continue;
            }

            $headerIndex[$normalized] = $index;
        }

        return $headerIndex;
    }

    private function normalizeHeaderRow(array $headers): array
    {
        $normalizedHeaders = array_map(
            static fn ($header) => strtolower(trim((string) $header)),
            $headers
        );

        while ($normalizedHeaders !== [] && end($normalizedHeaders) === '') {
            array_pop($normalizedHeaders);
        }

        return array_values($normalizedHeaders);
    }

    private function extractSheetDataWithExactHeaders($sheet, array $expectedHeaders, string $sheetLabel): array
    {
        $rows = $sheet->toArray();
        $headers = array_shift($rows) ?? [];

        $normalizedHeaders = $this->normalizeHeaderRow($headers);
        $normalizedExpectedHeaders = $this->normalizeHeaderRow($expectedHeaders);

        if ($normalizedHeaders !== $normalizedExpectedHeaders) {
            throw new \InvalidArgumentException(
                'Sheet "' . $sheetLabel . '" không đúng cấu trúc file export chuẩn.'
            );
        }

        return [$headers, $this->buildHeaderIndex($headers), $rows];
    }

    private function hasHeader(array $headerIndex, $columns): bool
    {
        foreach ((array) $columns as $column) {
            $normalized = strtolower(trim((string) $column));
            if ($normalized !== '' && array_key_exists($normalized, $headerIndex)) {
                return true;
            }
        }

        return false;
    }

    private function getRowValueByHeader(array $row, array $headerIndex, $columns, $default = null)
    {
        foreach ((array) $columns as $column) {
            $normalized = strtolower(trim((string) $column));
            if ($normalized === '' || !array_key_exists($normalized, $headerIndex)) {
                continue;
            }

            $rowIndex = $headerIndex[$normalized];

            return array_key_exists($rowIndex, $row) ? $row[$rowIndex] : $default;
        }

        return $default;
    }

    private function getTrimmedRowValueByHeader(array $row, array $headerIndex, $columns, string $default = ''): string
    {
        foreach ((array) $columns as $column) {
            $normalized = strtolower(trim((string) $column));
            if ($normalized === '' || !array_key_exists($normalized, $headerIndex)) {
                continue;
            }

            $rowIndex = $headerIndex[$normalized];
            $value = array_key_exists($rowIndex, $row) ? $row[$rowIndex] : null;

            return trim((string) ($value ?? ''));
        }

        return trim((string) $default);
    }

    private function getBooleanRowValueByHeader(array $row, array $headerIndex, $columns, bool $default = false): bool
    {
        if (! $this->hasHeader($headerIndex, $columns)) {
            return $default;
        }

        $value = $this->getRowValueByHeader($row, $headerIndex, $columns, null);
        if ($value === null || $value === '') {
            return $default;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (float) $value > 0;
        }

        $normalized = strtolower(trim((string) $value));

        if (in_array($normalized, ['1', 'true', 'yes', 'y', 'on'], true)) {
            return true;
        }

        if (in_array($normalized, ['0', 'false', 'no', 'n', 'off'], true)) {
            return false;
        }

        return $default;
    }

    private function findBrandByReference(string $reference): ?Brand
    {
        $reference = trim($reference);
        if ($reference === '') {
            return null;
        }

        $slugCandidates = array_values(array_unique(array_filter([
            $reference,
            Str::slug($reference),
        ])));

        $brand = Brand::query()
            ->where('is_active', true)
            ->whereIn('slug', $slugCandidates)
            ->first();

        if ($brand) {
            return $brand;
        }

        return Brand::query()
            ->where('is_active', true)
            ->where('name', $reference)
            ->first();
    }

    private function findCategoryByReference(string $reference): ?Category
    {
        $reference = trim($reference);
        if ($reference === '') {
            return null;
        }

        $slugCandidates = array_values(array_unique(array_filter([
            $reference,
            Str::slug($reference),
        ])));

        $category = Category::query()
            ->whereIn('slug', $slugCandidates)
            ->first();

        if ($category) {
            return $category;
        }

        return Category::query()
            ->where('name', $reference)
            ->first();
    }

    private function buildVariantNameFromAttributes(array $attributes, ?string $fallbackSku = null): string
    {
        $parts = [];

        foreach (['color' => 'Màu', 'size' => 'Size'] as $key => $label) {
            $value = trim((string) ($attributes[$key] ?? ''));
            if ($value !== '') {
                $parts[] = $label . ' ' . $value;
            }
        }

        if ($parts !== []) {
            return implode(' / ', $parts);
        }

        $fallbackSku = trim((string) $fallbackSku);

        return $fallbackSku !== '' ? $fallbackSku : 'Variant mặc định';
    }

    private function buildImportedImageKeyMap($spreadsheet): array
    {
        $sheet = $this->findSheetByAliases($spreadsheet, ProductWorkbookSchema::imageSheetAliases());
        if (! $sheet) {
            return [];
        }

        $rows = $sheet->toArray();
        $headers = array_shift($rows);
        $headerIndex = $this->buildHeaderIndex($headers);
        $imageKeyMap = [];

        foreach ($rows as $row) {
            $imageKey = $this->getTrimmedRowValueByHeader($row, $headerIndex, ['image_key'], '');
            $rawUrl = $this->getTrimmedRowValueByHeader($row, $headerIndex, ['url', 'local_path'], '');
            $url = $this->normalizeImageColumnValue('url', $rawUrl !== '' ? basename($rawUrl) : '');

            if ($imageKey === '' || $url === '') {
                continue;
            }

            $image = Image::query()
                ->where('url', $url)
                ->latest('id')
                ->first();

            if (! $image && preg_match('/^IMG(\d+)$/i', $imageKey, $matches)) {
                $image = Image::find((int) $matches[1]);
            }

            if ($image) {
                $imageKeyMap[$imageKey] = $image->id;
            }
        }

        return $imageKeyMap;
    }

    private function buildDefaultMetaCanonical(?string $slug): ?string
    {
        $slug = trim((string) $slug);
        if ($slug === '') {
            return null;
        }

        $baseUrl = \App\Models\Setting::query()->where('key', 'site_url')->value('value') ?: config('app.url');
        $baseUrl = rtrim((string) $baseUrl, '/');

        return $baseUrl . '/san-pham/' . ltrim($slug, '/');
    }

    private function buildProductExportRow($product, array $categoryMap, array $brandMap, array $tagMap): array
    {
        $primarySlug = $product->primary_category_id ? ($categoryMap[$product->primary_category_id] ?? null) : optional($product->primaryCategory)->slug;
        $brandSlug = $product->brand_id ? ($brandMap[$product->brand_id] ?? null) : optional($product->brand)->slug;

        $categorySlugs = '';
        if (! empty($product->category_ids) && is_array($product->category_ids)) {
            $slugs = array_map(fn ($id) => $categoryMap[$id] ?? null, $product->category_ids);
            $categorySlugs = implode(',', array_filter($slugs));
        }

        $tagNames = '';
        if (! empty($product->tag_ids) && is_array($product->tag_ids)) {
            $names = array_map(fn ($id) => $tagMap[$id] ?? null, $product->tag_ids);
            $tagNames = implode(',', array_filter($names));
        }

        $metaKeywords = is_array($product->meta_keywords)
            ? implode(',', $product->meta_keywords)
            : ($product->meta_keywords ?? '');

        return [
            $product->sku,
            $product->name,
            $product->slug,
            $product->description,
            $product->short_description,
            $product->price,
            $product->sale_price,
            $product->cost_price,
            $product->stock_quantity,
            $product->meta_title,
            $product->meta_description,
            $metaKeywords,
            $product->meta_canonical,
            $primarySlug,
            $categorySlugs,
            $tagNames,
            $product->is_featured ? 1 : 0,
            $product->has_variants ? 1 : 0,
            $product->created_by,
            $product->is_active ? 1 : 0,
            $brandSlug,
            $product->link_shopee,
        ];
    }

    private function buildImageExportRow(string $productSku, Image $image): array
    {
        return [
            $productSku,
            'IMG'.$image->id,
            $this->normalizeImageColumnValue('url', $image->url),
            $this->normalizeImageColumnValue('title', $image->title),
            $this->normalizeImageColumnValue('notes', $image->notes),
            $this->normalizeImageColumnValue('alt', $image->alt),
            $image->is_primary ? 1 : 0,
            $image->order,
        ];
    }

    private function buildVariantExportRow(string $productSku, ProductVariant $variant): array
    {
        $attributes = is_array($variant->attributes)
            ? $variant->attributes
            : (is_string($variant->attributes) ? json_decode($variant->attributes, true) : []);

        return [
            $productSku,
            $variant->name,
            $variant->sku,
            $variant->price,
            $variant->sale_price,
            $variant->stock_quantity,
            $variant->image_id ? 'IMG' . $variant->image_id : null,
            ! empty($attributes) ? json_encode($attributes, JSON_UNESCAPED_UNICODE) : null,
            $variant->is_active ? 1 : 0,
        ];
    }

    private function writeExplicitSheetRow($sheet, int $rowNumber, array $headers, array $values): void
    {
        $numericHeaders = [
            'price',
            'sale_price',
            'cost_price',
            'stock_quantity',
            'is_featured',
            'has_variants',
            'created_by',
            'is_active',
        ];

        foreach ($values as $index => $value) {
            $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1) . $rowNumber;
            $header = $headers[$index] ?? '';

            if ($value === null || $value === '') {
                $sheet->setCellValueExplicit($cell, '', DataType::TYPE_STRING);

                continue;
            }

            $type = in_array($header, $numericHeaders, true) && is_numeric($value)
                ? DataType::TYPE_NUMERIC
                : DataType::TYPE_STRING;

            $sheet->setCellValueExplicit($cell, $value, $type);
        }
    }

    private function buildImportedImagePayload(?Product $product, string $url, string $title = '', string $notes = '', string $alt = '', bool $isPrimary = false, int $order = 0): array
    {
        $normalizedUrl = $this->normalizeImageColumnValue('url', basename($url));
        $normalizedPath = $this->normalizeImageColumnValue('path', $url);
        $normalizedName = $this->normalizeImageFileName($url);

        $payload = [
            'title' => $this->normalizeImageColumnValue('title', $title !== '' ? $title : null),
            'notes' => $this->normalizeImageColumnValue('notes', $notes !== '' ? $notes : null),
            'alt' => $this->normalizeImageColumnValue('alt', $alt !== '' ? $alt : null),
            'is_primary' => $isPrimary,
            'order' => $order,
            'path' => $normalizedPath,
            'url' => $normalizedUrl,
            'thumbnail_url' => $this->normalizeImageColumnValue('thumbnail_url', $normalizedUrl),
            'medium_url' => $this->normalizeImageColumnValue('medium_url', $normalizedUrl),
            'name' => $normalizedName,
        ];

        if ($product) {
            $payload['product_id'] = $product->id;
            $payload['entity_type'] = 'product';
            $payload['entity_id'] = $product->id;
            $payload['role'] = $isPrimary ? 'primary' : 'gallery';
            $payload['context'] = 'product';
        }

        return $payload;
    }

    private function normalizeImageFileName(?string $path): ?string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return null;
        }

        $fileName = basename((string) (parse_url($path, PHP_URL_PATH) ?: $path));

        return $this->normalizeImageColumnValue('name', $fileName);
    }

    private function normalizeImageColumnValue(string $column, mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if (! is_string($value)) {
            return $value;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $length = $this->getImageColumnLength($column);
        if ($length === null || mb_strlen($value) <= $length) {
            return $value;
        }

        if (in_array($column, ['name', 'path', 'url', 'thumbnail_url', 'medium_url'], true)) {
            return $this->truncatePathLikeValue($value, $length);
        }

        return mb_substr($value, 0, $length);
    }

    private function truncatePathLikeValue(string $value, int $length): string
    {
        if (mb_strlen($value) <= $length) {
            return $value;
        }

        $parsedPath = (string) (parse_url($value, PHP_URL_PATH) ?: $value);
        $extension = pathinfo($parsedPath, PATHINFO_EXTENSION);

        if ($extension === '') {
            return mb_substr($value, 0, $length);
        }

        $suffix = '.' . $extension;
        $baseLength = max(1, $length - mb_strlen($suffix));

        return rtrim(mb_substr($value, 0, $baseLength), '.') . $suffix;
    }

    private function getImageColumnLength(string $column): ?int
    {
        if (array_key_exists($column, $this->imageColumnLengths)) {
            return $this->imageColumnLengths[$column];
        }

        $info = DB::table('information_schema.columns')
            ->select('CHARACTER_MAXIMUM_LENGTH')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', 'images')
            ->where('COLUMN_NAME', $column)
            ->first();

        $length = $info?->CHARACTER_MAXIMUM_LENGTH;
        $this->imageColumnLengths[$column] = $length !== null ? (int) $length : null;

        return $this->imageColumnLengths[$column];
    }

    private function syncImportedImageAsset(?Image $image, string $sourceValue, array $payload, array &$errors, array $context = []): array
    {
        $fileHelper = app(FileHelperService::class);
        $targetRelativePath = $this->resolveImportedImageTargetRelativePath($sourceValue, $image);

        if ($targetRelativePath !== null && $fileHelper->fileExists($targetRelativePath)) {
            return $this->hydrateImportedImagePayloadFromFile($payload, $targetRelativePath);
        }

        $sourceAbsolutePath = $this->resolveImportedImageSourceAbsolutePath($sourceValue, $image);

        if ($sourceAbsolutePath === null) {
            $existingRelativePath = $this->findExistingImportedImageRelativePath($image);
            if ($existingRelativePath !== null) {
                return $this->hydrateImportedImagePayloadFromFile($payload, $existingRelativePath);
            }

            if ($targetRelativePath !== null && ! $this->isExternalImageReference($sourceValue)) {
                $errors[] = [
                    'type' => 'IMAGE_FILE_MISSING',
                    'sku' => $context['sku'] ?? 'N/A',
                    'message' => "Không tìm thấy file vật lý để đồng bộ ảnh '{$sourceValue}'.",
                    'row' => $context['row'] ?? null,
                    'sheet' => $context['sheet'] ?? ProductWorkbookSchema::SHEET_IMAGES,
                ];
            }

            return $image ? $this->mergeExistingImageFileValuesIntoPayload($payload, $image) : $payload;
        }

        if ($targetRelativePath === null) {
            $targetRelativePath = $this->buildImportedImageDefaultTargetRelativePath($sourceAbsolutePath, $image);
        }

        try {
            $syncedRelativePath = $this->copyImportedImageToManagedPath($sourceAbsolutePath, $targetRelativePath);

            return $this->hydrateImportedImagePayloadFromFile($payload, $syncedRelativePath);
        } catch (\Throwable $e) {
            Log::warning('Import image asset sync failed', [
                'source' => $sourceValue,
                'target' => $targetRelativePath,
                'sku' => $context['sku'] ?? null,
                'row' => $context['row'] ?? null,
                'sheet' => $context['sheet'] ?? ProductWorkbookSchema::SHEET_IMAGES,
                'error' => $e->getMessage(),
            ]);

            $errors[] = [
                'type' => 'IMAGE_SYNC_FAILED',
                'sku' => $context['sku'] ?? 'N/A',
                'message' => "Không thể đồng bộ file ảnh '{$sourceValue}': {$e->getMessage()}",
                'row' => $context['row'] ?? null,
                'sheet' => $context['sheet'] ?? ProductWorkbookSchema::SHEET_IMAGES,
            ];

            return $image ? $this->mergeExistingImageFileValuesIntoPayload($payload, $image) : $payload;
        }
    }

    private function resolveImportedImageSourceAbsolutePath(string $sourceValue, ?Image $image = null): ?string
    {
        $fileHelper = app(FileHelperService::class);
        $importsDirectory = trim((string) config('media.directories.imports', 'clients/assets/img/imports'), '/');
        $candidates = [];

        $normalizedSource = $fileHelper->normalizeRelativePath($sourceValue);
        if ($normalizedSource && ! $this->isExternalImageReference($normalizedSource)) {
            $candidates[] = $normalizedSource;

            $sourceBasename = basename($normalizedSource);
            if ($sourceBasename !== '' && $sourceBasename !== $normalizedSource) {
                $candidates[] = $importsDirectory . '/' . $sourceBasename;
            } elseif ($sourceBasename !== '') {
                $candidates[] = $importsDirectory . '/' . $sourceBasename;
            }
        }

        if ($image) {
            foreach ([$image->path, $image->url, $image->thumbnail_url, $image->medium_url] as $candidate) {
                $normalizedCandidate = $fileHelper->normalizeRelativePath($candidate);
                if (! $normalizedCandidate || $this->isExternalImageReference($normalizedCandidate)) {
                    continue;
                }

                $candidates[] = $normalizedCandidate;

                $candidateBasename = basename($normalizedCandidate);
                if ($candidateBasename !== '') {
                    $candidates[] = $importsDirectory . '/' . $candidateBasename;
                }
            }
        }

        foreach (array_values(array_unique(array_filter($candidates))) as $relativePath) {
            $absolutePath = $fileHelper->toAbsolutePath($relativePath);
            if ($absolutePath && is_file($absolutePath)) {
                return $absolutePath;
            }
        }

        return null;
    }

    private function findExistingImportedImageRelativePath(?Image $image = null): ?string
    {
        if (! $image) {
            return null;
        }

        $fileHelper = app(FileHelperService::class);

        foreach ([$image->path, $image->url, $image->thumbnail_url, $image->medium_url] as $candidate) {
            $normalizedCandidate = $fileHelper->normalizeRelativePath($candidate);
            if (! $normalizedCandidate || $this->isExternalImageReference($normalizedCandidate)) {
                continue;
            }

            if ($fileHelper->fileExists($normalizedCandidate)) {
                return $normalizedCandidate;
            }
        }

        return null;
    }

    private function mergeExistingImageFileValuesIntoPayload(array $payload, Image $image): array
    {
        foreach ([
            'name',
            'path',
            'url',
            'thumbnail_url',
            'medium_url',
            'extension',
            'mime_type',
            'size',
            'width',
            'height',
            'file_modified_at',
        ] as $column) {
            $existingValue = $image->{$column} ?? null;
            if ($existingValue !== null && $existingValue !== '') {
                $payload[$column] = $existingValue;
            }
        }

        return $payload;
    }

    private function resolveImportedImageTargetRelativePath(string $sourceValue, ?Image $image = null): ?string
    {
        $fileHelper = app(FileHelperService::class);
        $clothesDirectory = trim((string) config('media.directories.clothes', 'clients/assets/img/clothes'), '/');

        foreach ([
            $image?->path,
            $image?->url,
            $sourceValue,
        ] as $candidate) {
            $normalizedCandidate = $fileHelper->normalizeRelativePath($candidate);
            if (! $normalizedCandidate || $this->isExternalImageReference($normalizedCandidate)) {
                continue;
            }

            if ($this->isManagedClothesImagePath($normalizedCandidate)) {
                return $normalizedCandidate;
            }
        }

        $normalizedSource = $fileHelper->normalizeRelativePath($sourceValue);
        if (! $normalizedSource || $this->isExternalImageReference($normalizedSource)) {
            return null;
        }

        $filename = basename($normalizedSource);
        if ($filename === '' || $filename === '.' || $filename === DIRECTORY_SEPARATOR) {
            $filename = $image?->name ?: 'product-image.webp';
        }

        $filename = $this->sanitizeImportedImageFilename($filename);

        return $clothesDirectory . '/' . $filename;
    }

    private function buildImportedImageDefaultTargetRelativePath(string $sourceAbsolutePath, ?Image $image = null): string
    {
        $clothesDirectory = trim((string) config('media.directories.clothes', 'clients/assets/img/clothes'), '/');
        $sourceFilename = basename($sourceAbsolutePath);
        $filename = $this->sanitizeImportedImageFilename($image?->name ?: $sourceFilename);

        $sourceExtension = strtolower(pathinfo($sourceAbsolutePath, PATHINFO_EXTENSION) ?: 'webp');
        $filenameInfo = pathinfo($filename);
        $baseName = $filenameInfo['filename'] ?? 'product-image';

        return $clothesDirectory . '/' . $baseName . '.' . $sourceExtension;
    }

    private function sanitizeImportedImageFilename(string $filename): string
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION) ?: 'webp');
        $baseName = pathinfo($filename, PATHINFO_FILENAME);
        $baseName = Str::slug($baseName, '-');

        if ($baseName === '') {
            $baseName = 'product-image';
        }

        return $baseName . '.' . $extension;
    }

    private function copyImportedImageToManagedPath(string $sourceAbsolutePath, string $targetRelativePath): string
    {
        $fileHelper = app(FileHelperService::class);
        $normalizedTargetRelativePath = $fileHelper->normalizeRelativePath($targetRelativePath) ?? $targetRelativePath;
        $sourceExtension = strtolower(pathinfo($sourceAbsolutePath, PATHINFO_EXTENSION) ?: 'webp');
        $targetInfo = pathinfo($normalizedTargetRelativePath);
        $targetDirectory = trim((string) ($targetInfo['dirname'] ?? ''), '/');
        $targetBaseName = $targetInfo['filename'] ?? 'product-image';
        $targetExtension = strtolower($targetInfo['extension'] ?? '');

        if ($targetExtension !== $sourceExtension) {
            $normalizedTargetRelativePath = ($targetDirectory !== '' ? $targetDirectory . '/' : '')
                . $targetBaseName . '.' . $sourceExtension;
        }

        $targetAbsolutePath = public_path($normalizedTargetRelativePath);
        $fileHelper->ensureDirectory(dirname($targetAbsolutePath));

        $realSourcePath = realpath($sourceAbsolutePath);
        $realTargetPath = is_file($targetAbsolutePath) ? realpath($targetAbsolutePath) : false;
        if ($realSourcePath !== false && $realTargetPath !== false && $realSourcePath === $realTargetPath) {
            @chmod($targetAbsolutePath, 0644);

            return $normalizedTargetRelativePath;
        }

        if (is_file($targetAbsolutePath) && ! $this->sameFileContents($sourceAbsolutePath, $targetAbsolutePath)) {
            $normalizedTargetRelativePath = $this->buildUniqueImportedImageTargetRelativePath($targetDirectory, $targetBaseName, $sourceExtension);
            $targetAbsolutePath = public_path($normalizedTargetRelativePath);
            $fileHelper->ensureDirectory(dirname($targetAbsolutePath));
        }

        if (! is_file($targetAbsolutePath)) {
            if (! @copy($sourceAbsolutePath, $targetAbsolutePath)) {
                throw new \RuntimeException("Không thể copy ảnh từ '{$sourceAbsolutePath}' sang '{$targetAbsolutePath}'.");
            }
        }

        @chmod($targetAbsolutePath, 0644);

        return $normalizedTargetRelativePath;
    }

    private function buildUniqueImportedImageTargetRelativePath(string $directory, string $baseName, string $extension): string
    {
        $directory = trim($directory, '/');
        $counter = 1;

        do {
            $suffix = now()->format('YmdHis') . '-' . Str::lower(Str::random(4)) . '-' . $counter;
            $candidate = ($directory !== '' ? $directory . '/' : '') . $baseName . '-' . $suffix . '.' . $extension;
            $counter++;
        } while (is_file(public_path($candidate)));

        return $candidate;
    }

    private function hydrateImportedImagePayloadFromFile(array $payload, string $relativePath): array
    {
        $fileHelper = app(FileHelperService::class);
        $normalizedRelativePath = $fileHelper->normalizeRelativePath($relativePath) ?? $relativePath;
        $absolutePath = public_path($normalizedRelativePath);
        $extension = pathinfo($normalizedRelativePath, PATHINFO_EXTENSION);

        $payload['path'] = $this->normalizeImageColumnValue('path', $normalizedRelativePath);
        $filenameOnly = basename($normalizedRelativePath);
        $payload['url'] = $this->normalizeImageColumnValue('url', $filenameOnly);
        $payload['thumbnail_url'] = $this->normalizeImageColumnValue('thumbnail_url', $filenameOnly);
        $payload['medium_url'] = $this->normalizeImageColumnValue('medium_url', $filenameOnly);
        $payload['name'] = $this->normalizeImageColumnValue('name', basename($normalizedRelativePath));
        $payload['extension'] = $extension !== '' ? strtolower($extension) : null;

        if (! is_file($absolutePath)) {
            return $payload;
        }

        $payload['size'] = filesize($absolutePath) ?: null;
        $payload['mime_type'] = @mime_content_type($absolutePath) ?: null;
        $payload['file_modified_at'] = date('Y-m-d H:i:s', filemtime($absolutePath) ?: time());

        $dimensions = @getimagesize($absolutePath);
        if ($dimensions) {
            $payload['width'] = $dimensions[0] ?? null;
            $payload['height'] = $dimensions[1] ?? null;
        }

        return $payload;
    }

    private function isManagedClothesImagePath(?string $relativePath): bool
    {
        $fileHelper = app(FileHelperService::class);
        $normalizedRelativePath = $fileHelper->normalizeRelativePath($relativePath);
        if (! $normalizedRelativePath || $this->isExternalImageReference($normalizedRelativePath)) {
            return false;
        }

        return $fileHelper->isManagedMediaPath($normalizedRelativePath, [
            config('media.directories.clothes', 'clients/assets/img/clothes'),
        ]);
    }

    private function isExternalImageReference(?string $value): bool
    {
        $value = trim((string) $value);

        return $value !== '' && Str::startsWith($value, ['http://', 'https://']);
    }

    private function sameFileContents(string $sourceAbsolutePath, string $targetAbsolutePath): bool
    {
        if (! is_file($sourceAbsolutePath) || ! is_file($targetAbsolutePath)) {
            return false;
        }

        $sourceSize = @filesize($sourceAbsolutePath);
        $targetSize = @filesize($targetAbsolutePath);
        if ($sourceSize === false || $targetSize === false || $sourceSize !== $targetSize) {
            return false;
        }

        return hash_file('sha1', $sourceAbsolutePath) === hash_file('sha1', $targetAbsolutePath);
    }

    private function syncImportedProductImages(Product $product, array $keepImageIds): void
    {
        $keepImageIds = array_values(array_unique(array_filter(array_map('intval', $keepImageIds))));

        if ($keepImageIds === []) {
            return;
        }

        Image::whereIn('id', $keepImageIds)->update([
            'product_id' => $product->id,
            'entity_type' => 'product',
            'entity_id' => $product->id,
            'context' => 'product',
        ]);

        $primaryImage = Image::where('product_id', $product->id)
            ->whereIn('id', $keepImageIds)
            ->where('is_primary', true)
            ->orderBy('order')
            ->orderBy('id')
            ->first();

        if (! $primaryImage) {
            $primaryImage = Image::where('product_id', $product->id)
                ->whereIn('id', $keepImageIds)
                ->orderBy('order')
                ->orderBy('id')
                ->first();
        }

        if ($primaryImage) {
            Image::where('product_id', $product->id)
                ->whereIn('id', $keepImageIds)
                ->where('id', '!=', $primaryImage->id)
                ->update([
                    'is_primary' => false,
                    'role' => 'gallery',
                ]);

            $primaryImage->update([
                'is_primary' => true,
                'role' => 'primary',
            ]);
        }

        Image::where('product_id', $product->id)
            ->whereNotIn('id', $keepImageIds)
            ->delete();

        $this->clearProductCacheEntry($product);
    }

    private function clearProductCacheEntry(Product $product): void
    {
        Cache::forget('product_detail_'.$product->slug);
        Cache::forget('slug_type_'.$product->slug);
    }

    /**
     * Import Products
     */
    private function importProducts($spreadsheet, &$errors)
    {
        $sheet = $this->findSheetByAliases($spreadsheet, ProductWorkbookSchema::productSheetAliases());
        if (! $sheet) {
            Log::error('Import products: Sheet products không tồn tại', [
                'available_sheets' => $spreadsheet->getSheetNames(),
            ]);
            throw new \Exception('Sheet "products" không tồn tại!');
        }

        [, $headerIndex, $rows] = $this->extractSheetDataWithExactHeaders(
            $sheet,
            ProductWorkbookSchema::productHeaders(),
            ProductWorkbookSchema::SHEET_PRODUCTS
        );
        $sheetTitle = $sheet->getTitle();

        $categoryMap = [];
        $brandMap = [];
        $tagCache = [];

        foreach ($rows as $rowIndex => $row) {
            if ($this->getTrimmedRowValueByHeader($row, $headerIndex, ['sku'], trim((string) ($row[0] ?? ''))) === '') {
                continue;
            } // Bỏ qua dòng trống (SKU rỗng)

            $sku = $this->getTrimmedRowValueByHeader($row, $headerIndex, ['sku'], trim((string) ($row[0] ?? '')));
            $name = $this->getTrimmedRowValueByHeader($row, $headerIndex, ['name'], trim((string) ($row[1] ?? '')));
            // Logic slug: ưu tiên slug từ Excel, nếu không có thì dùng SKU, cuối cùng fallback về name
            $slug = $this->getTrimmedRowValueByHeader($row, $headerIndex, ['slug'], trim((string) ($row[2] ?? '')));
            if (empty($slug)) {
                $slug = Str::slug($sku ?: $name);
            }
            $description = $this->getTrimmedRowValueByHeader($row, $headerIndex, ['description'], trim((string) ($row[3] ?? '')));
            $shortDescription = $this->getTrimmedRowValueByHeader($row, $headerIndex, ['short_description'], trim((string) ($row[4] ?? '')));
            $price = (float) $this->getRowValueByHeader($row, $headerIndex, ['price'], $row[5] ?? 0);
            $salePriceRaw = $this->getRowValueByHeader($row, $headerIndex, ['sale_price'], $row[6] ?? null);
            $salePrice = $salePriceRaw !== null && $salePriceRaw !== '' ? (float) $salePriceRaw : null;
            $costPriceRaw = $this->getRowValueByHeader($row, $headerIndex, ['cost_price'], $row[7] ?? null);
            $costPrice = $costPriceRaw !== null && $costPriceRaw !== '' ? (float) $costPriceRaw : null;
            $stockQuantityRaw = $this->getRowValueByHeader($row, $headerIndex, ['stock_quantity'], $row[8] ?? 0);
            $stockQuantity = $stockQuantityRaw !== null && $stockQuantityRaw !== '' ? (int) $stockQuantityRaw : 0;
            $metaTitle = $this->getTrimmedRowValueByHeader($row, $headerIndex, ['meta_title'], trim((string) ($row[9] ?? '')));
            $metaDescription = $this->getTrimmedRowValueByHeader($row, $headerIndex, ['meta_description'], trim((string) ($row[10] ?? '')));
            $metaKeywordsRaw = $this->getTrimmedRowValueByHeader($row, $headerIndex, ['meta_keywords'], trim((string) ($row[11] ?? '')));
            $metaCanonical = $this->getTrimmedRowValueByHeader($row, $headerIndex, ['meta_canonical'], trim((string) ($row[12] ?? '')));
            $primaryCategorySlug = $this->getTrimmedRowValueByHeader($row, $headerIndex, ['primary_category_slug'], trim((string) ($row[13] ?? '')));
            $brandSlug = $this->getTrimmedRowValueByHeader($row, $headerIndex, ['brand_slug'], trim((string) ($row[20] ?? $row[14] ?? '')));
            $categorySlugs = $this->getTrimmedRowValueByHeader($row, $headerIndex, ['category_slugs'], trim((string) ($row[14] ?? $row[15] ?? '')));
            $tagSlugs = $this->getTrimmedRowValueByHeader($row, $headerIndex, ['tag_slugs', 'tag_names'], trim((string) ($row[15] ?? $row[16] ?? '')));
            $isFeatured = $this->getBooleanRowValueByHeader($row, $headerIndex, ['is_featured'], false);
            $hasVariants = $this->getBooleanRowValueByHeader($row, $headerIndex, ['has_variants'], false);
            $isActive = $this->getBooleanRowValueByHeader($row, $headerIndex, ['is_active'], true);
            $createdByRaw = $this->getRowValueByHeader($row, $headerIndex, ['created_by'], $row[18] ?? $row[21] ?? null);
            $createdBy = $createdByRaw !== null && $createdByRaw !== ''
                ? (int) $createdByRaw
                : (Auth::check() ? Auth::id() : 1);
            $linkShopee = $this->getTrimmedRowValueByHeader($row, $headerIndex, ['link_shopee'], trim((string) ($row[21] ?? $row[22] ?? '')));

            if (empty($name)) {
                continue;
            }

            // Xử lý meta_keywords
            $metaKeywords = null;
            if (! empty($metaKeywordsRaw)) {
                $metaKeywords = array_filter(array_map('trim', explode(',', $metaKeywordsRaw)));
            }

            $resolvedMetaCanonical = $metaCanonical !== '' ? $metaCanonical : $this->buildDefaultMetaCanonical($slug);

            // Xử lý brand_id
            $brandId = null;
            if (! empty($brandSlug)) {
                $brandLookupKey = mb_strtolower($brandSlug);
                if (isset($brandMap[$brandLookupKey])) {
                    $brandId = $brandMap[$brandLookupKey];
                } else {
                    $brand = $this->findBrandByReference($brandSlug);
                    if ($brand) {
                        $brandId = $brand->id;
                        $brandMap[$brandLookupKey] = $brand->id;
                    } else {
                        $errors[] = [
                            'type' => 'BRAND_NOT_FOUND',
                            'sku' => $sku ?: 'N/A',
                            'brand_slug' => $brandSlug,
                            'message' => "Brand với slug '{$brandSlug}' không tồn tại hoặc không active.",
                            'row' => $rowIndex + 2,
                            'sheet' => $sheetTitle,
                        ];
                    }
                }
            }

            // Xử lý primary_category_id
            $primaryCategoryId = null;
            if (! empty($primaryCategorySlug)) {
                $primaryCategoryLookupKey = mb_strtolower($primaryCategorySlug);
                if (isset($categoryMap[$primaryCategoryLookupKey])) {
                    $primaryCategoryId = $categoryMap[$primaryCategoryLookupKey];
                } else {
                    $cat = $this->findCategoryByReference($primaryCategorySlug);
                    if ($cat) {
                        $primaryCategoryId = $cat->id;
                        $categoryMap[$primaryCategoryLookupKey] = $cat->id;
                    } else {
                        $errors[] = [
                            'type' => 'PRIMARY_CATEGORY_NOT_FOUND',
                            'sku' => $sku ?: 'N/A',
                            'category_slug' => $primaryCategorySlug,
                            'message' => "Primary category với slug '{$primaryCategorySlug}' không tồn tại.",
                            'row' => $rowIndex + 2,
                            'sheet' => $sheetTitle,
                        ];
                    }
                }
            }

            // Xử lý category_ids
            $categoryIds = [];
            if (! empty($categorySlugs)) {
                $categorySlugArray = array_map('trim', explode(',', $categorySlugs));
                foreach ($categorySlugArray as $catSlug) {
                    if (empty($catSlug)) {
                        continue;
                    }
                    $categoryLookupKey = mb_strtolower($catSlug);
                    if (isset($categoryMap[$categoryLookupKey])) {
                        $categoryIds[] = $categoryMap[$categoryLookupKey];
                    } else {
                        $cat = $this->findCategoryByReference($catSlug);
                        if ($cat) {
                            $categoryIds[] = $cat->id;
                            $categoryMap[$categoryLookupKey] = $cat->id;
                        } else {
                            $errors[] = [
                                'type' => 'CATEGORY_NOT_FOUND',
                                'sku' => $sku ?: 'N/A',
                                'category_slug' => $catSlug,
                                'message' => "Category với slug '{$catSlug}' không tồn tại.",
                                'row' => $rowIndex + 2,
                                'sheet' => $sheetTitle,
                            ];
                        }
                    }
                }
            }

            // Xử lý tag_ids
            $tagIds = [];
            if (! empty($tagSlugs)) {
                $tagNames = array_map('trim', explode(',', $tagSlugs));
                foreach ($tagNames as $tagName) {
                    if (empty($tagName)) {
                        continue;
                    }
                    
                    // Bỏ qua các tag có tên không hợp lệ (như thông báo lỗi API)
                    // Không giới hạn độ dài nữa vì cột name đã là text
                    if (str_contains($tagName, 'Bandwidth quota exceeded') || str_contains($tagName, 'Lỗi:') || str_contains($tagName, 'SQLSTATE')) {
                        continue;
                    }
                    
                    $slugTag = Str::slug($tagName);
                    if (empty($slugTag)) {
                        continue;
                    }
                    
                    // Giới hạn độ dài slug tối đa 255 ký tự (slug vẫn là string)
                    $slugTag = mb_substr($slugTag, 0, 255);

                    if (isset($tagCache[$slugTag])) {
                        $tagIds[] = $tagCache[$slugTag];

                        continue;
                    }

                    $tag = Tag::where('slug', $slugTag)->first();
                    if (! $tag) {
                        try {
                            $tag = Tag::create([
                                'name' => $tagName,
                                'slug' => $slugTag,
                                'is_active' => true,
                                'entity_id' => 0,
                                'entity_type' => \App\Models\Product::class,
                            ]);
                        } catch (\Exception $e) {
                            // Nếu lỗi khi tạo tag (ví dụ: name quá dài), bỏ qua và log
                            Log::warning('Import products: Không thể tạo tag', [
                                'tag_name' => $tagName,
                                'tag_slug' => $slugTag,
                                'error' => $e->getMessage(),
                            ]);
                            continue;
                        }
                    }

                    if ($tag) {
                        $tagCache[$slugTag] = $tag->id;
                        $tagIds[] = $tag->id;
                    }
                }
            }

            // Tìm product theo SKU
            $product = Product::where('sku', $sku)->first();

            // Chuẩn bị data để update/create
            // QUAN TRỌNG: Chỉ thêm các trường có giá trị (không rỗng) để tránh ghi đè dữ liệu cũ
            $data = [];
            $hasOtherData = false;
            
            // Chỉ thêm các trường có giá trị (không rỗng)
            if (!empty($name)) {
                $data['name'] = $name;
                $data['slug'] = $slug;
                $hasOtherData = true;
            }
            
            if (!empty($description)) {
                $data['description'] = $description;
                $hasOtherData = true;
            }
            if (!empty($shortDescription)) {
                $data['short_description'] = $shortDescription;
                $hasOtherData = true;
            }
            if ($price > 0) {
                $data['price'] = $price;
                $hasOtherData = true;
            }
            if ($salePrice !== null && $salePrice !== '') {
                $data['sale_price'] = $salePrice;
                $hasOtherData = true;
            }
            if ($costPrice !== null && $costPrice !== '') {
                $data['cost_price'] = $costPrice;
                $hasOtherData = true;
            }
            if ($stockQuantity !== null && $stockQuantity !== '') {
                $data['stock_quantity'] = $stockQuantity;
                $hasOtherData = true;
            }
            if (!empty($metaTitle)) {
                $data['meta_title'] = $metaTitle;
                $hasOtherData = true;
            }
            if (!empty($metaDescription)) {
                $data['meta_description'] = $metaDescription;
                $hasOtherData = true;
            }
            if (!empty($metaKeywords)) {
                $data['meta_keywords'] = $metaKeywords;
                $hasOtherData = true;
            }
            
            if ($primaryCategoryId !== null) {
                $data['primary_category_id'] = $primaryCategoryId;
                $hasOtherData = true;
            }
            if ($brandId !== null) {
                $data['brand_id'] = $brandId;
                $hasOtherData = true;
            }
            if (!empty($categoryIds)) {
                $data['category_ids'] = $categoryIds;
                $hasOtherData = true;
            }
            if (!empty($tagIds)) {
                $data['tag_ids'] = $tagIds;
                $hasOtherData = true;
            }
            if ($this->hasHeader($headerIndex, ['has_variants'])) {
                $data['has_variants'] = $hasVariants;
                $hasOtherData = true;
            }
            
            // is_featured và is_active chỉ update nếu có giá trị trong Excel (không phải mặc định)
            // Kiểm tra xem có giá trị trong Excel không (không phải mặc định false/true)
            if ($this->hasHeader($headerIndex, ['is_featured'])) {
                $data['is_featured'] = $isFeatured;
                $hasOtherData = true;
            }
            if ($this->hasHeader($headerIndex, ['is_active'])) {
                $data['is_active'] = $isActive;
                $hasOtherData = true;
            }
            if ($this->hasHeader($headerIndex, ['link_shopee'])) {
                $data['link_shopee'] = $linkShopee;
                $hasOtherData = true;
            }
            
            if (! empty($resolvedMetaCanonical)) {
                $data['meta_canonical'] = $resolvedMetaCanonical;
            }

            // KIỂM TRA: Nếu hàng quá trống (chỉ có SKU và name, không có dữ liệu khác) → bỏ qua
            if (empty($name) || !$hasOtherData) {
                // Không có dữ liệu để xử lý, bỏ qua hàng này
                continue;
            }

            // Nếu là CREATE mới, cần có ít nhất name và price
            if (!$product) {
                if (empty($name) || !isset($data['price']) || $data['price'] <= 0) {
                    // Không đủ dữ liệu để tạo mới, bỏ qua
                    $errors[] = [
                        'type' => 'INSUFFICIENT_DATA',
                        'sku' => $sku,
                        'message' => "Không đủ dữ liệu để tạo sản phẩm mới. Cần có ít nhất name và price > 0.",
                        'row' => $rowIndex + 2,
                        'sheet' => $sheetTitle,
                    ];
                    continue;
                }
                // Đảm bảo có giá trị mặc định cho các trường bắt buộc khi tạo mới
                if (!isset($data['stock_quantity'])) {
                    $data['stock_quantity'] = $stockQuantity ?? 0;
                }
                if (!isset($data['is_featured'])) {
                    $data['is_featured'] = false;
                }
                if (!isset($data['has_variants'])) {
                    $data['has_variants'] = false;
                }
                if (!isset($data['is_active'])) {
                    $data['is_active'] = true;
                }
                $data['created_by'] = $createdBy;
            }

            try {
                if ($product) {
                // Lưu slug cũ và is_active cũ để xóa cache
                $oldSlug = $product->slug;
                $oldIsActive = $product->is_active;

                // Update: chỉ cập nhật các trường có trong $data (có giá trị từ Excel)
                $updateData = [];
                foreach ($data as $key => $value) {
                    // Bỏ qua created_by khi update
                    if ($key === 'created_by') {
                        continue;
                    }
                    
                    // So sánh giá trị cũ và mới
                    $oldValue = $product->$key;
                    if ($key === 'category_ids' || $key === 'tag_ids' || $key === 'meta_keywords') {
                        // So sánh array
                        $oldArray = is_array($oldValue) ? $oldValue : [];
                        $newArray = is_array($value) ? $value : [];
                        sort($oldArray);
                        sort($newArray);
                        if ($oldArray !== $newArray) {
                            $updateData[$key] = $value;
                        }
                    } elseif ($oldValue != $value) {
                        $updateData[$key] = $value;
                    }
                }

                // Nếu có thay đổi → xóa cache
                if (! empty($updateData)) {
                    // Lưu oldTagIds trước khi update để cập nhật usage_count
                    $oldTagIds = is_array($product->tag_ids) ? $product->tag_ids : json_decode($product->tag_ids, true) ?? [];
                    $newTagIds = isset($updateData['tag_ids']) ? ($updateData['tag_ids'] ?? []) : $oldTagIds;
                    
                    $product->update($updateData);
                    $product->refresh();

                    // Cập nhật usage_count cho tags nếu tag_ids thay đổi
                    if (isset($updateData['tag_ids'])) {
                        $tagService = app(\App\Services\TagService::class);
                        $tagService->updateUsageCountForTags($oldTagIds, $newTagIds);
                    }

                    // Xóa cache với slug cũ
                    Cache::forget('product_detail_'.$oldSlug);
                    Cache::forget('slug_type_'.$oldSlug);

                    // Nếu slug thay đổi, cũng xóa cache với slug mới
                    $newSlug = $product->slug;
                    if ($newSlug !== $oldSlug) {
                        Cache::forget('product_detail_'.$newSlug);
                        Cache::forget('slug_type_'.$newSlug);
                    } elseif (isset($updateData['is_active']) && $oldIsActive !== $product->is_active) {
                        // Nếu is_active thay đổi, invalidate slug_type cache
                        Cache::forget('slug_type_'.$newSlug);
                    }
                }
                // Nếu không có thay đổi → giữ nguyên cache
                } else {
                    // Create: tạo mới với SKU
                    // Đảm bảo có đủ dữ liệu tối thiểu (đã check ở trên)
                    $data['sku'] = $sku;
                    
                    $newProduct = Product::create($data);

                    // Cập nhật usage_count cho tags (tăng cho tags mới)
                    $newTagIds = $data['tag_ids'] ?? [];
                    if (!empty($newTagIds)) {
                        $tagService = app(\App\Services\TagService::class);
                        $tagService->updateUsageCountForTags([], $newTagIds);
                    }

                    // Xóa cache với slug mới (tạo mới luôn cần xóa cache)
                    Cache::forget('product_detail_'.$newProduct->slug);
                    Cache::forget('slug_type_'.$newProduct->slug);
                }
                
            } catch (\Exception $e) {
                Log::error('❌ [IMPORT PRODUCTS] Lỗi khi xử lý sản phẩm', [
                    'sku' => $sku,
                    'row_index' => $rowIndex + 2,
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]);
                
                $errors[] = [
                    'type' => 'PRODUCT_IMPORT_ERROR',
                    'sku' => $sku,
                    'message' => $e->getMessage(),
                    'row' => $rowIndex + 2,
                    'sheet' => $sheetTitle,
                    'file' => basename($e->getFile()),
                    'line' => $e->getLine(),
                ];
            }
        }
    }

    /**
     * Import Images
     */
    private function importImages($spreadsheet, &$errors)
    {
        $sheet = $this->findSheetByAliases($spreadsheet, ProductWorkbookSchema::imageSheetAliases());
        if (! $sheet) {
            return;
        } // Sheet tùy chọn

        [, $headerIndex, $rows] = $this->extractSheetDataWithExactHeaders(
            $sheet,
            ProductWorkbookSchema::imageHeaders(),
            ProductWorkbookSchema::SHEET_IMAGES
        );
        $sheetTitle = $sheet->getTitle();

        $imageMap = []; // image_key => image_id
        $productImageMap = []; // product_id => [image_id1, image_id2, ...]
        $productCache = [];

        foreach ($rows as $rowIndex => $row) {
            if (empty($row[0]) && empty($row[1])) {
                continue;
            }

            // Check if first column is SKU or image_key (backward compatibility)
            $sku = null;
            $imageKey = null;
            $url = null;
            $title = null;
            $notes = null;
            $alt = null;
            $isPrimary = false;
            $order = 0;

            if ($this->hasHeader($headerIndex, ['image_key'])) {
                $sku = $this->getTrimmedRowValueByHeader($row, $headerIndex, ['sku'], '');
                $imageKey = $this->getTrimmedRowValueByHeader($row, $headerIndex, ['image_key'], '');
                $url = $this->getTrimmedRowValueByHeader($row, $headerIndex, ['url', 'local_path'], '');
                $title = $this->getTrimmedRowValueByHeader($row, $headerIndex, ['title'], '');
                $notes = $this->getTrimmedRowValueByHeader($row, $headerIndex, ['notes'], '');
                $alt = $this->getTrimmedRowValueByHeader($row, $headerIndex, ['alt'], '');
                $isPrimary = $this->getBooleanRowValueByHeader($row, $headerIndex, ['is_primary'], false);
                $order = (int) $this->getRowValueByHeader($row, $headerIndex, ['order'], 0);
            } else {
                // Detect format: if first column looks like SKU (not starting with IMG), it's new format
                $firstCol = trim($row[0] ?? '');
                if (! empty($firstCol) && ! preg_match('/^IMG\d+$/i', $firstCol)) {
                    // New format: sku, image_key, url, title, notes, alt, is_primary, order
                    $sku = $firstCol;
                    $imageKey = trim($row[1] ?? '');
                    $url = trim($row[2] ?? '');
                    $title = trim($row[3] ?? '');
                    $notes = trim($row[4] ?? '');
                    $alt = trim($row[5] ?? '');
                    $isPrimary = isset($row[6]) ? (bool) $row[6] : false;
                    $order = (int) ($row[7] ?? 0);
                } else {
                    // Old format: image_key, url, title, notes, alt, is_primary, order (no SKU)
                    $imageKey = $firstCol;
                    $url = trim($row[1] ?? '');
                    $title = trim($row[2] ?? '');
                    $notes = trim($row[3] ?? '');
                    $alt = trim($row[4] ?? '');
                    $isPrimary = isset($row[5]) ? (bool) $row[5] : false;
                    $order = (int) ($row[6] ?? 0);
                }
            }

            if (empty($imageKey) || empty($url)) {
                continue;
            }

            $product = null;
            if (! empty($sku)) {
                if (! isset($productCache[$sku])) {
                    $productCache[$sku] = Product::where('sku', $sku)->first();
                }

                $product = $productCache[$sku];

                if (! $product) {
                    $errors[] = [
                        'type' => 'PRODUCT_NOT_FOUND',
                        'sku' => $sku,
                        'message' => "Không tìm thấy sản phẩm với SKU '{$sku}' trong sheet images. Đã bỏ qua ảnh này.",
                        'row' => $rowIndex + 2,
                        'sheet' => $sheetTitle,
                    ];

                    continue;
                }
            }

            // Extract image ID from image_key (IMG123 -> 123)
            $imageId = null;
            if (preg_match('/^IMG(\d+)$/i', $imageKey, $matches)) {
                $imageId = (int) $matches[1];
            }

            $normalizedLookupUrl = $this->normalizeImageColumnValue('url', basename($url));
            $image = $imageId ? Image::find($imageId) : null;

            if (! $image && $product) {
                $image = Image::query()
                    ->where('product_id', $product->id)
                    ->where('url', $normalizedLookupUrl)
                    ->latest('id')
                    ->first();
            }

            $payload = $this->buildImportedImagePayload($product, $url, $title, $notes, $alt, $isPrimary, $order);
            $payload = $this->syncImportedImageAsset($image, $url, $payload, $errors, [
                'sku' => $sku ?: ($product?->sku ?? 'N/A'),
                'row' => $rowIndex + 2,
                'sheet' => $sheetTitle,
            ]);

            if ($image) {
                $image->update($payload);
            } else {
                $image = Image::create($payload);
            }

            $imageMap[$imageKey] = $image->id;

            if ($product) {
                if (! isset($productImageMap[$product->id])) {
                    $productImageMap[$product->id] = [];
                }
                $productImageMap[$product->id][] = $image->id;
            }
        }

        foreach ($productImageMap as $productId => $imageIds) {
            $product = Product::find($productId);
            if (! $product) {
                continue;
            }

            $this->syncImportedProductImages($product, $imageIds);
        }

        // Fallback cho file cũ: chỉ dùng nếu sheet products còn cột image_ids legacy.
        if (empty($productImageMap)) {
            $productSheet = $this->findSheetByAliases($spreadsheet, ProductWorkbookSchema::productSheetAliases());
            if ($productSheet) {
                $productRows = $productSheet->toArray();
                $productHeaders = $productRows[0] ?? [];
                $productHeaderIndex = $this->buildHeaderIndex($productHeaders);

                if (! $this->hasHeader($productHeaderIndex, ['image_ids'])) {
                    return;
                }

                array_shift($productRows);

                foreach ($productRows as $row) {
                    $sku = $this->getTrimmedRowValueByHeader($row, $productHeaderIndex, ['sku'], trim((string) ($row[0] ?? '')));
                    $imageIdsRaw = $this->getTrimmedRowValueByHeader($row, $productHeaderIndex, ['image_ids'], '');

                    if ($sku === '' || $imageIdsRaw === '') {
                        continue;
                    }

                    $product = Product::where('sku', $sku)->first();
                    if (! $product) {
                        continue;
                    }

                    $imageIds = [];
                    foreach (array_map('trim', explode(',', $imageIdsRaw)) as $imageKey) {
                        if ($imageKey === '') {
                            continue;
                        }

                        if (isset($imageMap[$imageKey])) {
                            $imageIds[] = $imageMap[$imageKey];
                        } elseif (preg_match('/^IMG(\d+)$/i', $imageKey, $matches)) {
                            $imageIds[] = (int) $matches[1];
                        }
                    }

                    $this->syncImportedProductImages($product, $imageIds);
                }
            }
        }
    }

    /**
     * Import FAQs
     */
    private function importFaqs($spreadsheet, &$errors)
    {
        $sheet = $this->findSheetByAliases($spreadsheet, ProductWorkbookSchema::faqSheetAliases());
        if (! $sheet) {
            return;
        } // Sheet tùy chọn

        [, $headerIndex, $rows] = $this->extractSheetDataWithExactHeaders(
            $sheet,
            ProductWorkbookSchema::faqHeaders(),
            ProductWorkbookSchema::SHEET_FAQS
        );
        $sheetTitle = $sheet->getTitle();

        foreach ($rows as $rowIndex => $row) {
            $sku = $this->getTrimmedRowValueByHeader($row, $headerIndex, ['sku'], '');
            if ($sku === '') {
                continue;
            }

            $question = $this->getTrimmedRowValueByHeader($row, $headerIndex, ['question'], '');
            $answer = $this->getTrimmedRowValueByHeader($row, $headerIndex, ['answer'], '');
            $order = (int) $this->getRowValueByHeader($row, $headerIndex, ['order'], 0);

            if (empty($sku) || empty($question)) {
                continue;
            }

            $product = Product::where('sku', $sku)->first();
            if (! $product) {
                $errors[] = [
                    'type' => 'PRODUCT_NOT_FOUND',
                    'sku' => $sku,
                    'message' => "Không tìm thấy sản phẩm với SKU '{$sku}'. Đã bỏ qua FAQ này.",
                    'row' => $rowIndex + 2,
                    'sheet' => $sheetTitle,
                ];

                continue;
            }

            // Kiểm tra xem FAQ đã tồn tại chưa
            $existingFaq = ProductFaq::where('product_id', $product->id)
                ->where('question', $question)
                ->first();

            $wasCreated = ! $existingFaq;
            $wasChanged = false;

            if ($existingFaq) {
                // So sánh dữ liệu cũ và mới
                $oldAnswer = $existingFaq->answer;
                $oldOrder = $existingFaq->order;
                if ($oldAnswer != $answer || $oldOrder != $order) {
                    $wasChanged = true;
                }
            }

            // Update or create FAQ
            ProductFaq::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'question' => $question,
                ],
                [
                    'answer' => $answer ?: null,
                    'order' => $order,
                ]
            );

            // Nếu FAQ được tạo mới hoặc thay đổi → xóa cache
            if ($wasCreated || $wasChanged) {
                Cache::forget('product_detail_'.$product->slug);
                Cache::forget('slug_type_'.$product->slug);
            }
        }
    }

    /**
     * Import Variants
     */
    private function importVariants($spreadsheet, array &$errors): void
    {
        $sheet = $this->findSheetByAliases($spreadsheet, ProductWorkbookSchema::variantSheetAliases());
        if (! $sheet) {
            // Không có sheet variants thì bỏ qua (giữ logic cũ)
            return;
        }

        [, $headerIndex, $rows] = $this->extractSheetDataWithExactHeaders(
            $sheet,
            ProductWorkbookSchema::variantHeaders(),
            ProductWorkbookSchema::SHEET_VARIANTS
        );
        $sheetTitle = $sheet->getTitle();
        $imageKeyMap = $this->buildImportedImageKeyMap($spreadsheet);

        $requiredCols = ['sku', 'price'];
        foreach ($requiredCols as $col) {
            if (! $this->hasHeader($headerIndex, [$col, $col === 'sku' ? 'product_sku' : $col])) {
                throw new \Exception("Sheet \"variants\" thiếu cột bắt buộc: {$col}");
            }
        }

        $processed = []; // product_id => [variant_ids_kept]

        foreach ($rows as $rowIndex => $row) {
            $rowNumber = $rowIndex + 2; // +2 vì header ở dòng 1

            $productSku = $this->getTrimmedRowValueByHeader($row, $headerIndex, ['product_sku', 'sku'], '');
            $variantName = $this->getTrimmedRowValueByHeader($row, $headerIndex, ['variant_name'], '');
            $variantSku = $this->getTrimmedRowValueByHeader($row, $headerIndex, ['variant_sku'], '');
            $price = (float) $this->getRowValueByHeader($row, $headerIndex, ['price'], 0);
            $salePrice = $this->getRowValueByHeader($row, $headerIndex, ['sale_price'], null);
            $stockQuantity = $this->getRowValueByHeader($row, $headerIndex, ['stock_quantity'], null);
            $imageId = $this->getRowValueByHeader($row, $headerIndex, ['image_id', 'image_key'], null);
            $attributesJson = $this->getRowValueByHeader($row, $headerIndex, ['attributes_json'], null);
            $isActive = $this->getRowValueByHeader($row, $headerIndex, ['is_active'], 1);

            if ($variantName === '') {
                $variantName = $this->buildVariantNameFromAttributes([
                    'color' => $this->getTrimmedRowValueByHeader($row, $headerIndex, ['attributes_color'], ''),
                    'size' => $this->getTrimmedRowValueByHeader($row, $headerIndex, ['attributes_size'], ''),
                ], $variantSku ?: null);
            }

            if (empty($productSku) || empty($variantName) || $price <= 0) {
                continue; // Bỏ qua dòng không hợp lệ
            }

            $product = Product::where('sku', $productSku)->first();
            if (! $product) {
                $errors[] = [
                    'type' => 'PRODUCT_NOT_FOUND',
                    'sku' => $productSku,
                    'message' => "Không tìm thấy sản phẩm với SKU '{$productSku}' khi import biến thể.",
                    'row' => $rowNumber,
                    'sheet' => $sheetTitle,
                ];

                continue;
            }

            // Parse attributes JSON
            $attributes = null;
            if (! empty($attributesJson)) {
                $decoded = json_decode($attributesJson, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $attributes = $decoded;
                } else {
                    $errors[] = [
                        'type' => 'INVALID_ATTRIBUTES_JSON',
                        'sku' => $productSku,
                        'message' => "JSON attributes không hợp lệ tại dòng {$rowNumber}: {$attributesJson}",
                        'row' => $rowNumber,
                        'sheet' => $sheetTitle,
                    ];
                }
            }

            if ($attributes === null) {
                $attributes = array_filter([
                    'color' => $this->getTrimmedRowValueByHeader($row, $headerIndex, ['attributes_color'], ''),
                    'size' => $this->getTrimmedRowValueByHeader($row, $headerIndex, ['attributes_size'], ''),
                ], fn ($value) => $value !== '');
            }

            // Lấy variant theo sku nếu có, nếu không dùng name
            $variantQuery = ProductVariant::where('product_id', $product->id);
            if (! empty($variantSku)) {
                $variantQuery->where('sku', $variantSku);
            } else {
                $variantQuery->where('name', $variantName);
            }
            $variant = $variantQuery->first();

            // Chuẩn bị data
            $variantData = [
                'name' => $variantName,
                'sku' => $variantSku ?: null,
                'price' => (float) $price,
                'sale_price' => $salePrice !== null && $salePrice !== '' ? (float) $salePrice : null,
                'stock_quantity' => $stockQuantity !== null && $stockQuantity !== '' ? (int) $stockQuantity : null,
                'image_id' => is_numeric($imageId)
                    ? (int) $imageId
                    : ($imageKeyMap[(string) $imageId] ?? null),
                'attributes' => $attributes,
                'is_active' => (bool) $isActive,
            ];

            if ($variant) {
                $variant->update($variantData);
                $variantId = $variant->id;
            } else {
                $variantId = ProductVariant::create(array_merge($variantData, [
                    'product_id' => $product->id,
                ]))->id;
            }

            // Ghi nhận variant đã xử lý
            if (! isset($processed[$product->id])) {
                $processed[$product->id] = [];
            }
            $processed[$product->id][] = $variantId;

            if (! $product->has_variants) {
                $product->update(['has_variants' => true]);
            }

            // Clear cache product
            Cache::forget('product_detail_'.$product->slug);
            Cache::forget('slug_type_'.$product->slug);
        }

        // Xóa các biến thể không có trong file cho từng sản phẩm đã xử lý
        foreach ($processed as $productId => $keepIds) {
            ProductVariant::where('product_id', $productId)
                ->whereNotIn('id', $keepIds)
                ->delete();

            // Xóa cache sản phẩm
            $product = Product::find($productId);
            if ($product) {
                Cache::forget('product_detail_'.$product->slug);
                Cache::forget('slug_type_'.$product->slug);
            }
        }
    }

    /**
     * Import How-Tos
     */
    private function importHowTos($spreadsheet, &$errors)
    {
        $sheet = $this->findSheetByAliases($spreadsheet, ProductWorkbookSchema::howToSheetAliases());
        if (! $sheet) {
            return;
        } // Sheet tùy chọn

        [, $headerIndex, $rows] = $this->extractSheetDataWithExactHeaders(
            $sheet,
            ProductWorkbookSchema::howToHeaders(),
            ProductWorkbookSchema::SHEET_HOW_TOS
        );
        $sheetTitle = $sheet->getTitle();

        foreach ($rows as $rowIndex => $row) {
            $sku = $this->getTrimmedRowValueByHeader($row, $headerIndex, ['sku'], '');
            if ($sku === '') {
                continue;
            }

            $title = $this->getTrimmedRowValueByHeader($row, $headerIndex, ['title'], '');
            $description = $this->getTrimmedRowValueByHeader($row, $headerIndex, ['description'], '');
            $stepsRaw = $this->getTrimmedRowValueByHeader($row, $headerIndex, ['steps'], '');
            $suppliesRaw = $this->getTrimmedRowValueByHeader($row, $headerIndex, ['supplies'], '');
            $isActive = $this->getBooleanRowValueByHeader($row, $headerIndex, ['is_active'], true);

            if (empty($sku) || empty($title)) {
                continue;
            }

            $product = Product::where('sku', $sku)->first();
            if (! $product) {
                $errors[] = [
                    'type' => 'PRODUCT_NOT_FOUND',
                    'sku' => $sku,
                    'message' => "Không tìm thấy sản phẩm với SKU '{$sku}'. Đã bỏ qua How-To này.",
                    'row' => $rowIndex + 2,
                    'sheet' => $sheetTitle,
                ];

                continue;
            }

            // Xử lý steps và supplies (JSON)
            $steps = null;
            if (! empty($stepsRaw)) {
                $decoded = json_decode($stepsRaw, true);
                $steps = $decoded ?: array_filter(array_map('trim', explode("\n", $stepsRaw)));
            }

            $supplies = null;
            if (! empty($suppliesRaw)) {
                $decoded = json_decode($suppliesRaw, true);
                $supplies = $decoded ?: array_filter(array_map('trim', explode(',', $suppliesRaw)));
            }

            // Kiểm tra xem How-To đã tồn tại chưa
            $existingHowTo = ProductHowTo::where('product_id', $product->id)
                ->where('title', $title)
                ->first();

            $wasCreated = ! $existingHowTo;
            $wasChanged = false;

            if ($existingHowTo) {
                // So sánh dữ liệu cũ và mới
                $oldDescription = $existingHowTo->description;
                $oldSteps = $existingHowTo->steps ?? [];
                $oldSupplies = $existingHowTo->supplies ?? [];
                $oldIsActive = $existingHowTo->is_active;

                $oldStepsArray = is_array($oldSteps) ? $oldSteps : [];
                $newStepsArray = is_array($steps) ? $steps : [];
                sort($oldStepsArray);
                sort($newStepsArray);

                $oldSuppliesArray = is_array($oldSupplies) ? $oldSupplies : [];
                $newSuppliesArray = is_array($supplies) ? $supplies : [];
                sort($oldSuppliesArray);
                sort($newSuppliesArray);

                if ($oldDescription != $description ||
                    $oldStepsArray !== $newStepsArray ||
                    $oldSuppliesArray !== $newSuppliesArray ||
                    $oldIsActive != $isActive) {
                    $wasChanged = true;
                }
            }

            // Update or create How-To
            ProductHowTo::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'title' => $title,
                ],
                [
                    'description' => $description ?: null,
                    'steps' => $steps,
                    'supplies' => $supplies,
                    'is_active' => $isActive,
                ]
            );

            // Nếu How-To được tạo mới hoặc thay đổi → xóa cache
            if ($wasCreated || $wasChanged) {
                Cache::forget('product_detail_'.$product->slug);
                Cache::forget('slug_type_'.$product->slug);
            }
        }
    }

    /**
     * Ghi log lỗi vào file txt
     */
    private function writeErrorLog($errors, $originalFileName)
    {
        if (empty($errors)) {
            return null;
        }

        $logDir = storage_path('logs/imports');
        if (! is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $timestamp = date('Y-m-d_H-i-s');
        $baseName = pathinfo($originalFileName, PATHINFO_FILENAME);
        $logFileName = "import_errors_{$baseName}_{$timestamp}.txt";
        $logPath = $logDir.'/'.$logFileName;

        $content = "========================================\n";
        $content .= "LOG LỖI IMPORT EXCEL\n";
        $content .= "========================================\n";
        $content .= "File Excel: {$originalFileName}\n";
        $content .= 'Thời gian: '.date('Y-m-d H:i:s')."\n";
        $content .= 'Tổng số lỗi: '.count($errors)."\n";
        $content .= "========================================\n\n";

        foreach ($errors as $index => $error) {
            $content .= '['.($index + 1).'] '.($error['type'] ?? 'UNKNOWN')."\n";
            $content .= 'Sheet: '.($error['sheet'] ?? 'N/A').' | ';
            $content .= 'Dòng: '.($error['row'] ?? 'N/A').' | ';
            $content .= 'SKU: '.($error['sku'] ?? 'N/A')."\n";
            $content .= 'Mô tả: '.($error['message'] ?? 'Không có mô tả')."\n";
            $content .= "\n";
        }

        file_put_contents($logPath, $content);

        return $logFileName;
    }

    /**
     * Xóa cache cho tất cả sản phẩm (product_detail_*, slug_type_*, related_products_*, vouchers_for_product_*)
     * để đảm bảo dữ liệu luôn mới sau mỗi lần import Excel.
     */
    private function clearAllProductCaches(): void
    {
        Product::query()
            ->select('id', 'slug')
            ->chunkById(200, function ($products): void {
                foreach ($products as $product) {
                    Cache::forget('product_detail_'.$product->slug);
                    Cache::forget('slug_type_'.$product->slug);
                    Cache::forget('related_products_'.$product->id);
                    Cache::forget('vouchers_for_product_'.$product->id);
                }
            });
    }

    // ============================================
    // API METHODS CHO EXPORT/IMPORT VỚI FILTER
    // ============================================

    /**
     * Bắt đầu export sản phẩm theo filter (API)
     */
    public function startExportWithFilter(Request $request): JsonResponse
    {
        $request->validate([
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'integer|exists:categories,id',
            'brand_ids' => 'nullable|array',
            'brand_ids.*' => 'integer|exists:brands,id',
        ]);

        try {
            // Log để debug
            Log::info('Export with filter request', [
                'category_ids' => $request->input('category_ids'),
                'brand_ids' => $request->input('brand_ids'),
                'category_ids_count' => count($request->input('category_ids', [])),
                'brand_ids_count' => count($request->input('brand_ids', [])),
            ]);

            // Đếm tổng số sản phẩm cần export
            $query = $this->buildFilterQuery($request);
            $totalProducts = $query->count();

            // Giới hạn tối đa 100k sản phẩm
            $maxAllowed = 100000;
            if ($totalProducts > $maxAllowed) {
                return response()->json([
                    'success' => false,
                    'message' => "Hiện tại chỉ cho phép xuất tối đa {$maxAllowed} sản phẩm. Vui lòng thu hẹp bộ lọc (hiện có {$totalProducts} sản phẩm).",
                ], 400);
            }

            if ($totalProducts === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không có sản phẩm nào phù hợp với bộ lọc.',
                ], 400);
            }

            // Nếu > 20k sản phẩm, dùng Job export khi class job có sẵn.
            $useJobExport = $totalProducts > 20000 && class_exists(ExportProductsJob::class);

            if ($totalProducts > 20000 && ! class_exists(ExportProductsJob::class)) {
                Log::warning('ExportProductsJob is missing. Falling back to chunk export flow.', [
                    'total_products' => $totalProducts,
                ]);
            }
            
            if ($useJobExport) {
                // Dùng Job export cho dataset lớn
                $sessionId = 'export_'.time().'_'.uniqid();
                $fileName = "products_export_{$sessionId}.xlsx";
                $filePath = storage_path("app/exports/{$fileName}");

                // Lưu thông tin export vào cache
                Cache::put("export_{$sessionId}", [
                    'category_ids' => $request->input('category_ids', []),
                    'brand_ids' => $request->input('brand_ids', []),
                    'total_products' => $totalProducts,
                    'processed' => 0,
                    'status' => 'queued',
                    'file_path' => $filePath,
                    'created_at' => now()->toDateTimeString(),
                ], now()->addHours(2));

                // Dispatch Job để xử lý export nền
                ExportProductsJob::dispatch($sessionId, $request->input('category_ids', []), $request->input('brand_ids', []), $totalProducts);

                return response()->json([
                    'success' => true,
                    'session_id' => $sessionId,
                    'total_products' => $totalProducts,
                    'message' => 'Đang bắt đầu xuất sản phẩm trong nền (Job export)...',
                ]);
            }

            // Dùng chunk-based export cho dataset nhỏ (< 20k)
            $sessionId = 'export_'.time().'_'.uniqid();

            // Lưu thông tin export vào cache (expire sau 1 giờ)
            Cache::put("export_{$sessionId}", [
                'category_ids' => $request->input('category_ids', []),
                'brand_ids' => $request->input('brand_ids', []),
                'total_products' => $totalProducts,
                'processed' => 0,
                'status' => 'processing',
                'created_at' => now()->toDateTimeString(),
            ], now()->addHour());

            return response()->json([
                'success' => true,
                'session_id' => $sessionId,
                'total_products' => $totalProducts,
                'message' => 'Bắt đầu xuất sản phẩm...',
            ]);

        } catch (\Exception $e) {
            Log::error('Export start error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi bắt đầu xuất: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Xử lý export chunk (được gọi nhiều lần)
     */
    public function processExportChunk(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|string',
            'chunk' => 'required|integer|min:0',
            'chunk_size' => 'required|integer|min:1|max:500',
        ]);

        $sessionId = $request->input('session_id');
        $chunk = (int) $request->input('chunk');
        $chunkSize = (int) $request->input('chunk_size', 100);

        $cacheKey = "export_{$sessionId}";
        $exportData = Cache::get($cacheKey);

        if (! $exportData) {
            return response()->json([
                'success' => false,
                'message' => 'Session không tồn tại hoặc đã hết hạn.',
            ], 404);
        }

        if ($exportData['status'] === 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'Export đã bị hủy.',
                'cancelled' => true,
            ], 400);
        }

        try {
            // Build query với filter
            $request->merge([
                'category_ids' => $exportData['category_ids'] ?? [],
                'brand_ids' => $exportData['brand_ids'] ?? [],
            ]);
            $query = $this->buildFilterQuery($request);

            // Kiểm tra xem chunk này đã được xử lý chưa (tránh xử lý trùng)
            $chunkFile = storage_path("app/exports/{$sessionId}_chunk_{$chunk}.json");
            if (file_exists($chunkFile)) {
                // Chunk đã được xử lý, chỉ cập nhật progress
                $processed = $exportData['processed'] ?? 0;
                $progress = $exportData['total_products'] > 0
                    ? ($processed / $exportData['total_products']) * 100
                    : 0;

                return response()->json([
                    'success' => true,
                    'processed' => $processed,
                    'total' => $exportData['total_products'],
                    'progress' => round($progress, 2),
                    'completed' => false,
                    'message' => 'Chunk đã được xử lý',
                ]);
            }

            // Lấy chunk sản phẩm
            $products = $query->skip($chunk * $chunkSize)
                ->take($chunkSize)
                ->with([
                    'primaryCategory',
                    'brand',
                    'faqs',
                    'howTos',
                    'variants',
                ])
                ->get();

            if ($products->isEmpty()) {
                // Không còn sản phẩm nào, kiểm tra xem đã xử lý hết chưa
                $totalProcessed = $exportData['processed'] ?? 0;
                
                // Nếu đã xử lý đủ số lượng, finalize
                if ($totalProcessed >= $exportData['total_products']) {
                    // Đảm bảo finalize chỉ được gọi 1 lần
                    if ($exportData['status'] !== 'finalizing' && $exportData['status'] !== 'completed') {
                        Cache::put($cacheKey, array_merge($exportData, [
                            'status' => 'finalizing',
                        ]), now()->addHour());
                        
                        // Finalize ngay lập tức (không async)
                        try {
                            $this->finalizeExportWithFilter($sessionId, $exportData);
                            
                            // Kiểm tra file đã được tạo chưa
                            $filePath = storage_path("app/exports/{$sessionId}.xlsx");
                            if (!file_exists($filePath)) {
                                throw new \Exception('File export chưa được tạo.');
                            }
                        } catch (\Exception $e) {
                            Log::error('Finalize export error', [
                                'session_id' => $sessionId,
                                'error' => $e->getMessage(),
                                'trace' => $e->getTraceAsString(),
                            ]);
                            Cache::put($cacheKey, array_merge($exportData, [
                                'status' => 'error',
                                'error' => $e->getMessage(),
                            ]), now()->addHours(2));
                            
                            // Trả JSON error thay vì throw exception
                            return response()->json([
                                'success' => false,
                                'completed' => false,
                                'processed' => $totalProcessed,
                                'total' => $exportData['total_products'],
                                'error' => $e->getMessage(),
                                'message' => 'Lỗi khi tạo file export: ' . $e->getMessage(),
                            ], 500);
                        }
                    }
                    
                    // Kiểm tra lại file đã tồn tại chưa
                    $filePath = storage_path("app/exports/{$sessionId}.xlsx");
                    if (file_exists($filePath)) {
                        return response()->json([
                            'success' => true,
                            'completed' => true,
                            'processed' => $totalProcessed,
                            'total' => $exportData['total_products'],
                            'file_url' => $this->getExportFileUrl($sessionId),
                        ]);
                    } else {
                        // File chưa sẵn sàng, trả về đang xử lý
                        return response()->json([
                            'success' => true,
                            'processed' => $totalProcessed,
                            'total' => $exportData['total_products'],
                            'progress' => 99,
                            'completed' => false,
                            'message' => 'Đang tạo file Excel...',
                        ]);
                    }
                }

                // Chưa đủ, tiếp tục
                return response()->json([
                    'success' => true,
                    'processed' => $totalProcessed,
                    'total' => $exportData['total_products'],
                    'progress' => round(($totalProcessed / $exportData['total_products']) * 100, 2),
                    'completed' => false,
                ]);
            }

            // Lưu product IDs vào file tạm (chỉ lưu IDs để tiết kiệm bộ nhớ)
            $this->saveExportChunk($sessionId, $chunk, $products->pluck('id')->toArray());

            // Cập nhật progress
            $processed = ($exportData['processed'] ?? 0) + $products->count();
            Cache::put($cacheKey, array_merge($exportData, [
                'processed' => $processed,
                'last_chunk' => $chunk,
            ]), now()->addHour());

            $progress = ($processed / $exportData['total_products']) * 100;

            // Kiểm tra xem đã xử lý hết chưa
            if ($processed >= $exportData['total_products']) {
                // Đảm bảo finalize chỉ được gọi 1 lần
                if ($exportData['status'] !== 'finalizing' && $exportData['status'] !== 'completed') {
                    Cache::put($cacheKey, array_merge($exportData, [
                        'status' => 'finalizing',
                        'processed' => $processed,
                    ]), now()->addHour());
                    
                    // Finalize ngay lập tức (không async)
                    try {
                        $this->finalizeExportWithFilter($sessionId, array_merge($exportData, ['processed' => $processed]));
                        
                        // Kiểm tra file đã được tạo chưa
                        $filePath = storage_path("app/exports/{$sessionId}.xlsx");
                        if (!file_exists($filePath)) {
                            throw new \Exception('File export chưa được tạo.');
                        }
                    } catch (\Exception $e) {
                        Log::error('Finalize export error', [
                            'session_id' => $sessionId,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                        Cache::put($cacheKey, array_merge($exportData, [
                            'status' => 'error',
                            'error' => $e->getMessage(),
                            'processed' => $processed,
                        ]), now()->addHours(2));
                        
                        // Trả JSON error thay vì throw exception
                        return response()->json([
                            'success' => false,
                            'completed' => false,
                            'processed' => $processed,
                            'total' => $exportData['total_products'],
                            'error' => $e->getMessage(),
                            'message' => 'Lỗi khi tạo file export: ' . $e->getMessage(),
                        ], 500);
                    }
                }
                
                // Kiểm tra lại file đã tồn tại chưa
                $filePath = storage_path("app/exports/{$sessionId}.xlsx");
                if (file_exists($filePath)) {
                    return response()->json([
                        'success' => true,
                        'completed' => true,
                        'processed' => $processed,
                        'total' => $exportData['total_products'],
                        'file_url' => $this->getExportFileUrl($sessionId),
                    ]);
                } else {
                    // File chưa sẵn sàng, trả về đang xử lý
                    return response()->json([
                        'success' => true,
                        'processed' => $processed,
                        'total' => $exportData['total_products'],
                        'progress' => 99,
                        'completed' => false,
                        'message' => 'Đang tạo file Excel...',
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'processed' => $processed,
                'total' => $exportData['total_products'],
                'progress' => round($progress, 2),
                'completed' => false,
            ]);

        } catch (\Exception $e) {
            Log::error('Export chunk error', [
                'session_id' => $sessionId,
                'chunk' => $chunk,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            Cache::put($cacheKey, array_merge($exportData, [
                'status' => 'error',
                'error' => $e->getMessage(),
            ]), now()->addHour());

            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xử lý chunk: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Hủy export
     */
    public function cancelExport(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|string',
        ]);

        $sessionId = $request->input('session_id');
        $cacheKey = "export_{$sessionId}";
        $exportData = Cache::get($cacheKey);

        if ($exportData) {
            Cache::put($cacheKey, array_merge($exportData, [
                'status' => 'cancelled',
            ]), now()->addHour());

            // Xóa file tạm nếu có
            $this->cleanupExportFiles($sessionId);
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã hủy xuất sản phẩm.',
        ]);
    }

    /**
     * Lấy progress của export (hỗ trợ cả Job export và chunk export)
     */
    public function getExportProgress(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|string',
        ]);

        $sessionId = $request->input('session_id');
        $cacheKey = "export_{$sessionId}";
        $exportData = Cache::get($cacheKey);

        if (! $exportData) {
            return response()->json([
                'success' => false,
                'message' => 'Session không tồn tại.',
            ], 404);
        }

        // Tính progress từ processed và total_products
        $processed = $exportData['processed'] ?? 0;
        $total = $exportData['total_products'] ?? 0;
        
        // Nếu có progress trong cache thì dùng, không thì tính lại
        $progress = $exportData['progress'] ?? ($total > 0 ? ($processed / $total) * 100 : 0);

        $status = $exportData['status'] ?? 'processing';
        $isCompleted = $status === 'completed';
        $isCancelled = $status === 'cancelled';

        // Kiểm tra file đã tồn tại chưa (cho Job export)
        $filePath = storage_path("app/exports/{$sessionId}.xlsx");
        if ($isCompleted && !file_exists($filePath)) {
            // File chưa sẵn sàng, đánh dấu là đang finalizing
            $status = 'finalizing';
            $isCompleted = false;
        }

        return response()->json([
            'success' => true,
            'processed' => $processed,
            'total' => $total,
            'progress' => round($progress, 2),
            'status' => $status,
            'completed' => $isCompleted,
            'cancelled' => $isCancelled,
            'file_url' => $isCompleted ? $this->getExportFileUrl($sessionId) : null,
            'error' => $exportData['error'] ?? null,
        ]);
    }

    /**
     * Download file export
     */
    public function downloadExport(Request $request, string $sessionId)
    {
        $filePath = storage_path("app/exports/{$sessionId}.xlsx");

        if (! file_exists($filePath)) {
            Log::warning('Export file not found', [
                'session_id' => $sessionId,
                'file_path' => $filePath,
            ]);
            
            // Kiểm tra xem có đang finalize không
            $cacheKey = "export_{$sessionId}";
            $exportData = Cache::get($cacheKey);
            
            // Nếu là AJAX request, luôn trả JSON
            if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                if ($exportData) {
                    $status = $exportData['status'] ?? 'processing';
                    if ($status === 'finalizing' || $status === 'processing' || $status === 'queued') {
                        return response()->json([
                            'success' => false,
                            'message' => 'File đang được tạo, vui lòng đợi thêm vài giây.',
                            'status' => $status,
                        ], 202); // 202 Accepted
                    }
                }
                
                return response()->json([
                    'success' => false,
                    'message' => 'File không tồn tại hoặc đã bị xóa. Vui lòng thử export lại.',
                    'status' => 'not_found',
                ], 404);
            }
            
            // Nếu không phải AJAX, trả HTML error page
            if ($exportData && ($exportData['status'] === 'finalizing' || $exportData['status'] === 'processing')) {
                abort(202, 'File đang được tạo, vui lòng đợi thêm vài giây.');
            }
            
            abort(404, 'File không tồn tại hoặc đã bị xóa.');
        }

        // Kiểm tra file size
        $fileSize = filesize($filePath);
        if ($fileSize === false || $fileSize === 0) {
            Log::warning('Export file is empty', [
                'session_id' => $sessionId,
                'file_path' => $filePath,
                'file_size' => $fileSize,
            ]);
            abort(500, 'File export rỗng hoặc không hợp lệ.');
        }

        $fileName = 'products_export_'.now()->format('Y-m-d_H-i-s').'.xlsx';

        // Nếu là AJAX request, trả JSON với download link
        if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'file_url' => $this->getExportFileUrl($sessionId),
                'file_size' => $fileSize,
                'message' => 'File đã sẵn sàng. Vui lòng click vào link để download.',
            ]);
        }

        // Nếu không phải AJAX, download trực tiếp
        // KHÔNG xóa file ngay (deleteFileAfterSend = false) để user có thể download lại
        return response()->download($filePath, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(false);
    }

    /**
     * Build query với filter (dùng cho export với filter)
     */
    protected function buildFilterQuery(Request $request)
    {
        $query = Product::query();

        // Filter theo category (sử dụng primary_category_id hoặc category_ids JSON)
        $categoryIds = $request->input('category_ids', []);
        if (is_array($categoryIds) && !empty($categoryIds)) {
            // Lọc bỏ các giá trị null, empty, và convert sang integer
            $categoryIds = array_filter(array_map('intval', $categoryIds), function($id) {
                return $id > 0;
            });
            
            if (!empty($categoryIds)) {
                $query->where(function ($q) use ($categoryIds) {
                    $q->whereIn('primary_category_id', $categoryIds);
                    // Xử lý JSON contains cho từng category ID
                    foreach ($categoryIds as $catId) {
                        $q->orWhereJsonContains('category_ids', $catId);
                    }
                });
            }
        }

        // Filter theo brand
        $brandIds = $request->input('brand_ids', []);
        if (is_array($brandIds) && !empty($brandIds)) {
            // Lọc bỏ các giá trị null, empty, và convert sang integer
            $brandIds = array_filter(array_map('intval', $brandIds), function($id) {
                return $id > 0;
            });
            
            if (!empty($brandIds)) {
                $query->whereIn('brand_id', $brandIds);
            }
        }

        // Log để debug
        Log::info('Build filter query', [
            'category_ids' => $categoryIds ?? [],
            'brand_ids' => $brandIds ?? [],
            'has_category_filter' => !empty($categoryIds),
            'has_brand_filter' => !empty($brandIds),
        ]);

        return $query->orderBy('id');
    }

    /**
     * Lưu chunk vào file tạm (chỉ lưu product IDs)
     */
    protected function saveExportChunk(string $sessionId, int $chunk, array $productIds)
    {
        $exportDir = storage_path('app/exports');
        if (! is_dir($exportDir)) {
            mkdir($exportDir, 0755, true);
        }

        $chunkFile = "{$exportDir}/{$sessionId}_chunk_{$chunk}.json";
        file_put_contents($chunkFile, json_encode($productIds, JSON_UNESCAPED_UNICODE));
    }

    /**
     * Hoàn thành export và merge tất cả chunks
     * SỬ DỤNG OPENSPOUT - STREAMING THẬT, KHÔNG OOM
     */
    protected function finalizeExportWithFilter(string $sessionId, array $exportData)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M'); // Tăng lên 1GB cho dataset lớn (48k products)

        if (! class_exists(Writer::class) || ! class_exists(Options::class) || ! class_exists(Row::class) || ! class_exists(Cell::class)) {
            throw new \RuntimeException('Không thể export nền vì thư viện OpenSpout chưa được cài đặt đầy đủ trên server.');
        }
        
        $cacheKey = "export_{$sessionId}";
        
        // Kiểm tra xem đã finalize chưa (tránh gọi nhiều lần)
        $currentData = Cache::get($cacheKey);
        if ($currentData && $currentData['status'] === 'completed') {
            Log::info('Export already finalized', ['session_id' => $sessionId]);
            return;
        }

        Log::info('Starting finalize export (OpenSpout)', [
            'session_id' => $sessionId,
            'total_products' => $exportData['total_products'] ?? 0,
        ]);

        // Đánh dấu đang finalize
        Cache::put($cacheKey, array_merge($exportData, [
            'status' => 'finalizing',
        ]), now()->addHours(2));

        $exportDir = storage_path('app/exports');
        $chunkFiles = glob("{$exportDir}/{$sessionId}_chunk_*.json");
        sort($chunkFiles);

        if (empty($chunkFiles)) {
            Log::warning('Export: No chunk files found', ['session_id' => $sessionId]);
            throw new \Exception('Không tìm thấy file chunks để xuất.');
        }

        // Đếm tổng số products (chỉ đếm, không load vào memory)
        $totalProducts = 0;
        foreach ($chunkFiles as $chunkFile) {
            if (!file_exists($chunkFile)) {
                continue;
            }
            $productIds = json_decode(file_get_contents($chunkFile), true);
            if (is_array($productIds)) {
                $totalProducts += count($productIds);
            }
            unset($productIds);
        }

        if ($totalProducts === 0) {
            Log::warning('Export: No product IDs in chunks', ['session_id' => $sessionId]);
            throw new \Exception('Không có sản phẩm nào trong các chunks.');
        }

        Log::info('Starting export (no IDs loaded)', [
            'session_id' => $sessionId,
            'total_products' => $totalProducts,
            'chunk_files_count' => count($chunkFiles),
        ]);

        // Load maps (nhỏ, không ảnh hưởng RAM)
        $categoryMap = Category::pluck('slug', 'id')->toArray();
        $brandMap = Brand::pluck('slug', 'id')->toArray();
        $tagMap = Tag::pluck('name', 'id')->toArray();

        // Map product_id => sku để dùng cho các sheet phụ - build từng chunk, không load hết
        $productIdToSku = [];

        $filePath = "{$exportDir}/{$sessionId}.xlsx";
        
        try {
            // Xóa file cũ nếu có
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
            
            Log::info('Creating export file with OpenSpout (streaming, no IDs loaded)', [
                'session_id' => $sessionId,
                'file_path' => $filePath,
                'total_products' => $totalProducts,
                'chunk_files_count' => count($chunkFiles),
            ]);
            
            // Tạo writer với OpenSpout - STREAMING THẬT
            $options = new Options();
            $writer = new Writer($options);
            $writer->openToFile($filePath);

            // =========================
            // Sheet 1: Products
            // =========================
            $productsSheet = $writer->getCurrentSheet();
            $productsSheet->setName('products');

            // Headers
            $headers = ProductWorkbookSchema::productHeaders();
            
            // Tạo header row
            $headerCells = array_map(fn($value) => Cell::fromValue($value), $headers);
            $headerRow = new Row($headerCells);
            $writer->addRow($headerRow);

            // Process products với OpenSpout - đọc từng chunk file và query trực tiếp
            // KHÔNG load tất cả IDs vào memory
            foreach ($chunkFiles as $chunkFile) {
                if (!file_exists($chunkFile)) {
                    continue;
                }
                
                // Đọc IDs từ chunk file (chỉ một chunk nhỏ)
                $productIds = json_decode(file_get_contents($chunkFile), true);
                if (!is_array($productIds) || empty($productIds)) {
                    unset($productIds);
                    continue;
                }
                
                // Query products từ chunk này - dùng chunkById để tránh OOM
                Product::whereIn('id', $productIds)
                    ->select([
                        'id', 'sku', 'name', 'slug', 'description', 'short_description',
                        'price', 'sale_price', 'cost_price', 'stock_quantity',
                        'meta_title', 'meta_description', 'meta_keywords',
                        'meta_canonical', 'primary_category_id', 'brand_id',
                        'category_ids', 'tag_ids',
                        'is_featured', 'has_variants', 'is_active', 'created_by',
                    ])
                    ->orderBy('id')
                    ->chunkById(100, function ($productsChunk) use ($writer, $categoryMap, $brandMap, $tagMap, &$productIdToSku) {
                    foreach ($productsChunk as $p) {
                        $rowValues = $this->buildProductExportRow($p, $categoryMap, $brandMap, $tagMap);
                        
                        $rowCells = array_map(fn($value) => Cell::fromValue($value), $rowValues);
                        $row = new Row($rowCells);
                        $writer->addRow($row);
                        
                        // Build productIdToSku đồng thời (cần cho các sheet phụ)
                        $productIdToSku[$p->id] = $p->sku;
                    }

                    unset($productsChunk);
                    gc_collect_cycles();
                    });
                
                // Cleanup sau mỗi chunk file
                unset($productIds);
                gc_collect_cycles();
            }
            unset($chunkFiles); // Giải phóng chunk files

            // =========================
            // Sheet 2: Images
            // =========================
            $imagesSheet = $writer->addNewSheetAndMakeItCurrent();
            $imagesSheet->setName(ProductWorkbookSchema::SHEET_IMAGES);
            
            $imagesHeaders = ProductWorkbookSchema::imageHeaders();
            $imagesHeaderCells = array_map(fn($value) => Cell::fromValue($value), $imagesHeaders);
            $writer->addRow(new Row($imagesHeaderCells));

            // Đọc lại từ chunk files - KHÔNG load tất cả IDs
            $chunkFilesForImages = glob("{$exportDir}/{$sessionId}_chunk_*.json");
            sort($chunkFilesForImages);
            
            foreach ($chunkFilesForImages as $chunkFile) {
                if (!file_exists($chunkFile)) {
                    continue;
                }
                
                $productIds = json_decode(file_get_contents($chunkFile), true);
                if (!is_array($productIds) || empty($productIds)) {
                    unset($productIds);
                    continue;
                }
                
                Image::whereIn('product_id', $productIds)
                    ->orderBy('product_id')
                    ->orderBy('order')
                    ->orderBy('id')
                    ->chunkById(200, function ($imagesChunk) use ($writer, $productIdToSku) {
                        foreach ($imagesChunk as $image) {
                            $sku = $productIdToSku[$image->product_id] ?? '';
                            if ($sku === '') {
                                continue;
                            }

                            $rowValues = $this->buildImageExportRow($sku, $image);
                            $rowCells = array_map(fn($value) => Cell::fromValue($value), $rowValues);
                            $writer->addRow(new Row($rowCells));
                        }

                        unset($imagesChunk);
                        gc_collect_cycles();
                    });
                
                unset($productIds);
                gc_collect_cycles();
            }
            unset($chunkFilesForImages);

            // =========================
            // Sheet 3: FAQs
            // =========================
            $faqsSheet = $writer->addNewSheetAndMakeItCurrent();
            $faqsSheet->setName(ProductWorkbookSchema::SHEET_FAQS);
            
            $faqsHeaders = ProductWorkbookSchema::faqHeaders();
            $faqsHeaderCells = array_map(fn($value) => Cell::fromValue($value), $faqsHeaders);
            $writer->addRow(new Row($faqsHeaderCells));

            // Đọc lại từ chunk files - KHÔNG load tất cả IDs
            $chunkFilesForFaqs = glob("{$exportDir}/{$sessionId}_chunk_*.json");
            sort($chunkFilesForFaqs);
            
            foreach ($chunkFilesForFaqs as $chunkFile) {
                if (!file_exists($chunkFile)) {
                    continue;
                }
                
                $productIds = json_decode(file_get_contents($chunkFile), true);
                if (!is_array($productIds) || empty($productIds)) {
                    unset($productIds);
                    continue;
                }
                
                ProductFaq::whereIn('product_id', $productIds)
                ->orderBy('product_id')
                ->chunkById(200, function ($faqsChunk) use ($writer, $productIdToSku) {
                    foreach ($faqsChunk as $faq) {
                        $sku = $productIdToSku[$faq->product_id] ?? '';

                        $rowValues = [
                            $sku,
                            $faq->question,
                            $faq->answer,
                            $faq->order,
                        ];
                        
                        $rowCells = array_map(fn($value) => Cell::fromValue($value), $rowValues);
                        $writer->addRow(new Row($rowCells));
                    }

                    unset($faqsChunk);
                    gc_collect_cycles();
                    });
                
                unset($productIds);
                gc_collect_cycles();
            }
            unset($chunkFilesForFaqs);

            // =========================
            // Sheet 4: How-Tos
            // =========================
            $howTosSheet = $writer->addNewSheetAndMakeItCurrent();
            $howTosSheet->setName(ProductWorkbookSchema::SHEET_HOW_TOS);
            
            $howTosHeaders = ProductWorkbookSchema::howToHeaders();
            $howTosHeaderCells = array_map(fn($value) => Cell::fromValue($value), $howTosHeaders);
            $writer->addRow(new Row($howTosHeaderCells));

            // Đọc lại từ chunk files - KHÔNG load tất cả IDs
            $chunkFilesForHowTos = glob("{$exportDir}/{$sessionId}_chunk_*.json");
            sort($chunkFilesForHowTos);
            
            foreach ($chunkFilesForHowTos as $chunkFile) {
                if (!file_exists($chunkFile)) {
                    continue;
                }
                
                $productIds = json_decode(file_get_contents($chunkFile), true);
                if (!is_array($productIds) || empty($productIds)) {
                    unset($productIds);
                    continue;
                }
                
                ProductHowTo::whereIn('product_id', $productIds)
                ->orderBy('product_id')
                ->chunkById(200, function ($howTosChunk) use ($writer, $productIdToSku) {
                    foreach ($howTosChunk as $howTo) {
                        $sku = $productIdToSku[$howTo->product_id] ?? '';

                        $steps = is_array($howTo->steps) ? implode('|', $howTo->steps) : ($howTo->steps ?? '');
                        $supplies = is_array($howTo->supplies) ? implode(',', $howTo->supplies) : ($howTo->supplies ?? '');

                        $rowValues = [
                            $sku,
                            $howTo->title,
                            $howTo->description,
                            $steps,
                            $supplies,
                            $howTo->is_active ? 1 : 0,
                        ];
                        
                        $rowCells = array_map(fn($value) => Cell::fromValue($value), $rowValues);
                        $writer->addRow(new Row($rowCells));
                    }

                    unset($howTosChunk);
                    gc_collect_cycles();
                    });
                
                unset($productIds);
                gc_collect_cycles();
            }
            unset($chunkFilesForHowTos);

            // =========================
            // Sheet 5: Variants
            // =========================
            $variantsSheet = $writer->addNewSheetAndMakeItCurrent();
            $variantsSheet->setName(ProductWorkbookSchema::SHEET_VARIANTS);
            
            $variantsHeaders = ProductWorkbookSchema::variantHeaders();
            $variantsHeaderCells = array_map(fn($value) => Cell::fromValue($value), $variantsHeaders);
            $writer->addRow(new Row($variantsHeaderCells));

            // Đọc lại từ chunk files - KHÔNG load tất cả IDs
            $chunkFilesForVariants = glob("{$exportDir}/{$sessionId}_chunk_*.json");
            sort($chunkFilesForVariants);
            
            foreach ($chunkFilesForVariants as $chunkFile) {
                if (!file_exists($chunkFile)) {
                    continue;
                }
                
                $productIds = json_decode(file_get_contents($chunkFile), true);
                if (!is_array($productIds) || empty($productIds)) {
                    unset($productIds);
                    continue;
                }
                
                ProductVariant::whereIn('product_id', $productIds)
                ->orderBy('product_id')
                ->chunkById(200, function ($variantsChunk) use ($writer, $productIdToSku) {
                    foreach ($variantsChunk as $variant) {
                        $sku = $productIdToSku[$variant->product_id] ?? '';

                        $rowValues = $this->buildVariantExportRow($sku, $variant);
                        
                        $rowCells = array_map(fn($value) => Cell::fromValue($value), $rowValues);
                        $writer->addRow(new Row($rowCells));
                    }

                    unset($variantsChunk);
                    gc_collect_cycles();
                    });
                
                unset($productIds);
                gc_collect_cycles();
            }
            unset($chunkFilesForVariants, $productIdToSku); // Cleanup

            // Close writer
            $writer->close();

            // Kiểm tra file đã được tạo chưa
            if (!file_exists($filePath)) {
                Log::error('Export file not created after save', [
                    'session_id' => $sessionId,
                    'file_path' => $filePath,
                ]);
                throw new \Exception('Không thể tạo file export.');
            }

            // Kiểm tra file size (phải > 0)
            $fileSize = filesize($filePath);
            if ($fileSize === false || $fileSize === 0) {
                Log::error('Export file is empty', [
                    'session_id' => $sessionId,
                    'file_path' => $filePath,
                    'file_size' => $fileSize,
                ]);
                throw new \Exception('File export rỗng hoặc không hợp lệ.');
            }

            Log::info('Export file created successfully (OpenSpout)', [
                'session_id' => $sessionId,
                'file_path' => $filePath,
                'file_size' => $fileSize,
                'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            ]);

            // Xóa chunk files
            $chunkFilesToDelete = glob("{$exportDir}/{$sessionId}_chunk_*.json");
            foreach ($chunkFilesToDelete as $chunkFile) {
                @unlink($chunkFile);
            }
            unset($chunkFilesToDelete);

            // Cập nhật status
            Cache::put($cacheKey, array_merge($exportData, [
                'status' => 'completed',
                'file_path' => $filePath,
                'file_size' => $fileSize,
                'completed_at' => now()->toDateTimeString(),
            ]), now()->addHours(2));
            
            Log::info('Export finalized successfully (OpenSpout)', [
                'session_id' => $sessionId,
                'file_path' => $filePath,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error finalizing export (OpenSpout)', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Cập nhật status error
            Cache::put($cacheKey, array_merge($exportData, [
                'status' => 'error',
                'error' => $e->getMessage(),
            ]), now()->addHours(2));
            
            throw $e;
        } finally {
            // OpenSpout tự cleanup, không cần làm gì thêm
            gc_collect_cycles();
        }
    }

    /**
     * Lấy URL download file
     */
    protected function getExportFileUrl(string $sessionId): string
    {
        return route('admin.products.export-import.download', ['sessionId' => $sessionId]);
    }

    /**
     * Cleanup export files
     */
    protected function cleanupExportFiles(string $sessionId)
    {
        $exportDir = storage_path('app/exports');
        $files = glob("{$exportDir}/{$sessionId}*");
        foreach ($files as $file) {
            @unlink($file);
        }
    }

    // ============================================
    // API METHODS CHO IMPORT VỚI FILE UPLOAD
    // ============================================

    /**
     * Bắt đầu import Excel với file upload (API)
     * Hỗ trợ parallel processing với nhiều workers
     */
    public function startImportWithFile(Request $request): JsonResponse
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls|max:51200', // max 50MB
            'workers' => 'nullable|integer|min:1|max:10', // Số luồng xử lý song song (1-10)
        ]);

        try {
            $file = $request->file('excel_file');
            $workers = (int) ($request->input('workers', 4)); // Mặc định 4 workers
            $workers = max(1, min(10, $workers)); // Đảm bảo trong khoảng 1-10
            
            // Tạo group_id để quản lý nhiều workers
            $groupId = 'import_group_'.time().'_'.uniqid();
            
            // Lưu file tạm (dùng chung cho tất cả workers)
            $tempDir = storage_path('app/imports');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            
            $tempFilePath = "{$tempDir}/{$groupId}.xlsx";
            $file->move($tempDir, "{$groupId}.xlsx");
            
            // Chỉ load sheet products để khởi tạo import, tránh parse toàn bộ workbook ở bước start
            $spreadsheet = $this->loadProductsOnlySpreadsheet($tempFilePath);
            $sheet = $this->findSheetByAliases($spreadsheet, ProductWorkbookSchema::productSheetAliases());
            
            $totalRows = 0;
            if ($sheet) {
                [, $headerIndex, $rows] = $this->extractSheetDataWithExactHeaders(
                    $sheet,
                    ProductWorkbookSchema::productHeaders(),
                    ProductWorkbookSchema::SHEET_PRODUCTS
                );

                $validRows = array_filter($rows, function ($row) use ($headerIndex) {
                    return $this->getTrimmedRowValueByHeader($row, $headerIndex, ['sku'], '') !== '';
                });
                $totalRows = count($validRows);
            } else {
                Log::warning('Import start: Sheet products không tồn tại', [
                    'available_sheets' => $spreadsheet->getSheetNames(),
                ]);
            }
            
            // Tạo session cho từng worker
            $sessionIds = [];
            $groupData = [
                'group_id' => $groupId,
                'file_path' => $tempFilePath,
                'total_rows' => $totalRows,
                'workers' => $workers,
                'completed_workers' => [],
                'status' => 'processing',
                'created_at' => now()->toDateTimeString(),
            ];
            
            for ($i = 0; $i < $workers; $i++) {
                $sessionId = "{$groupId}_worker_{$i}";
                $sessionIds[] = $sessionId;
                
                // Worker xử lý theo modulo giống hệt processImportChunk, nên assigned_rows cũng phải tính theo modulo.
                $assignedRows = $i < $totalRows
                    ? (int) floor(($totalRows - 1 - $i) / $workers) + 1
                    : 0;
                
                $cacheData = [
                    'group_id' => $groupId,
                    'worker_index' => $i,
                    'total_workers' => $workers,
                    'file_path' => $tempFilePath,
                    'total_rows' => $totalRows,
                    'assigned_rows' => $assignedRows,
                    'start_row_index' => $i,
                    'end_row_index' => $totalRows,
                    'processed' => 0,
                    'status' => 'processing',
                    'errors' => [],
                    'created_at' => now()->toDateTimeString(),
                ];
                
                Cache::put("import_{$sessionId}", $cacheData, now()->addHours(2));
            }
            
            // Lưu thông tin group
            Cache::put("import_group_{$groupId}", $groupData, now()->addHours(2));

            return response()->json([
                'success' => true,
                'group_id' => $groupId,
                'session_ids' => $sessionIds,
                'total_rows' => $totalRows,
                'workers' => $workers,
                'message' => "Bắt đầu nhập sản phẩm với {$workers} luồng song song...",
            ]);

        } catch (\Exception $e) {
            Log::error('Import start error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi bắt đầu nhập: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Xử lý import chunk (được gọi nhiều lần)
     * Hỗ trợ parallel processing với nhiều workers
     */
    public function processImportChunk(Request $request): JsonResponse
    {
        $sessionId = $request->input('session_id');
        $chunk = (int) $request->input('chunk');
        $chunkSize = (int) $request->input('chunk_size', 50);

        $request->validate([
            'session_id' => 'required|string',
            'chunk' => 'required|integer|min:0',
            'chunk_size' => 'required|integer|min:1|max:500',
        ]);

        $cacheKey = "import_{$sessionId}";
        $importData = Cache::get($cacheKey);

        if (! $importData) {
            return response()->json([
                'success' => false,
                'message' => 'Session không tồn tại hoặc đã hết hạn.',
            ], 404);
        }

        if ($importData['status'] === 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'Import đã bị hủy.',
                'cancelled' => true,
            ], 400);
        }

        if ($importData['status'] === 'completed' || $importData['status'] === 'completed_worker') {
            return response()->json([
                'success' => true,
                'completed' => true,
                'processed' => $importData['processed'],
                'total' => $importData['assigned_rows'] ?? $importData['total_rows'],
                'errors_count' => count($importData['errors'] ?? []),
            ]);
        }

        try {
            if (!file_exists($importData['file_path'])) {
                throw new \Exception('File import không tồn tại.');
            }

            $spreadsheet = $this->loadProductsOnlySpreadsheet($importData['file_path']);
            $sheet = $this->findSheetByAliases($spreadsheet, ProductWorkbookSchema::productSheetAliases());
            
            if (!$sheet) {
                Log::error('Import chunk: Sheet products không tồn tại', [
                    'available_sheets' => $spreadsheet->getSheetNames(),
                ]);
                throw new \Exception('Sheet "products" không tồn tại!');
            }

            [$headers, $headerIndex, $rows] = $this->extractSheetDataWithExactHeaders(
                $sheet,
                ProductWorkbookSchema::productHeaders(),
                ProductWorkbookSchema::SHEET_PRODUCTS
            );
            
            // Lọc các dòng có SKU
            $validRows = array_filter($rows, function ($row) use ($headerIndex) {
                return $this->getTrimmedRowValueByHeader($row, $headerIndex, ['sku'], '') !== '';
            });
            $validRows = array_values($validRows); // Reindex

            // Nếu có worker_index, chỉ lấy các dòng được gán cho worker này
            if (isset($importData['worker_index']) && isset($importData['total_workers'])) {
                $workerIndex = $importData['worker_index'];
                $totalWorkers = $importData['total_workers'];
                
                // Filter rows theo worker: chỉ lấy các dòng có index % total_workers == worker_index
                $workerRows = [];
                foreach ($validRows as $index => $row) {
                    if ($index % $totalWorkers === $workerIndex) {
                        $workerRows[] = $row;
                    }
                }
                $validRows = $workerRows;
            }

            // Tính toán chunk trong phạm vi rows của worker này
            $startIndex = $chunk * $chunkSize;
            $chunkRows = array_slice($validRows, $startIndex, $chunkSize);

            if (empty($chunkRows)) {
                // Worker này đã xử lý xong
                $workerIndex = $importData['worker_index'] ?? 0;
                $groupId = $importData['group_id'] ?? null;
                
                // Đánh dấu worker này đã hoàn thành
                Cache::put($cacheKey, array_merge($importData, [
                    'status' => 'completed_worker',
                ]), now()->addHours(2));
                
                // Nếu là worker 0 (master), kiểm tra xem tất cả workers đã hoàn thành chưa
                if ($workerIndex === 0 && $groupId) {
                    $allWorkersCompleted = $this->checkAllWorkersCompleted($groupId, $importData['total_workers'] ?? 1);
                    
                    if ($allWorkersCompleted) {
                        // Tất cả workers đã hoàn thành, finalize import
                        try {
                            $this->finalizeImportGroup($groupId);
                            
                            return response()->json([
                                'success' => true,
                                'completed' => true,
                                'processed' => $importData['processed'],
                                'total' => $importData['total_rows'],
                                'errors_count' => count($importData['errors'] ?? []),
                            ]);
                        } catch (\Exception $e) {
                            Log::error('Import chunk: Lỗi khi finalize group', [
                                'group_id' => $groupId,
                                'error' => $e->getMessage(),
                                'trace' => $e->getTraceAsString(),
                            ]);
                            
                            return response()->json([
                                'success' => false,
                                'completed' => false,
                                'message' => 'Lỗi khi hoàn thành import: '.$e->getMessage(),
                                'processed' => $importData['processed'],
                                'total' => $importData['total_rows'],
                                'errors_count' => count($importData['errors'] ?? []),
                            ], 500);
                        }
                    } else {
                        // Chờ các workers khác hoàn thành
                        return response()->json([
                            'success' => true,
                            'completed' => false,
                            'processed' => $importData['processed'],
                            'total' => $importData['assigned_rows'] ?? $importData['total_rows'],
                            'message' => 'Đang chờ các workers khác hoàn thành...',
                        ]);
                    }
                } else {
                    // Worker khác đã hoàn thành
                    return response()->json([
                        'success' => true,
                        'completed' => true,
                        'processed' => $importData['processed'],
                        'total' => $importData['assigned_rows'] ?? $importData['total_rows'],
                        'errors_count' => count($importData['errors'] ?? []),
                    ]);
                }
            }

            // Xử lý chunk này
            $errors = [];
            DB::beginTransaction();
            
            try {
                // Tạo spreadsheet tạm chỉ với chunk này
                $tempSpreadsheet = new Spreadsheet;
                $tempSheet = $tempSpreadsheet->getActiveSheet();
                $tempSheet->setTitle('products'); // QUAN TRỌNG: Set tên sheet
                $tempSheet->fromArray($headers, null, 'A1');
                $rowNum = 2;
                foreach ($chunkRows as $row) {
                    $tempSheet->fromArray($row, null, 'A'.$rowNum);
                    $rowNum++;
                }
                
                // Import chunk này - chỉ import products
                $this->importProducts($tempSpreadsheet, $errors);
                
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Import chunk error', [
                    'session_id' => $sessionId,
                    'chunk' => $chunk,
                    'worker_index' => $importData['worker_index'] ?? null,
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]);
                
                $errors[] = [
                    'type' => 'CHUNK_ERROR',
                    'sku' => 'N/A',
                    'message' => $e->getMessage(),
                    'chunk' => $chunk,
                    'worker_index' => $importData['worker_index'] ?? null,
                    'file' => basename($e->getFile()),
                    'line' => $e->getLine(),
                ];
            }

            // Cập nhật progress
            // CHỈ đếm số rows thực sự được xử lý trong chunk này (đã filter theo worker)
            $chunkProcessed = count($chunkRows);
            $processed = $importData['processed'] + $chunkProcessed;
            $allErrors = array_merge($importData['errors'] ?? [], $errors);
            
            Cache::put($cacheKey, array_merge($importData, [
                'processed' => $processed,
                'errors' => $allErrors,
                'last_chunk' => $chunk,
            ]), now()->addHours(2));

            // Dùng assigned_rows (số rows được gán cho worker này) thay vì total_rows
            $assignedRows = $importData['assigned_rows'] ?? $importData['total_rows'];
            
            // Đảm bảo processed không vượt quá assigned_rows
            if ($processed > $assignedRows) {
                Log::warning('Import chunk: processed vượt quá assigned_rows', [
                    'session_id' => $sessionId,
                    'worker_index' => $importData['worker_index'] ?? null,
                    'processed' => $processed,
                    'assigned_rows' => $assignedRows,
                    'chunk' => $chunk,
                    'chunk_rows_count' => $chunkProcessed,
                ]);
                $processed = $assignedRows; // Giới hạn processed
            }
            
            $progress = $assignedRows > 0 ? ($processed / $assignedRows) * 100 : 0;

            return response()->json([
                'success' => true,
                'processed' => $processed,
                'total' => $assignedRows, // Trả về assigned_rows cho worker này
                'progress' => round($progress, 2),
                'errors_count' => count($allErrors),
                'completed' => false,
            ]);

        } catch (\Exception $e) {
            Log::error('Import chunk: Lỗi nghiêm trọng', [
                'session_id' => $sessionId,
                'chunk' => $chunk,
                'worker_index' => $importData['worker_index'] ?? null,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            Cache::put($cacheKey, array_merge($importData, [
                'status' => 'error',
                'error' => $e->getMessage(),
            ]), now()->addHours(2));

            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xử lý chunk: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Hủy import
     */
    public function cancelImport(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'nullable|string',
            'group_id' => 'nullable|string',
        ]);

        $groupId = trim((string) $request->input('group_id', ''));
        $sessionId = trim((string) $request->input('session_id', ''));

        if ($groupId === '' && $sessionId === '') {
            return response()->json([
                'success' => false,
                'message' => 'Thiếu session_id hoặc group_id để hủy import.',
            ], 422);
        }

        if ($groupId !== '') {
            $groupKey = "import_group_{$groupId}";
            $groupData = Cache::get($groupKey);

            if ($groupData) {
                Cache::put($groupKey, array_merge($groupData, [
                    'status' => 'cancelled',
                ]), now()->addHours(2));

                for ($i = 0; $i < ($groupData['workers'] ?? 1); $i++) {
                    $workerSessionId = "{$groupId}_worker_{$i}";
                    $workerData = Cache::get("import_{$workerSessionId}");

                    if ($workerData) {
                        Cache::put("import_{$workerSessionId}", array_merge($workerData, [
                            'status' => 'cancelled',
                        ]), now()->addHours(2));
                    }
                }
            }

            $this->cleanupImportFiles($groupId);

            return response()->json([
                'success' => true,
                'message' => 'Đã hủy nhập sản phẩm.',
            ]);
        }

        $cacheKey = "import_{$sessionId}";
        $importData = Cache::get($cacheKey);

        if ($importData) {
            Cache::put($cacheKey, array_merge($importData, [
                'status' => 'cancelled',
            ]), now()->addHours(2));

            $cleanupKey = $importData['group_id'] ?? $sessionId;
            $this->cleanupImportFiles($cleanupKey);
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã hủy nhập sản phẩm.',
        ]);
    }

    /**
     * Lấy progress của import
     */
    public function getImportProgress(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|string',
        ]);

        $sessionId = $request->input('session_id');
        $cacheKey = "import_{$sessionId}";
        $importData = Cache::get($cacheKey);

        if (! $importData) {
            return response()->json([
                'success' => false,
                'message' => 'Session không tồn tại.',
            ], 404);
        }

        // Nếu có group_id, tính tổng hợp từ tất cả workers real-time
        $errors = $importData['errors'] ?? [];
        $status = $importData['status'] ?? 'processing';
        $processed = $importData['processed'] ?? 0;
        $total = $importData['total_rows'] ?? 0;
        $errorMessage = $importData['error'] ?? null;

        $logFile = $importData['log_file'] ?? null;

        if (isset($importData['group_id'])) {
            $groupId = $importData['group_id'];
            $groupKey = "import_group_{$groupId}";
            $groupData = Cache::get($groupKey);

            if ($groupData) {
                $status = $groupData['status'] ?? $status;
                $total = $groupData['total_rows'] ?? $total; // Lấy total từ group (chỉ 1 giá trị)
                $logFile = $groupData['log_file'] ?? $logFile;
                $errorMessage = $groupData['error'] ?? $errorMessage;
                
                // Tính tổng processed và errors từ tất cả workers real-time
                $totalProcessed = 0;
                $allErrors = [];
                
                for ($i = 0; $i < ($groupData['workers'] ?? 1); $i++) {
                    $workerSessionId = "{$groupId}_worker_{$i}";
                    $workerData = Cache::get("import_{$workerSessionId}");
                    
                    if ($workerData) {
                        // Mỗi worker chỉ xử lý một phần dữ liệu (chia theo modulo)
                        // Nên processed của mỗi worker là số rows mà worker đó đã xử lý
                        $workerProcessed = $workerData['processed'] ?? 0;
                        $totalProcessed += $workerProcessed;
                        $allErrors = array_merge($allErrors, $workerData['errors'] ?? []);
                    }
                }
                
                // Đảm bảo totalProcessed không vượt quá total_rows
                if ($totalProcessed > $total) {
                    Log::warning('Import progress: totalProcessed vượt quá total_rows', [
                        'group_id' => $groupId,
                        'total_processed' => $totalProcessed,
                        'total_rows' => $total,
                        'workers' => $groupData['workers'] ?? 1,
                    ]);
                    $totalProcessed = $total; // Giới hạn processed
                }
                
                $processed = $totalProcessed;
                $errors = $allErrors;
                
                // Kiểm tra xem tất cả workers đã hoàn thành chưa
                $allWorkersCompleted = $this->checkAllWorkersCompleted($groupId, $groupData['workers'] ?? 1);
                
                // Nếu tất cả workers đã hoàn thành nhưng group chưa finalize, trigger finalize
                if ($allWorkersCompleted && $groupData['status'] !== 'completed' && $groupData['status'] !== 'finalizing') {
                    // Worker 0 sẽ trigger finalize, nhưng nếu worker 0 chưa gọi thì trigger ở đây
                    try {
                        $this->finalizeImportGroup($groupId);
                        // Reload group data sau khi finalize
                        $groupData = Cache::get($groupKey);
                        if ($groupData) {
                            $status = $groupData['status'] ?? $status;
                        }
                    } catch (\Exception $e) {
                        Log::error('Import progress: Lỗi khi finalize group', [
                            'group_id' => $groupId,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
                
                // Cập nhật group data với processed mới nhất (để cache)
                if ($groupData['status'] !== 'completed' && $groupData['status'] !== 'finalizing') {
                    Cache::put($groupKey, array_merge($groupData, [
                        'processed' => $totalProcessed,
                        'errors' => $allErrors,
                    ]), now()->addHours(2));
                }
                
                // Nếu tất cả workers đã completed, đánh dấu completed
                if ($allWorkersCompleted) {
                    $status = $groupData['status'] ?? 'completed';
                }
            }
        }

        $progress = $total > 0 ? ($processed / $total) * 100 : 0;
        
        // Kiểm tra completed: group status là completed HOẶC tất cả workers đã completed
        $isCompleted = $status === 'completed' || $status === 'completed_worker';
        if (isset($importData['group_id']) && !$isCompleted) {
            $groupIdForCheck = $importData['group_id'];
            $isCompleted = $this->checkAllWorkersCompleted($groupIdForCheck, $importData['total_workers'] ?? 1);
        }

        return response()->json([
            'success' => true,
            'processed' => $processed,
            'total' => $total,
            'progress' => round($progress, 2),
            'status' => $status,
            'completed' => $isCompleted,
            'cancelled' => $status === 'cancelled',
            'log_file' => $logFile,
            'error' => $errorMessage,
            'errors_count' => count($errors),
            'errors' => $errors, // Trả toàn bộ lỗi để hiển thị trên UI
        ]);
    }

    /**
     * Kiểm tra xem tất cả workers đã hoàn thành chưa
     */
    protected function checkAllWorkersCompleted(string $groupId, int $totalWorkers): bool
    {
        $groupKey = "import_group_{$groupId}";
        $groupData = Cache::get($groupKey);
        
        if (!$groupData) {
            return false;
        }
        
        $completedWorkers = $groupData['completed_workers'] ?? [];
        
        // Kiểm tra từng worker
        for ($i = 0; $i < $totalWorkers; $i++) {
            $sessionId = "{$groupId}_worker_{$i}";
            $workerData = Cache::get("import_{$sessionId}");
            
            if (!$workerData) {
                return false;
            }
            
            $status = $workerData['status'] ?? 'processing';
            if ($status !== 'completed_worker' && $status !== 'completed') {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Hoàn thành import cho cả group (sau khi tất cả workers đã xong)
     */
    protected function finalizeImportGroup(string $groupId)
    {
        $groupKey = "import_group_{$groupId}";
        $groupData = Cache::get($groupKey);
        
        if (!$groupData) {
            throw new \Exception("Group {$groupId} không tồn tại.");
        }
        
        // Kiểm tra xem đã finalize chưa
        if ($groupData['status'] === 'completed') {
            return;
        }
        
        // Đánh dấu đang finalize
        Cache::put($groupKey, array_merge($groupData, [
            'status' => 'finalizing',
        ]), now()->addHours(2));
        
        // Thu thập tất cả errors từ các workers
        $allErrors = [];
        $totalProcessed = 0;
        
        for ($i = 0; $i < ($groupData['workers'] ?? 1); $i++) {
            $sessionId = "{$groupId}_worker_{$i}";
            $workerData = Cache::get("import_{$sessionId}");
            
            if ($workerData) {
                $totalProcessed += $workerData['processed'] ?? 0;
                $allErrors = array_merge($allErrors, $workerData['errors'] ?? []);
            }
        }
        
        try {
            // Import các sheet khác (images, faqs, how_tos, variants) từ file gốc
            if (file_exists($groupData['file_path'])) {
                $spreadsheet = IOFactory::load($groupData['file_path']);
                
                // Import Images (Sheet 2)
                try {
                    $this->importImages($spreadsheet, $allErrors);
                } catch (\Exception $e) {
                    Log::error('Finalize import group: Lỗi khi import images', [
                        'group_id' => $groupId,
                        'error' => $e->getMessage(),
                    ]);
                    $allErrors[] = [
                        'type' => 'IMAGES_IMPORT_ERROR',
                        'sku' => 'N/A',
                        'message' => $e->getMessage(),
                    ];
                }

                // Import FAQs (Sheet 3)
                try {
                    $this->importFaqs($spreadsheet, $allErrors);
                } catch (\Exception $e) {
                    Log::error('Finalize import group: Lỗi khi import FAQs', [
                        'group_id' => $groupId,
                        'error' => $e->getMessage(),
                    ]);
                    $allErrors[] = [
                        'type' => 'FAQS_IMPORT_ERROR',
                        'sku' => 'N/A',
                        'message' => $e->getMessage(),
                    ];
                }

                // Import How-Tos (Sheet 4)
                try {
                    $this->importHowTos($spreadsheet, $allErrors);
                } catch (\Exception $e) {
                    Log::error('Finalize import group: Lỗi khi import How-Tos', [
                        'group_id' => $groupId,
                        'error' => $e->getMessage(),
                    ]);
                    $allErrors[] = [
                        'type' => 'HOWTOS_IMPORT_ERROR',
                        'sku' => 'N/A',
                        'message' => $e->getMessage(),
                    ];
                }

                // Import Variants (Sheet 5)
                try {
                    $this->importVariants($spreadsheet, $allErrors);
                } catch (\Exception $e) {
                    Log::error('Finalize import group: Lỗi khi import Variants', [
                        'group_id' => $groupId,
                        'error' => $e->getMessage(),
                    ]);
                    $allErrors[] = [
                        'type' => 'VARIANTS_IMPORT_ERROR',
                        'sku' => 'N/A',
                        'message' => $e->getMessage(),
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::error('Finalize import group error', [
                'group_id' => $groupId,
                'error' => $e->getMessage(),
            ]);
            $allErrors[] = [
                'type' => 'FINALIZE_ERROR',
                'sku' => 'N/A',
                'message' => $e->getMessage(),
            ];
        }

        // Xóa cache tất cả sản phẩm
        $this->clearAllProductCaches();

        // Ghi log lỗi nếu có
        $logFile = null;
        if (!empty($allErrors)) {
            $logFile = $this->writeErrorLog($allErrors, "import_group_{$groupId}.xlsx");
        }

        // Cập nhật status cho group và tất cả workers
        Cache::put($groupKey, array_merge($groupData, [
            'status' => 'completed',
            'completed_at' => now()->toDateTimeString(),
            'log_file' => $logFile,
            'errors' => $allErrors,
            'processed' => $totalProcessed,
        ]), now()->addHours(2));
        
        // Cập nhật status cho tất cả workers
        for ($i = 0; $i < ($groupData['workers'] ?? 1); $i++) {
            $sessionId = "{$groupId}_worker_{$i}";
            $workerData = Cache::get("import_{$sessionId}");
            
            if ($workerData) {
                Cache::put("import_{$sessionId}", array_merge($workerData, [
                    'status' => 'completed',
                ]), now()->addHours(2));
            }
        }

        // Xóa file tạm
        if (file_exists($groupData['file_path'])) {
            @unlink($groupData['file_path']);
        }
    }

    /**
     * Hoàn thành import (backward compatibility - cho single worker)
     */
    protected function finalizeImport(string $sessionId, array $importData)
    {
        $cacheKey = "import_{$sessionId}";
        
        // Kiểm tra xem đã finalize chưa
        $currentData = Cache::get($cacheKey);
        if ($currentData && $currentData['status'] === 'completed') {
            return;
        }

        // Đánh dấu đang finalize
        Cache::put($cacheKey, array_merge($importData, [
            'status' => 'finalizing',
        ]), now()->addHours(2));

        $errors = $importData['errors'] ?? [];

        try {
            // Import các sheet khác (images, faqs, how_tos, variants) từ file gốc
            if (file_exists($importData['file_path'])) {
                $spreadsheet = IOFactory::load($importData['file_path']);
                
                // Import Images (Sheet 2)
                try {
                    $this->importImages($spreadsheet, $errors);
                } catch (\Exception $e) {
                    Log::error('Finalize import: Lỗi khi import images', [
                        'error' => $e->getMessage(),
                    ]);
                    $errors[] = [
                        'type' => 'IMAGES_IMPORT_ERROR',
                        'sku' => 'N/A',
                        'message' => $e->getMessage(),
                    ];
                }

                // Import FAQs (Sheet 3)
                try {
                    $this->importFaqs($spreadsheet, $errors);
                } catch (\Exception $e) {
                    Log::error('Finalize import: Lỗi khi import FAQs', [
                        'error' => $e->getMessage(),
                    ]);
                    $errors[] = [
                        'type' => 'FAQS_IMPORT_ERROR',
                        'sku' => 'N/A',
                        'message' => $e->getMessage(),
                    ];
                }

                // Import How-Tos (Sheet 4)
                try {
                    $this->importHowTos($spreadsheet, $errors);
                } catch (\Exception $e) {
                    Log::error('Finalize import: Lỗi khi import How-Tos', [
                        'error' => $e->getMessage(),
                    ]);
                    $errors[] = [
                        'type' => 'HOWTOS_IMPORT_ERROR',
                        'sku' => 'N/A',
                        'message' => $e->getMessage(),
                    ];
                }

                // Import Variants (Sheet 5)
                try {
                    $this->importVariants($spreadsheet, $errors);
                } catch (\Exception $e) {
                    Log::error('Finalize import: Lỗi khi import Variants', [
                        'error' => $e->getMessage(),
                    ]);
                    $errors[] = [
                        'type' => 'VARIANTS_IMPORT_ERROR',
                        'sku' => 'N/A',
                        'message' => $e->getMessage(),
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::error('Finalize import error', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);
            $errors[] = [
                'type' => 'FINALIZE_ERROR',
                'sku' => 'N/A',
                'message' => $e->getMessage(),
            ];
        }

        // Xóa cache tất cả sản phẩm
        $this->clearAllProductCaches();

        // Ghi log lỗi nếu có
        $logFile = null;
        if (!empty($errors)) {
            $logFile = $this->writeErrorLog($errors, "import_{$sessionId}.xlsx");
        }

        // Cập nhật status
        Cache::put($cacheKey, array_merge($importData, [
            'status' => 'completed',
            'completed_at' => now()->toDateTimeString(),
            'log_file' => $logFile,
            'errors' => $errors,
        ]), now()->addHours(2));

        // Xóa file tạm
        if (file_exists($importData['file_path'])) {
            @unlink($importData['file_path']);
        }
    }

    /**
     * Cleanup import files
     */
    protected function cleanupImportFiles(string $sessionId)
    {
        $importDir = storage_path('app/imports');
        $files = glob("{$importDir}/{$sessionId}*");
        foreach ($files as $file) {
            @unlink($file);
        }
    }
}
