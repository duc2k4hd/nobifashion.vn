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
            \App\Jobs\ExportProductsJob::dispatch($sessionId, $categoryIds, $brandIds, $totalProducts);

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
            // Tăng memory limit và time limit cho export lớn
            set_time_limit(0);
            ini_set('memory_limit', '256M'); // Fallback, nhưng sẽ dùng disk cache nên không cần nhiều
            
            // BẬT DISK CACHE cho PhpSpreadsheet - QUAN TRỌNG để giảm RAM
            $cacheDir = sys_get_temp_dir() . '/phpspreadsheet_cache_' . uniqid();
            if (!is_dir($cacheDir)) {
                @mkdir($cacheDir, 0755, true);
            }
            
            try {
                // Bật cache cell ra disk để giảm RAM xuống gần 0
                if (method_exists(Settings::class, 'setCacheStorageMethod')) {
                    // Thử dùng constant từ CachedObjectStorageFactory nếu class tồn tại
                    $cacheClass = 'PhpOffice\\PhpSpreadsheet\\Cell\\CachedObjectStorageFactory';
                    if (class_exists($cacheClass) && defined("{$cacheClass}::cache_to_discISAM")) {
                        Settings::setCacheStorageMethod(
                            constant("{$cacheClass}::cache_to_discISAM"),
                            ['dir' => $cacheDir]
                        );
                    } else {
                        // Fallback: dùng string constant
                        Settings::setCacheStorageMethod('cache_to_discISAM', ['dir' => $cacheDir]);
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Export stream: cannot enable disc cache', [
                    'error' => $e->getMessage(),
                ]);
                // Fallback: vẫn tiếp tục nhưng sẽ tốn RAM hơn
            }

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('products');

            // Header giống sheet products
            $headers = [
                'sku', 'name', 'slug', 'description', 'short_description',
                'price', 'sale_price', 'cost_price', 'stock_quantity',
                'meta_title', 'meta_description', 'meta_keywords',
                'meta_canonical', 'primary_category_slug', 'brand_slug',
                'category_slugs', 'tag_slugs',
                'image_ids', 'link_catalog', 'is_featured', 'is_active',
            ];
            $sheet->fromArray($headers, null, 'A1');

            // Load maps một lần (nhỏ, không ảnh hưởng RAM)
            $categoryMap = Category::pluck('slug', 'id')->toArray();
            $brandMap = Brand::pluck('slug', 'id')->toArray();
            $tagMap = Tag::pluck('name', 'id')->toArray();

            $row = 2;
            $chunkSize = 100; // Giảm chunk size để giảm memory peak

            // Base query với filter hiện tại
            $query = $this->buildFilterQuery($request)
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
                    'image_ids',
                    'link_catalog',
                    'is_featured',
                    'is_active',
                ])
                ->orderBy('id');

            // Duyệt theo chunk nhỏ để không ăn RAM
            $query->chunkById($chunkSize, function ($products) use (&$row, $sheet, $categoryMap, $brandMap, $tagMap) {
                foreach ($products as $p) {
                    $primarySlug = $p->primary_category_id ? ($categoryMap[$p->primary_category_id] ?? null) : null;
                    $brandSlug = $p->brand_id ? ($brandMap[$p->brand_id] ?? null) : null;

                    $categorySlugs = '';
                    if (!empty($p->category_ids) && is_array($p->category_ids)) {
                        $slugs = array_map(function ($id) use ($categoryMap) {
                            return $categoryMap[$id] ?? null;
                        }, $p->category_ids);
                        $categorySlugs = implode(',', array_filter($slugs));
                    }

                    $tagNames = '';
                    if (!empty($p->tag_ids) && is_array($p->tag_ids)) {
                        $names = array_map(function ($id) use ($tagMap) {
                            return $tagMap[$id] ?? null;
                        }, $p->tag_ids);
                        $tagNames = implode(',', array_filter($names));
                    }

                    $imageIds = '';
                    if (!empty($p->image_ids) && is_array($p->image_ids)) {
                        $imageIds = implode(',', array_map(fn ($id) => 'IMG'.$id, $p->image_ids));
                    }

                    $linkCatalog = '';
                    if (!empty($p->link_catalog) && is_array($p->link_catalog)) {
                        $linkCatalog = implode(',', $p->link_catalog);
                    } elseif (is_string($p->link_catalog)) {
                        $linkCatalog = $p->link_catalog;
                    }

                    $metaKeywords = is_array($p->meta_keywords) ? implode(',', $p->meta_keywords) : ($p->meta_keywords ?? '');

                    // Ghi từng cell với setCellValueExplicit để tránh auto-format và giảm RAM
                    $sheet->setCellValueExplicit("A{$row}", $p->sku, DataType::TYPE_STRING);
                    $sheet->setCellValueExplicit("B{$row}", $p->name, DataType::TYPE_STRING);
                    $sheet->setCellValueExplicit("C{$row}", $p->slug, DataType::TYPE_STRING);
                    $sheet->setCellValueExplicit("D{$row}", $p->description, DataType::TYPE_STRING);
                    $sheet->setCellValueExplicit("E{$row}", $p->short_description, DataType::TYPE_STRING);
                    $sheet->setCellValueExplicit("F{$row}", $p->price, DataType::TYPE_NUMERIC);
                    $sheet->setCellValueExplicit("G{$row}", $p->sale_price, DataType::TYPE_NUMERIC);
                    $sheet->setCellValueExplicit("H{$row}", $p->cost_price, DataType::TYPE_NUMERIC);
                    $sheet->setCellValueExplicit("I{$row}", $p->stock_quantity, DataType::TYPE_NUMERIC);
                    $sheet->setCellValueExplicit("J{$row}", $p->meta_title, DataType::TYPE_STRING);
                    $sheet->setCellValueExplicit("K{$row}", $p->meta_description, DataType::TYPE_STRING);
                    $sheet->setCellValueExplicit("L{$row}", $metaKeywords, DataType::TYPE_STRING);
                    $sheet->setCellValueExplicit("M{$row}", $p->meta_canonical, DataType::TYPE_STRING);
                    $sheet->setCellValueExplicit("N{$row}", $primarySlug, DataType::TYPE_STRING);
                    $sheet->setCellValueExplicit("O{$row}", $brandSlug, DataType::TYPE_STRING);
                    $sheet->setCellValueExplicit("P{$row}", $categorySlugs, DataType::TYPE_STRING);
                    $sheet->setCellValueExplicit("Q{$row}", $tagNames, DataType::TYPE_STRING);
                    $sheet->setCellValueExplicit("R{$row}", $imageIds, DataType::TYPE_STRING);
                    $sheet->setCellValueExplicit("S{$row}", $linkCatalog, DataType::TYPE_STRING);
                    $sheet->setCellValueExplicit("T{$row}", $p->is_featured ? 1 : 0, DataType::TYPE_NUMERIC);
                    $sheet->setCellValueExplicit("U{$row}", $p->is_active ? 1 : 0, DataType::TYPE_NUMERIC);

                    $row++;
                }

                // Giải phóng memory sau mỗi chunk
                unset($products);
                gc_collect_cycles();
            });

            // Tắt pre-calculate formulas để giảm RAM
            $writer = new Xlsx($spreadsheet);
            if (method_exists($writer, 'setPreCalculateFormulas')) {
                $writer->setPreCalculateFormulas(false);
            }

            // Stream trực tiếp ra output - không giữ trong RAM
            $writer->save('php://output');

            // Cleanup
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet, $writer, $sheet);
            gc_collect_cycles();
            
            // Xóa cache directory
            if (isset($cacheDir) && is_dir($cacheDir)) {
                $files = glob($cacheDir . '/*');
                foreach ($files as $file) {
                    @unlink($file);
                }
                @rmdir($cacheDir);
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
            'excel_file' => 'required|file|mimes:xlsx,xls|max:10240', // max 10MB
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
        $sheet->setTitle('products');

        $headers = [
            'sku', 'name', 'slug', 'description', 'short_description',
            'price', 'sale_price', 'cost_price', 'stock_quantity',
            'meta_title', 'meta_description', 'meta_keywords',
            'meta_canonical', 'primary_category_slug', 'brand_slug', 'category_slugs', 'tag_slugs',
            'image_ids', 'link_catalog', 'is_featured', 'is_active', 'created_by',
        ];
        $sheet->fromArray($headers, null, 'A1');

        $row = 2;
        foreach ($products as $product) {
            $primarySlug = optional($product->primaryCategory)->slug;
            $brandSlug = optional($product->brand)->slug;

            $categorySlugs = '';
            if (! empty($product->category_ids)) {
                $slugs = array_map(function ($id) use ($categoryMap) {
                    return $categoryMap[$id] ?? null;
                }, $product->category_ids ?? []);
                $categorySlugs = implode(',', array_filter($slugs));
            }

            $tagNames = '';
            if (! empty($product->tag_ids)) {
                $names = array_map(function ($id) use ($tagMap) {
                    return $tagMap[$id] ?? null;
                }, $product->tag_ids ?? []);
                $tagNames = implode(',', array_filter($names));
            }

            // Format image_ids: IMG1,IMG2,IMG3
            $imageIds = '';
            if (! empty($product->image_ids) && is_array($product->image_ids)) {
                $imageIds = implode(',', array_map(function ($id) {
                    return 'IMG'.$id;
                }, $product->image_ids));
            }

            // Format link_catalog: URL1,URL2,URL3 hoặc JSON
            $linkCatalog = '';
            if (! empty($product->link_catalog) && is_array($product->link_catalog)) {
                $linkCatalog = implode(',', $product->link_catalog);
            } elseif (is_string($product->link_catalog)) {
                $linkCatalog = $product->link_catalog;
            }

            $sheet->fromArray([
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
                is_array($product->meta_keywords) ? implode(',', $product->meta_keywords) : ($product->meta_keywords ?? ''),
                $product->meta_canonical,
                $primarySlug,
                $brandSlug,
                $categorySlugs,
                $tagNames,
                $imageIds,
                $linkCatalog,
                $product->is_featured ? 1 : 0,
                $product->is_active ? 1 : 0,
                $product->created_by,
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
        $sheet->setTitle('images');

        $headers = ['sku', 'image_key', 'url', 'title', 'notes', 'alt', 'is_primary', 'order'];
        $sheet->fromArray($headers, null, 'A1');

        $row = 2;
        foreach ($products as $product) {
            if (! empty($product->image_ids) && is_array($product->image_ids)) {
                foreach ($product->image_ids as $imageId) {
                    $image = $images->get($imageId);
                    if ($image) {
                        $sheet->fromArray([
                            $product->sku ?? '',
                            'IMG'.$image->id,
                            $image->url,
                            $image->title,
                            $image->notes,
                            $image->alt,
                            $image->is_primary ? 1 : 0,
                            $image->order,
                        ], null, 'A'.$row);
                        $row++;
                    }
                }
            }
        }
    }

    /**
     * Build FAQs Sheet
     */
    private function buildFaqsSheet(Spreadsheet $spreadsheet, $products)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('faqs');

        $headers = ['sku', 'question', 'answer', 'order'];
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
        $sheet->setTitle('how_tos');

        $headers = ['sku', 'title', 'description', 'steps', 'supplies', 'is_active'];
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
        $sheet->setTitle('variants');

        $headers = [
            'product_sku',
            'variant_name',
            'variant_sku',
            'price',
            'sale_price',
            'cost_price',
            'stock_quantity',
            'image_id',
            'attributes_json',
            'is_active',
            'sort_order',
        ];

        $sheet->fromArray($headers, null, 'A1');

        $row = 2;
        foreach ($products as $product) {
            if (! $product->variants || $product->variants->isEmpty()) {
                continue;
            }

            foreach ($product->variants as $variant) {
                $sheet->fromArray([
                    $product->sku,
                    $variant->name,
                    $variant->sku,
                    $variant->price,
                    $variant->sale_price,
                    $variant->cost_price,
                    $variant->stock_quantity,
                    $variant->image_id,
                    $variant->attributes ? json_encode($variant->attributes, JSON_UNESCAPED_UNICODE) : null,
                    $variant->is_active ? 1 : 0,
                    $variant->sort_order,
                ], null, 'A'.$row);
                $row++;
            }
        }
    }

    /**
     * Import Products
     */
    private function importProducts($spreadsheet, &$errors)
    {
        $sheet = $spreadsheet->getSheetByName('products');
        if (! $sheet) {
            Log::error('Import products: Sheet products không tồn tại', [
                'available_sheets' => $spreadsheet->getSheetNames(),
            ]);
            throw new \Exception('Sheet "products" không tồn tại!');
        }

        $rows = $sheet->toArray();
        $headers = array_shift($rows);

        $categoryMap = [];
        $brandMap = [];
        $tagCache = [];
        $processedCount = 0;
        $errorCount = 0;

        foreach ($rows as $rowIndex => $row) {
            if (empty($row[0])) {
                continue;
            } // Bỏ qua dòng trống (SKU rỗng)

            $sku = trim($row[0] ?? '');
            $name = trim($row[1] ?? '');
            // Logic slug: ưu tiên slug từ Excel, nếu không có thì dùng SKU, cuối cùng fallback về name
            $slug = trim($row[2] ?? '');
            if (empty($slug)) {
                $slug = Str::slug($sku ?: $name);
            }
            $description = trim($row[3] ?? '');
            $shortDescription = trim($row[4] ?? '');
            $price = (float) ($row[5] ?? 0);
            $salePrice = ! empty($row[6]) ? (float) $row[6] : null;
            $costPrice = ! empty($row[7]) ? (float) $row[7] : null;
            $stockQuantity = (int) ($row[8] ?? 0);
            $metaTitle = trim($row[9] ?? '');
            $metaDescription = trim($row[10] ?? '');
            $metaKeywordsRaw = trim($row[11] ?? '');
            $metaCanonical = trim($row[12] ?? '');
            $primaryCategorySlug = trim($row[13] ?? '');
            $brandSlug = trim($row[14] ?? '');
            $categorySlugs = trim($row[15] ?? '');
            $tagSlugs = trim($row[16] ?? '');
            $imageIdsRaw = trim($row[17] ?? '');
            $linkCatalogRaw = trim($row[18] ?? '');
            $isFeatured = isset($row[19]) ? (bool) $row[19] : false;
            $isActive = isset($row[20]) ? (bool) $row[20] : true;
            $createdBy = (int) ($row[21] ?? (Auth::check() ? Auth::id() : 1));

            if (empty($name)) {
                continue;
            }

            // Xử lý meta_keywords
            $metaKeywords = null;
            if (! empty($metaKeywordsRaw)) {
                $metaKeywords = array_filter(array_map('trim', explode(',', $metaKeywordsRaw)));
            }

            // Tính lại meta_canonical luôn theo slug và site_url (bỏ qua giá trị trong file Excel)
            $domainName = \App\Models\Setting::where('key', 'site_url')->value('value') ?? config('app.url');
            $domainName = rtrim($domainName, '/');
            $computedCanonical = $domainName.'/'.$slug;

            // Xử lý brand_id
            $brandId = null;
            if (! empty($brandSlug)) {
                if (isset($brandMap[$brandSlug])) {
                    $brandId = $brandMap[$brandSlug];
                } else {
                    $brand = Brand::where('slug', $brandSlug)->where('is_active', true)->first();
                    if ($brand) {
                        $brandId = $brand->id;
                        $brandMap[$brandSlug] = $brand->id;
                    } else {
                        $errors[] = [
                            'type' => 'BRAND_NOT_FOUND',
                            'sku' => $sku ?: 'N/A',
                            'brand_slug' => $brandSlug,
                            'message' => "Brand với slug '{$brandSlug}' không tồn tại hoặc không active.",
                            'row' => $rowIndex + 2,
                            'sheet' => 'products',
                        ];
                    }
                }
            }

            // Xử lý primary_category_id
            $primaryCategoryId = null;
            if (! empty($primaryCategorySlug)) {
                if (isset($categoryMap[$primaryCategorySlug])) {
                    $primaryCategoryId = $categoryMap[$primaryCategorySlug];
                } else {
                    $cat = Category::where('slug', $primaryCategorySlug)->first();
                    if ($cat) {
                        $primaryCategoryId = $cat->id;
                        $categoryMap[$primaryCategorySlug] = $cat->id;
                    } else {
                        $errors[] = [
                            'type' => 'PRIMARY_CATEGORY_NOT_FOUND',
                            'sku' => $sku ?: 'N/A',
                            'category_slug' => $primaryCategorySlug,
                            'message' => "Primary category với slug '{$primaryCategorySlug}' không tồn tại.",
                            'row' => $rowIndex + 2,
                            'sheet' => 'products',
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
                    if (isset($categoryMap[$catSlug])) {
                        $categoryIds[] = $categoryMap[$catSlug];
                    } else {
                        $cat = Category::where('slug', $catSlug)->first();
                        if ($cat) {
                            $categoryIds[] = $cat->id;
                            $categoryMap[$catSlug] = $cat->id;
                        } else {
                            $errors[] = [
                                'type' => 'CATEGORY_NOT_FOUND',
                                'sku' => $sku ?: 'N/A',
                                'category_slug' => $catSlug,
                                'message' => "Category với slug '{$catSlug}' không tồn tại.",
                                'row' => $rowIndex + 2,
                                'sheet' => 'products',
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

            // Xử lý image_ids (sẽ được xử lý sau trong importImages)
            // Tạm thời để null, sẽ cập nhật sau khi import images
            $imageIds = null;

            // Xử lý link_catalog
            $linkCatalog = null;
            if (! empty($linkCatalogRaw)) {
                // Hỗ trợ cả comma-separated và JSON
                if (preg_match('/^\[.*\]$/', $linkCatalogRaw)) {
                    // JSON format
                    $decoded = json_decode($linkCatalogRaw, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $linkCatalog = array_filter(array_map('trim', $decoded));
                    }
                } else {
                    // Comma-separated format
                    $linkCatalog = array_filter(array_map('trim', explode(',', $linkCatalogRaw)));
                }
                $linkCatalog = ! empty($linkCatalog) ? array_values($linkCatalog) : null;
            }

            // Tìm product theo SKU
            $product = Product::where('sku', $sku)->first();

            // Chuẩn bị data để update/create
            // QUAN TRỌNG: Chỉ thêm các trường có giá trị (không rỗng) để tránh ghi đè dữ liệu cũ
            $data = [];
            $hasOtherData = false; // Flag để kiểm tra xem có dữ liệu khác ngoài name không
            
            // Chỉ thêm các trường có giá trị (không rỗng)
            if (!empty($name)) {
                $data['name'] = $name;
                $data['slug'] = $slug;
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
            if ($linkCatalog !== null) {
                $data['link_catalog'] = $linkCatalog;
                $hasOtherData = true;
            }
            
            // is_featured và is_active chỉ update nếu có giá trị trong Excel (không phải mặc định)
            // Kiểm tra xem có giá trị trong Excel không (không phải mặc định false/true)
            if (isset($row[19])) {
                $data['is_featured'] = $isFeatured;
                $hasOtherData = true;
            }
            if (isset($row[20])) {
                $data['is_active'] = $isActive;
                $hasOtherData = true;
            }
            
            // Nếu có name/slug, luôn cập nhật meta_canonical
            if (!empty($name)) {
                $data['meta_canonical'] = $computedCanonical;
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
                        'sheet' => 'products',
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
                
                $processedCount++;
            } catch (\Exception $e) {
                $errorCount++;
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
        $sheet = $spreadsheet->getSheetByName('images');
        if (! $sheet) {
            return;
        } // Sheet tùy chọn

        $rows = $sheet->toArray();
        $headers = array_shift($rows);

        $imageMap = []; // image_key => image_id
        $productImageMap = []; // sku => [image_id1, image_id2, ...]

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

            if (empty($imageKey) || empty($url)) {
                continue;
            }

            // Extract image ID from image_key (IMG123 -> 123)
            $imageId = null;
            if (preg_match('/^IMG(\d+)$/i', $imageKey, $matches)) {
                $imageId = (int) $matches[1];
            }

            if ($imageId) {
                // Update existing image
                $image = Image::find($imageId);
                if ($image) {
                    $image->update([
                        'url' => $url,
                        'title' => $title ?: null,
                        'notes' => $notes ?: null,
                        'alt' => $alt ?: null,
                        'is_primary' => $isPrimary,
                        'order' => $order,
                    ]);
                    $imageMap[$imageKey] = $image->id;
                } else {
                    // Create new image
                    $image = Image::create([
                        'url' => $url,
                        'title' => $title ?: null,
                        'notes' => $notes ?: null,
                        'alt' => $alt ?: null,
                        'is_primary' => $isPrimary,
                        'order' => $order,
                    ]);
                    $imageMap[$imageKey] = $image->id;
                }
            } else {
                // Create new image without ID
                $image = Image::create([
                    'url' => $url,
                    'title' => $title ?: null,
                    'notes' => $notes ?: null,
                    'alt' => $alt ?: null,
                    'is_primary' => $isPrimary,
                    'order' => $order,
                ]);
                $imageMap[$imageKey] = $image->id;
            }

            // If SKU is provided, add to product image map
            if (! empty($sku)) {
                $finalImageId = $imageMap[$imageKey] ?? $image->id;
                if (! isset($productImageMap[$sku])) {
                    $productImageMap[$sku] = [];
                }
                $productImageMap[$sku][] = $finalImageId;
            }
        }

        // Cập nhật image_ids cho products từ SKU trong sheet images
        foreach ($productImageMap as $sku => $imageIds) {
            $product = Product::where('sku', $sku)->first();
            if ($product) {
                $oldImageIds = $product->image_ids ?? [];
                $newImageIds = array_unique($imageIds);

                // So sánh image_ids cũ và mới
                $oldArray = is_array($oldImageIds) ? $oldImageIds : [];
                $newArray = is_array($newImageIds) ? $newImageIds : [];
                sort($oldArray);
                sort($newArray);

                // Chỉ update nếu có thay đổi
                if ($oldArray !== $newArray) {
                    $product->update(['image_ids' => $newImageIds]);
                    // Xóa cache vì image_ids đã thay đổi
                    Cache::forget('product_detail_'.$product->slug);
                    Cache::forget('slug_type_'.$product->slug);
                }
            } else {
                $errors[] = [
                    'type' => 'PRODUCT_NOT_FOUND',
                    'sku' => $sku,
                    'message' => "Không tìm thấy sản phẩm với SKU '{$sku}' trong sheet images. Đã bỏ qua ảnh này.",
                    'row' => null,
                    'sheet' => 'images',
                ];
            }
        }

        // Fallback: Cập nhật image_ids từ sheet products (nếu không có SKU trong sheet images)
        if (empty($productImageMap)) {
            $sheet = $spreadsheet->getSheetByName('products');
            if ($sheet) {
                $rows = $sheet->toArray();
                array_shift($rows); // Bỏ header

                foreach ($rows as $row) {
                    if (empty($row[0])) {
                        continue;
                    }
                    $sku = trim($row[0] ?? '');
                    // Index 17 vì đã thêm brand_slug vào vị trí 14 (sau primary_category_slug)
                    $imageIdsRaw = trim($row[17] ?? '');

                    if (empty($sku) || empty($imageIdsRaw)) {
                        continue;
                    }

                    $product = Product::where('sku', $sku)->first();
                    if (! $product) {
                        continue;
                    }

                    // Parse image_ids: IMG1,IMG2,IMG3 -> [1,2,3]
                    $imageKeys = array_map('trim', explode(',', $imageIdsRaw));
                    $imageIds = [];
                    foreach ($imageKeys as $imageKey) {
                        if (isset($imageMap[$imageKey])) {
                            $imageIds[] = $imageMap[$imageKey];
                        } elseif (preg_match('/^IMG(\d+)$/i', $imageKey, $matches)) {
                            $imageIds[] = (int) $matches[1];
                        }
                    }

                    if (! empty($imageIds)) {
                        $oldImageIds = $product->image_ids ?? [];
                        $newImageIds = array_unique($imageIds);

                        // So sánh image_ids cũ và mới
                        $oldArray = is_array($oldImageIds) ? $oldImageIds : [];
                        $newArray = is_array($newImageIds) ? $newImageIds : [];
                        sort($oldArray);
                        sort($newArray);

                        // Chỉ update nếu có thay đổi
                        if ($oldArray !== $newArray) {
                            $product->update(['image_ids' => $newImageIds]);
                            // Xóa cache vì image_ids đã thay đổi
                            Cache::forget('product_detail_'.$product->slug);
                            Cache::forget('slug_type_'.$product->slug);
                        }
                    }
                }
            }
        }
    }

    /**
     * Import FAQs
     */
    private function importFaqs($spreadsheet, &$errors)
    {
        $sheet = $spreadsheet->getSheetByName('faqs');
        if (! $sheet) {
            return;
        } // Sheet tùy chọn

        $rows = $sheet->toArray();
        $headers = array_shift($rows);

        foreach ($rows as $rowIndex => $row) {
            if (empty($row[0])) {
                continue;
            }

            $sku = trim($row[0] ?? '');
            $question = trim($row[1] ?? '');
            $answer = trim($row[2] ?? '');
            $order = (int) ($row[3] ?? 0);

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
                    'sheet' => 'faqs',
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
        $sheet = $spreadsheet->getSheetByName('variants');
        if (! $sheet) {
            // Không có sheet variants thì bỏ qua (giữ logic cũ)
            return;
        }

        $rows = $sheet->toArray();
        $headers = array_shift($rows);

        // Map header -> index
        $headerIndex = [];
        foreach ($headers as $index => $header) {
            $headerIndex[strtolower(trim($header))] = $index;
        }

        $requiredCols = ['product_sku', 'variant_name'];
        foreach ($requiredCols as $col) {
            if (! array_key_exists($col, $headerIndex)) {
                throw new \Exception("Sheet \"variants\" thiếu cột bắt buộc: {$col}");
            }
        }

        $processed = []; // product_id => [variant_ids_kept]

        foreach ($rows as $rowIndex => $row) {
            $rowNumber = $rowIndex + 2; // +2 vì header ở dòng 1

            $productSku = trim((string) ($row[$headerIndex['product_sku']] ?? ''));
            $variantName = trim((string) ($row[$headerIndex['variant_name']] ?? ''));
            $variantSku = array_key_exists('variant_sku', $headerIndex) ? trim((string) ($row[$headerIndex['variant_sku']] ?? '')) : null;
            $price = (float) ($row[$headerIndex['price']] ?? 0);
            $salePrice = array_key_exists('sale_price', $headerIndex) ? $row[$headerIndex['sale_price']] : null;
            $costPrice = array_key_exists('cost_price', $headerIndex) ? $row[$headerIndex['cost_price']] : null;
            $stockQuantity = array_key_exists('stock_quantity', $headerIndex) ? $row[$headerIndex['stock_quantity']] : null;
            $imageId = array_key_exists('image_id', $headerIndex) ? $row[$headerIndex['image_id']] : null;
            $attributesJson = array_key_exists('attributes_json', $headerIndex) ? $row[$headerIndex['attributes_json']] : null;
            $isActive = array_key_exists('is_active', $headerIndex) ? $row[$headerIndex['is_active']] : 1;
            $sortOrder = array_key_exists('sort_order', $headerIndex) ? (int) $row[$headerIndex['sort_order']] : 0;

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
                    'sheet' => 'variants',
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
                        'sheet' => 'variants',
                    ];
                }
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
                'cost_price' => $costPrice !== null && $costPrice !== '' ? (float) $costPrice : null,
                'stock_quantity' => $stockQuantity !== null && $stockQuantity !== '' ? (int) $stockQuantity : null,
                'image_id' => $imageId && is_numeric($imageId) ? (int) $imageId : null,
                'attributes' => $attributes,
                'is_active' => (bool) $isActive,
                'sort_order' => $sortOrder,
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
        $sheet = $spreadsheet->getSheetByName('how_tos');
        if (! $sheet) {
            return;
        } // Sheet tùy chọn

        $rows = $sheet->toArray();
        $headers = array_shift($rows);

        foreach ($rows as $rowIndex => $row) {
            if (empty($row[0])) {
                continue;
            }

            $sku = trim($row[0] ?? '');
            $title = trim($row[1] ?? '');
            $description = trim($row[2] ?? '');
            $stepsRaw = trim($row[3] ?? '');
            $suppliesRaw = trim($row[4] ?? '');
            $isActive = isset($row[5]) ? (bool) $row[5] : true;

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
                    'sheet' => 'how_tos',
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

            // Nếu > 20k sản phẩm, dùng Job export (tránh OOM)
            $useJobExport = $totalProducts > 20000;
            
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
            $headers = [
                'sku', 'name', 'slug', 'description', 'short_description',
                'price', 'sale_price', 'cost_price', 'stock_quantity',
                'meta_title', 'meta_description', 'meta_keywords',
                'meta_canonical', 'primary_category_slug', 'brand_slug', 'category_slugs', 'tag_slugs',
                'image_ids', 'link_catalog', 'is_featured', 'is_active', 'created_by',
            ];
            
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
                        'category_ids', 'tag_ids', 'image_ids', 'link_catalog',
                        'is_featured', 'is_active', 'created_by',
                    ])
                    ->orderBy('id')
                    ->chunkById(100, function ($productsChunk) use ($writer, $categoryMap, $brandMap, $tagMap, &$productIdToSku) {
                    foreach ($productsChunk as $p) {
                        $primarySlug = $p->primary_category_id ? ($categoryMap[$p->primary_category_id] ?? null) : null;
                        $brandSlug = $p->brand_id ? ($brandMap[$p->brand_id] ?? null) : null;

                        $categorySlugs = '';
                        if (!empty($p->category_ids) && is_array($p->category_ids)) {
                            $slugs = array_map(function ($id) use ($categoryMap) {
                                return $categoryMap[$id] ?? null;
                            }, $p->category_ids);
                            $categorySlugs = implode(',', array_filter($slugs));
                        }

                        $tagNames = '';
                        if (!empty($p->tag_ids) && is_array($p->tag_ids)) {
                            $names = array_map(function ($id) use ($tagMap) {
                                return $tagMap[$id] ?? null;
                            }, $p->tag_ids);
                            $tagNames = implode(',', array_filter($names));
                        }

                        $imageIds = '';
                        if (!empty($p->image_ids) && is_array($p->image_ids)) {
                            $imageIds = implode(',', array_map(fn ($id) => 'IMG'.$id, $p->image_ids));
                        }

                        $linkCatalog = '';
                        if (!empty($p->link_catalog) && is_array($p->link_catalog)) {
                            $linkCatalog = implode(',', $p->link_catalog);
                        } elseif (is_string($p->link_catalog)) {
                            $linkCatalog = $p->link_catalog;
                        }

                        $metaKeywords = is_array($p->meta_keywords) ? implode(',', $p->meta_keywords) : ($p->meta_keywords ?? '');

                        // Ghi row với OpenSpout - streaming thật
                        $rowValues = [
                            $p->sku,
                            $p->name,
                            $p->slug,
                            $p->description,
                            $p->short_description,
                            $p->price,
                            $p->sale_price,
                            $p->cost_price,
                            $p->stock_quantity,
                            $p->meta_title,
                            $p->meta_description,
                            $metaKeywords,
                            $p->meta_canonical,
                            $primarySlug,
                            $brandSlug,
                            $categorySlugs,
                            $tagNames,
                            $imageIds,
                            $linkCatalog,
                            $p->is_featured ? 1 : 0,
                            $p->is_active ? 1 : 0,
                            $p->created_by,
                        ];
                        
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
            $imagesSheet->setName('images');
            
            $imagesHeaders = ['sku', 'image_key', 'url', 'title', 'notes', 'alt', 'is_primary', 'order'];
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
                
                Product::whereIn('id', $productIds)
                ->select(['id', 'sku', 'image_ids'])
                ->orderBy('id')
                ->chunkById(200, function ($productsChunk) use ($writer, $productIdToSku) {
                    $imageIdMap = [];
                    $allImageIds = [];

                    foreach ($productsChunk as $p) {
                        if (!empty($p->image_ids) && is_array($p->image_ids)) {
                            $imageIdMap[$p->id] = $p->image_ids;
                            $allImageIds = array_merge($allImageIds, $p->image_ids);
                        }
                    }

                    if (!empty($allImageIds)) {
                        $images = Image::whereIn('id', array_unique($allImageIds))->get()->keyBy('id');

                        foreach ($productsChunk as $p) {
                            $ids = $imageIdMap[$p->id] ?? [];
                            foreach ($ids as $imgId) {
                                /** @var Image|null $img */
                                $img = $images->get($imgId);
                                if (! $img) {
                                    continue;
                                }

                                $rowValues = [
                                    $p->sku ?? '',
                                    'IMG'.$img->id,
                                    $img->url,
                                    $img->title,
                                    $img->notes,
                                    $img->alt,
                                    $img->is_primary ? 1 : 0,
                                    $img->order,
                                ];
                                
                                $rowCells = array_map(fn($value) => Cell::fromValue($value), $rowValues);
                                $writer->addRow(new Row($rowCells));
                            }
                        }

                        unset($images);
                    }

                    unset($productsChunk, $imageIdMap, $allImageIds);
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
            $faqsSheet->setName('faqs');
            
            $faqsHeaders = ['sku', 'question', 'answer', 'order'];
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
            $howTosSheet->setName('how_tos');
            
            $howTosHeaders = ['sku', 'title', 'description', 'steps', 'supplies', 'is_active'];
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
            $variantsSheet->setName('variants');
            
            $variantsHeaders = [
                'product_sku',
                'variant_name',
                'variant_sku',
                'price',
                'sale_price',
                'cost_price',
                'stock_quantity',
                'image_id',
                'attributes_json',
                'is_active',
                'sort_order',
            ];
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

                        $attributesJson = is_array($variant->attributes) || is_object($variant->attributes)
                            ? json_encode($variant->attributes, JSON_UNESCAPED_UNICODE)
                            : ($variant->attributes ?? '');

                        $rowValues = [
                            $sku,
                            $variant->name,
                            $variant->sku,
                            $variant->price,
                            $variant->sale_price,
                            $variant->cost_price,
                            $variant->stock_quantity,
                            $variant->image_id,
                            $attributesJson,
                            $variant->is_active ? 1 : 0,
                            $variant->sort_order,
                        ];
                        
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
            'excel_file' => 'required|file|mimes:xlsx,xls|max:10240', // max 10MB
            'workers' => 'nullable|integer|min:1|max:10', // Số luồng xử lý song song (1-10)
        ]);

        try {
            $file = $request->file('excel_file');
            $workers = (int) ($request->input('workers', 10)); // Mặc định 10 workers
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
            
            // Load spreadsheet để đếm số dòng
            $spreadsheet = IOFactory::load($tempFilePath);
            $sheet = $spreadsheet->getSheetByName('products');
            
            $totalRows = 0;
            if ($sheet) {
                $rows = $sheet->toArray();
                array_shift($rows); // Bỏ header
                
                $validRows = array_filter($rows, function($row) {
                    return !empty($row[0]); // Có SKU
                });
                $totalRows = count($validRows);
            } else {
                Log::warning('Import start: Sheet products không tồn tại', [
                    'available_sheets' => $spreadsheet->getSheetNames(),
                ]);
            }
            
            // Tính số dòng cho mỗi worker
            $rowsPerWorker = (int) ceil($totalRows / $workers);
            
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
                
                // Tính toán phạm vi dòng cho worker này
                $startRow = $i * $rowsPerWorker;
                $endRow = min(($i + 1) * $rowsPerWorker, $totalRows);
                $assignedRows = $endRow - $startRow;
                
                $cacheData = [
                    'group_id' => $groupId,
                    'worker_index' => $i,
                    'total_workers' => $workers,
                    'file_path' => $tempFilePath,
                    'total_rows' => $totalRows,
                    'assigned_rows' => $assignedRows,
                    'start_row_index' => $startRow,
                    'end_row_index' => $endRow,
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

            $spreadsheet = IOFactory::load($importData['file_path']);
            $sheet = $spreadsheet->getSheetByName('products');
            
            if (!$sheet) {
                Log::error('Import chunk: Sheet products không tồn tại', [
                    'available_sheets' => $spreadsheet->getSheetNames(),
                ]);
                throw new \Exception('Sheet "products" không tồn tại!');
            }

            $rows = $sheet->toArray();
            $headers = array_shift($rows);
            
            // Lọc các dòng có SKU
            $validRows = array_filter($rows, function($row) {
                return !empty($row[0]); // Có SKU
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
            $endIndex = $startIndex + $chunkSize;
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
            'session_id' => 'required|string',
        ]);

        $sessionId = $request->input('session_id');
        $cacheKey = "import_{$sessionId}";
        $importData = Cache::get($cacheKey);

        if ($importData) {
            Cache::put($cacheKey, array_merge($importData, [
                'status' => 'cancelled',
            ]), now()->addHours(2));

            // Xóa file tạm nếu có
            $this->cleanupImportFiles($sessionId);
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

        if (isset($importData['group_id'])) {
            $groupId = $importData['group_id'];
            $groupKey = "import_group_{$groupId}";
            $groupData = Cache::get($groupKey);

            if ($groupData) {
                $status = $groupData['status'] ?? $status;
                $total = $groupData['total_rows'] ?? $total; // Lấy total từ group (chỉ 1 giá trị)
                
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
