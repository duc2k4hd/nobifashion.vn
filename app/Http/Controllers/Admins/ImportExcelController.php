<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Image;
use App\Models\ProductVariant;
use App\Models\ProductFaq;
use App\Models\ProductHowTo;
use App\Models\Category;
use App\Models\Setting;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

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
     * Export toàn bộ sản phẩm ra file Excel template
     */
    public function export()
    {
        $products = Product::with([
            'primaryCategory',
            'images',
            'variants',
            'faqs',
            'howTos'
        ])->get();

        $categoryMap = Category::pluck('slug', 'id')->toArray();
        $tagMap = Tag::pluck('name', 'id')->toArray();

        $spreadsheet = new Spreadsheet();

        $this->buildProductsSheet($spreadsheet, $products, $categoryMap, $tagMap);
        $this->buildImagesSheet($spreadsheet, $products);
        $this->buildVariantsSheet($spreadsheet, $products);
        $this->buildFaqsSheet($spreadsheet, $products);
        $this->buildHowTosSheet($spreadsheet, $products);

        $fileName = 'nobi_products_export_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        $tempDir = storage_path('app/tmp');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        $fullPath = $tempDir . '/' . $fileName;

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($fullPath);

        return response()->download($fullPath, $fileName)->deleteFileAfterSend(true);
    }

    /**
     * Xử lý import Excel
     */
    public function import(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls|max:10240', // max 10MB
        ]);

        // Mảng lưu tất cả lỗi
        $errors = [];

        try {
            $file = $request->file('excel_file');
            $spreadsheet = IOFactory::load($file->getRealPath());
            
            // Maps để lưu quan hệ
            $productMap = []; // sku => product_id
            $imageMap = [];   // image_key => image_id
            $categoryMap = []; // slug => category_id

            DB::beginTransaction();

            // STEP 1: Insert Products (Sheet 1)
            // Note: Categories phải được tạo trước trong hệ thống, chỉ cần điền slug vào Excel
            $this->importProducts($spreadsheet, $productMap, $categoryMap, $errors);

            // STEP 2: Insert Images (Sheet 2)
            $this->importImages($spreadsheet, $productMap, $imageMap, $errors);

            // STEP 3: Insert Product Variants (Sheet 3)
            $this->importVariants($spreadsheet, $productMap, $imageMap, $errors);

            // STEP 4: Insert FAQs (Sheet 4)
            $this->importFaqs($spreadsheet, $productMap, $errors);

            // STEP 5: Insert How-Tos (Sheet 5)
            $this->importHowTos($spreadsheet, $productMap, $errors);

            DB::commit();

            // Ghi log lỗi vào file txt
            $logFile = $this->writeErrorLog($errors, $file->getClientOriginalName());

            $message = 'Import thành công!';
            if (!empty($errors)) {
                $message .= ' Có ' . count($errors) . ' lỗi đã được ghi vào file log.';
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
                ->with('error', 'Lỗi import: ' . $e->getMessage())
                ->with('log_file', $logFile);
        }
    }

    /**
     * Import Products (Sheet 1)
     */
    private function importProducts($spreadsheet, &$productMap, $categoryMap, &$errors)
    {
        $sheet = $spreadsheet->getSheetByName('products');
        if (!$sheet) {
            throw new \Exception('Sheet "products" không tồn tại!');
        }

        $rows = $sheet->toArray();
        $headers = array_shift($rows);

        foreach ($rows as $rowIndex => $row) {
            if (empty($row[0])) continue; // Bỏ qua dòng trống (SKU rỗng)

            $sku = trim($row[0] ?? '');
            $name = trim($row[1] ?? '');
            $slug = trim($row[2] ?? '') ?: Str::slug($name);
            $description = trim($row[3] ?? '');
            $shortDescription = trim($row[4] ?? '');
            $price = (float)($row[5] ?? 0);
            $salePrice = !empty($row[6]) ? (float)$row[6] : null;
            $costPrice = !empty($row[7]) ? (float)$row[7] : null;
            $stockQuantity = (int)($row[8] ?? 0);
            $metaTitle = trim($row[9] ?? '');
            $metaDescription = trim($row[10] ?? '');
            // meta_keywords (Excel dạng: keyword1, keyword2, keyword3)
            $metaKeywordsRaw = trim($row[11] ?? '');
            $metaKeywords = null;

            if (!empty($metaKeywordsRaw)) {
                // Tách theo dấu phẩy → trim → bỏ rỗng
                $metaKeywords = array_filter(array_map('trim', explode(',', $metaKeywordsRaw)));
            }
            $metaCanonical = trim($row[12] ?? '');
            $primaryCategorySlug = trim($row[13] ?? '');
            $categorySlugs = trim($row[14] ?? '');
            $tagSlugs = trim($row[15] ?? '');
            $isFeatured = isset($row[16]) ? (bool)$row[16] : false;
            $hasVariants = isset($row[17]) ? (bool)$row[17] : false;
            $createdBy = (int)($row[18] ?? (Auth::check() ? Auth::id() : 1));
            $isActive = isset($row[19]) ? (bool)$row[19] : true;

            if (empty($name)) continue;

            // Debug: Log giá trị đọc được từ Excel để kiểm tra
            if (!empty($primaryCategorySlug)) {
                Log::debug("Import Product", [
                    'sku' => $sku,
                    'row' => $rowIndex + 2,
                    'primary_category_slug_from_excel' => $primaryCategorySlug,
                    'category_slugs_from_excel' => $categorySlugs,
                ]);
            }

            // Xử lý primary_category_id - CHỈ DÙNG NẾU ĐÃ TỒN TẠI
            $primaryCategoryId = null;
            if (!empty($primaryCategorySlug)) {
                // Trim và loại bỏ khoảng trắng, ký tự đặc biệt
                $primaryCategorySlug = trim($primaryCategorySlug);
                // Loại bỏ các ký tự không hợp lệ (nếu có)
                $primaryCategorySlug = preg_replace('/[^\w\-]/', '', $primaryCategorySlug);
                
                if (isset($categoryMap[$primaryCategorySlug])) {
                    $primaryCategoryId = $categoryMap[$primaryCategorySlug];
                } else {
                    // Query chính xác với slug (không dùng LIKE, phải exact match)
                    // Sử dụng DB::table để đảm bảo không có scope nào can thiệp
                    $cat = DB::table('categories')
                        ->where('slug', '=', $primaryCategorySlug)
                        ->first();
                    
                    if ($cat) {
                        $primaryCategoryId = $cat->id;
                        $categoryMap[$primaryCategorySlug] = $cat->id;
                    } else {
                        // Category không tồn tại - log lỗi và bỏ qua
                        $errors[] = [
                            'type' => 'PRIMARY_CATEGORY_NOT_FOUND',
                            'sku' => $sku ?: 'N/A',
                            'category_slug' => $primaryCategorySlug,
                            'message' => "Primary category với slug '{$primaryCategorySlug}' không tồn tại trong hệ thống. Đã bỏ qua primary category.",
                            'row' => $rowIndex + 2,
                            'sheet' => 'products',
                        ];
                    }
                }
            }

            // Xử lý category_ids (JSON array) - CHỈ DÙNG NẾU ĐÃ TỒN TẠI
            $categoryIds = [];
            if (!empty($categorySlugs)) {
                $categorySlugArray = array_map('trim', explode(',', $categorySlugs));
                foreach ($categorySlugArray as $catSlug) {
                    if (empty($catSlug)) continue;
                    
                    // Trim lại để chắc chắn
                    $catSlug = trim($catSlug);
                    
                    if (isset($categoryMap[$catSlug])) {
                        $categoryIds[] = $categoryMap[$catSlug];
                    } else {
                        // Query chính xác với slug (không dùng LIKE, phải exact match)
                        // Sử dụng DB::table để đảm bảo không có scope nào can thiệp
                        $cat = DB::table('categories')
                            ->where('slug', '=', $catSlug)
                            ->first();
                        
                        if ($cat) {
                            $categoryIds[] = $cat->id;
                            $categoryMap[$catSlug] = $cat->id;
                        } else {
                            // Category không tồn tại - log lỗi
                            $errors[] = [
                                'type' => 'CATEGORY_NOT_FOUND',
                                'sku' => $sku ?: 'N/A',
                                'category_slug' => $catSlug,
                                'message' => "Category với slug '{$catSlug}' trong category_slugs không tồn tại trong hệ thống. Đã bỏ qua category này.",
                                'row' => $rowIndex + 2,
                                'sheet' => 'products',
                            ];
                        }
                    }
                }
            }

            // Xử lý tag_ids (JSON array) - nhập tên tag, tự tạo slug và thêm mới nếu chưa có
            static $tagCache = [];
            $tagIds = [];
            if (!empty($tagSlugs)) {
                $tagNames = array_map('trim', explode(',', $tagSlugs));
                foreach ($tagNames as $tagName) {
                    if (empty($tagName)) continue;

                    $slugTag = Str::slug($tagName);
                    if (empty($slugTag)) continue;

                    if (isset($tagCache[$slugTag])) {
                        $tagIds[] = $tagCache[$slugTag];
                        continue;
                    }

                    $tag = Tag::where('slug', $slugTag)->first();
                    if (!$tag) {
                        $tag = Tag::create([
                            'name' => $tagName,
                            'slug' => $slugTag,
                            'is_active' => true,
                            'entity_id' => 0,
                            'entity_type' => 'product',
                        ]);
                    }

                    if ($tag) {
                        $tagCache[$slugTag] = $tag->id;
                        $tagIds[] = $tag->id;
                    }
                }
            }


            $domain_name = Setting::where('key', 'site_url')->first();
            $domain_name = $domain_name->value;

            // Tạo hoặc cập nhật product
            $product = Product::updateOrCreate(
                ['sku' => $sku ?: null],
                [
                    'name' => $name,
                    'slug' => $slug,
                    'description' => $description ?: null,
                    'short_description' => $shortDescription ?: null,
                    'price' => $price,
                    'sale_price' => $salePrice,
                    'cost_price' => $costPrice,
                    'stock_quantity' => $stockQuantity,
                    'meta_title' => $metaTitle ?: null,
                    'meta_description' => $metaDescription ?: null,
                    'meta_keywords' => $metaKeywords, // để null hoặc array đều hợp lệ
                    'meta_canonical' => $metaCanonical ?: $domain_name . '/san-pham/' . $slug,
                    'primary_category_id' => $primaryCategoryId,
                    'category_ids' => !empty($categoryIds) ? $categoryIds : null,
                    'tag_ids' => !empty($tagIds) ? $tagIds : null,
                    'is_featured' => $isFeatured,
                    'has_variants' => $hasVariants,
                    'created_by' => $createdBy,
                    'is_active' => $isActive,
                ]
            );

            $productMap[$sku ?: $product->id] = $product->id;
        }
    }

    /**
     * Import Images (Sheet 2)
     */
    private function importImages($spreadsheet, $productMap, &$imageMap, &$errors)
    {
        // XÓA TOÀN BỘ ẢNH CŨ CỦA SẢN PHẨM TRƯỚC KHI IMPORT MỚI
        foreach ($productMap as $sku => $productId) {
            $oldImages = Image::where('product_id', $productId)->get();

            foreach ($oldImages as $img) {
                // Xóa file
                $filePath = public_path('clients/assets/img/clothes/' . $img->url);
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }

                // Xóa record DB
                $img->delete();
            }
        }
        $sheet = $spreadsheet->getSheetByName('images');
        if (!$sheet) return; // Sheet tùy chọn

        $rows = $sheet->toArray();
        $headers = array_shift($rows);

        foreach ($rows as $rowIndex => $row) {
            if (empty($row[0])) continue; // Bỏ qua dòng trống

            $sku = trim($row[0] ?? '');
            $imageKey = trim($row[1] ?? '');
            $localPath = trim($row[2] ?? '');
            $title = trim($row[3] ?? '');
            $notes = trim($row[4] ?? '');
            $alt = trim($row[5] ?? '');
            $isPrimary = isset($row[6]) ? (bool)$row[6] : false;
            $order = (int)($row[7] ?? 0);

            if (empty($sku) || empty($imageKey) || empty($localPath)) continue;

            // Tìm product_id từ sku
            $productId = $productMap[$sku] ?? null;
            if (!$productId) {
                $product = Product::where('sku', $sku)->first();
                if (!$product) {
                    $errors[] = [
                        'type' => 'PRODUCT_NOT_FOUND',
                        'sku' => $sku,
                        'image_key' => $imageKey,
                        'message' => "Không tìm thấy sản phẩm với SKU '{$sku}'. Đã bỏ qua ảnh này.",
                        'row' => $rowIndex + 2,
                        'sheet' => 'images',
                    ];
                    continue;
                }
                $productId = $product->id;
                $productMap[$sku] = $productId;
            }

            // Upload ảnh từ local_path (copy từ imports sang clothes)
            $filename = $this->uploadImage($localPath);
            if (!$filename) {
                $errors[] = [
                    'type' => 'IMAGE_UPLOAD_FAILED',
                    'sku' => $sku,
                    'image_key' => $imageKey,
                    'local_path' => $localPath,
                    'message' => "Không thể upload ảnh từ đường dẫn '{$localPath}'. File không tồn tại hoặc không hợp lệ.",
                    'row' => $rowIndex + 2,
                    'sheet' => 'images',
                ];
                continue;
            }

            // Update hoặc tạo image - dùng product_id + order để identify (thay vì url)
            // Logic: Nếu cùng product_id và order → UPDATE (thay thế ảnh cũ), không tạo mới
            $existingImageId = null;
            $oldImageUrl = null;
            
            if (isset($imageMap[$imageKey])) {
                // Nếu image_key đã tồn tại trong map (từ lần import trước trong cùng session)
                $existingImageId = $imageMap[$imageKey];
                $existingImage = Image::find($existingImageId);
                if ($existingImage) {
                    $oldImageUrl = $existingImage->url;
                }
            } else {
                // Tìm image đã tồn tại với cùng product_id và order
                $existingImage = Image::where('product_id', $productId)
                    ->where('order', $order)
                    ->first();
                if ($existingImage) {
                    $existingImageId = $existingImage->id;
                    $oldImageUrl = $existingImage->url;
                }
            }

            if ($existingImageId) {
                // Update image đã tồn tại - thay thế ảnh cũ bằng ảnh mới
                Image::where('id', $existingImageId)->update([
                    'title' => $title ?: null,
                    'notes' => $notes ?: null,
                    'alt' => $alt ?: null,
                    'url' => $filename, // Thay thế tên file mới
                    'thumbnail_url' => $filename,
                    'medium_url' => $filename,
                    'is_primary' => $isPrimary,
                    'order' => $order,
                ]);
                
                // Xóa file ảnh cũ nếu tên file khác (để tránh lãng phí dung lượng)
                if ($oldImageUrl && $oldImageUrl !== $filename) {
                    $oldImagePath = public_path('clients/assets/img/clothes/' . $oldImageUrl);
                    if (file_exists($oldImagePath)) {
                        @unlink($oldImagePath); // @ để tránh lỗi nếu file không tồn tại
                    }
                }
                
                $imageMap[$imageKey] = $existingImageId;
            } else {
                // Tạo mới image
                $image = Image::create([
                    'product_id' => $productId,
                    'title' => $title ?: null,
                    'notes' => $notes ?: null,
                    'alt' => $alt ?: null,
                    'url' => $filename,
                    'thumbnail_url' => $filename,
                    'medium_url' => $filename,
                    'is_primary' => $isPrimary,
                    'order' => $order,
                ]);
                $imageMap[$imageKey] = $image->id;
            }
        }
    }

    /**
     * Import Product Variants (Sheet 3)
     */
    private function importVariants($spreadsheet, $productMap, $imageMap, &$errors)
    {
        $sheet = $spreadsheet->getSheetByName('product_variants');
        if (!$sheet) return; // Sheet tùy chọn

        $rows = $sheet->toArray();
        $headers = array_shift($rows);

        $normalizedHeaders = [];
        $columnIndexes = [
            'sku' => null,
            'price' => null,
            'stock' => null,
            'image_key' => null,
        ];
        $attributeColumns = [];

        foreach ($headers as $colIndex => $header) {
            $raw = trim((string)$header);
            $rawLower = Str::lower($raw);
            $key = Str::slug($raw, '_');

            if ($key === '') {
                $normalizedHeaders[$colIndex] = null;
                continue;
            }

            $normalizedHeaders[$colIndex] = $key;

            // Đầu tiên: nhận diện cột thuộc tính động
            // - attribute_size, attributes_weight, attribute_color, ...
            if (preg_match('/^attributes?_(.*)$/', $rawLower, $m)) {
                $attrKey = trim($m[1], "_ \t\n\r\0\x0B");
                if ($attrKey === '') {
                    // Nếu không có hậu tố, đặt tên theo thứ tự: attr_1, attr_2, ...
                    $attrKey = 'attr_' . (count($attributeColumns) + 1);
                }
                $attrKey = Str::slug($attrKey, '_');
                if ($attrKey !== '') {
                    $attributeColumns[$colIndex] = $attrKey;
                }
                continue;
            }

            // Backward compatibility: cột 'color', 'size' cũ cũng coi là thuộc tính
            if (in_array($key, ['color', 'size'], true)) {
                $attributeColumns[$colIndex] = $key;
                continue;
            }

            // Cuối cùng mới map các cột cố định sku/price/stock/image_key
            if (array_key_exists($key, $columnIndexes)) {
                $columnIndexes[$key] = $colIndex;
            }
        }

        // Nếu không tìm được cột attribute_* nào thì fallback về template cũ:
        // cột 3 = color, cột 4 = size (nếu tồn tại)
        if (empty($attributeColumns)) {
            if (isset($headers[3]) && trim((string)$headers[3]) !== '') {
                $attributeColumns[3] = 'color';
            }
            if (isset($headers[4]) && trim((string)$headers[4]) !== '') {
                $attributeColumns[4] = 'size';
            }
        }

        // Xác định vị trí image_key nếu header không đặt đúng tên
        if ($columnIndexes['image_key'] === null) {
            foreach ($normalizedHeaders as $index => $key) {
                if ($key === null) {
                    continue;
                }
                // bỏ qua các cột SKU/price/stock
                if (in_array($key, ['sku', 'price', 'stock'], true)) {
                    continue;
                }
                // bỏ qua tất cả cột attribute_*
                if (Str::startsWith($key, 'attribute_')) {
                    continue;
                }
                // cột đầu tiên còn lại sau các cột trên coi là image_key
                $columnIndexes['image_key'] = $index;
                break;
            }
        }

        // Backward compatibility default indexes nếu vẫn không tìm được
        $columnIndexes['sku'] = $columnIndexes['sku'] ?? 0;
        $columnIndexes['price'] = $columnIndexes['price'] ?? 1;
        $columnIndexes['stock'] = $columnIndexes['stock'] ?? 2;
        if ($columnIndexes['image_key'] === null && isset($headers[5])) {
            $columnIndexes['image_key'] = 5;
        }

        // Gom các dòng theo SKU để xử lý theo từng sản phẩm
        $groupedRows = [];
        foreach ($rows as $rowIndex => $row) {
            $skuValue = $row[$columnIndexes['sku']] ?? ($row[0] ?? null);
            if (empty($skuValue)) continue;
            $sku = trim((string)$skuValue);
            if (empty($sku)) continue;

            $priceValue = $row[$columnIndexes['price']] ?? null;
            $stockValue = $row[$columnIndexes['stock']] ?? null;
            $imageKeyValue = $row[$columnIndexes['image_key']] ?? null;

            $attributes = [];
            foreach ($attributeColumns as $attrColumnIndex => $attrKey) {
                $attrValue = $row[$attrColumnIndex] ?? null;
                if (is_null($attrValue)) {
                    continue;
                }
                $attrValue = trim((string)$attrValue);
                if ($attrValue === '') {
                    continue;
                }
                $attributes[$attrKey] = $attrValue;
            }

            $groupedRows[$sku][] = [
                'row_index' => $rowIndex,
                'price' => isset($priceValue) && $priceValue !== '' ? (float)$priceValue : null,
                'stock' => isset($stockValue) ? (int)$stockValue : 0,
                'attributes' => $attributes,
                'image_key' => trim((string)$imageKeyValue),
            ];
        }

        foreach ($groupedRows as $sku => $variantRows) {
            $productId = $productMap[$sku] ?? null;
            if (!$productId) {
                $product = Product::where('sku', $sku)->first();
                if (!$product) {
                    foreach ($variantRows as $rowData) {
                        $errors[] = [
                            'type' => 'PRODUCT_NOT_FOUND',
                            'sku' => $sku,
                            'message' => "Không tìm thấy sản phẩm với SKU '{$sku}'. Đã bỏ qua variant này.",
                            'row' => $rowData['row_index'] + 2,
                            'sheet' => 'product_variants',
                        ];
                    }
                    continue;
                }
                $productId = $product->id;
                $productMap[$sku] = $productId;
            }

            // Lấy danh sách variant hiện tại của sản phẩm (giữ nguyên thứ tự theo id)
            $existingVariants = ProductVariant::where('product_id', $productId)
                ->orderBy('id')
                ->get();

            $keptVariantIds = [];

            foreach ($variantRows as $index => $rowData) {
                $attributes = $rowData['attributes'] ?? [];

                // Map image_key → image_id
                $imageId = null;
                if (!empty($rowData['image_key'])) {
                    if (isset($imageMap[$rowData['image_key']])) {
                        $imageId = $imageMap[$rowData['image_key']];
                    } else {
                        $errors[] = [
                            'type' => 'IMAGE_KEY_NOT_FOUND',
                            'sku' => $sku,
                            'image_key' => $rowData['image_key'],
                            'message' => "Image key '{$rowData['image_key']}' không tồn tại trong sheet images. Variant sẽ được tạo không có ảnh.",
                            'row' => $rowData['row_index'] + 2,
                            'sheet' => 'product_variants',
                        ];
                    }
                }

                if (isset($existingVariants[$index])) {
                    $variant = $existingVariants[$index];
                    ProductVariant::where('id', $variant->id)->update([
                        'price' => $rowData['price'],
                        'stock_quantity' => $rowData['stock'],
                        'image_id' => $imageId,
                        'attributes' => !empty($attributes) ? json_encode($attributes) : null,
                        'status' => 'active',
                        'updated_at' => now(),
                    ]);
                    $keptVariantIds[] = $variant->id;
                } else {
                    $newId = DB::table('product_variants')->insertGetId([
                        'product_id' => $productId,
                        'price' => $rowData['price'],
                        'stock_quantity' => $rowData['stock'],
                        'attributes' => !empty($attributes) ? json_encode($attributes) : null,
                        'image_id' => $imageId,
                        'status' => 'active',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $keptVariantIds[] = $newId;
                }
            }

            // Xóa các biến thể dư (những biến thể có chỉ số lớn hơn số dòng hiện có)
            if ($existingVariants->isNotEmpty()) {
                $variantIdsToDelete = $existingVariants->pluck('id')->toArray();
                if (!empty($keptVariantIds)) {
                    $variantIdsToDelete = array_diff($variantIdsToDelete, $keptVariantIds);
                }
                if (!empty($variantIdsToDelete)) {
                    ProductVariant::whereIn('id', $variantIdsToDelete)->delete();
                }
            }
        }
    }

    /**
     * Import FAQs (Sheet 4)
     */
    private function importFaqs($spreadsheet, $productMap, &$errors)
    {
        $sheet = $spreadsheet->getSheetByName('product_faqs');
        if (!$sheet) return; // Sheet tùy chọn

        $rows = $sheet->toArray();
        $headers = array_shift($rows);

        foreach ($rows as $rowIndex => $row) {
            if (empty($row[0])) continue;

            $sku = trim($row[0] ?? '');
            $question = trim($row[1] ?? '');
            $answer = trim($row[2] ?? '');
            $order = (int)($row[3] ?? 0);

            if (empty($sku) || empty($question)) continue;

            $productId = $productMap[$sku] ?? null;
            if (!$productId) {
                $product = Product::where('sku', $sku)->first();
                if (!$product) {
                    $errors[] = [
                        'type' => 'PRODUCT_NOT_FOUND',
                        'sku' => $sku,
                        'message' => "Không tìm thấy sản phẩm với SKU '{$sku}'. Đã bỏ qua FAQ này.",
                        'row' => $rowIndex + 2,
                        'sheet' => 'product_faqs',
                    ];
                    continue;
                }
                $productId = $product->id;
                $productMap[$sku] = $productId;
            }

            // Update hoặc tạo FAQ - dùng product_id + question để identify
            $existingFaq = DB::table('product_faqs')
                ->where('product_id', $productId)
                ->where('question', $question)
                ->first();

            if ($existingFaq) {
                // Update FAQ đã tồn tại
                DB::table('product_faqs')
                    ->where('id', $existingFaq->id)
                    ->update([
                        'answer' => $answer ?: null,
                        'order' => $order,
                        'updated_at' => now(),
                    ]);
            } else {
                // Tạo mới FAQ
                DB::table('product_faqs')->insert([
                    'product_id' => $productId,
                    'question' => $question,
                    'answer' => $answer ?: null,
                    'order' => $order,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Import How-Tos (Sheet 5)
     */
    private function importHowTos($spreadsheet, $productMap, &$errors)
    {
        $sheet = $spreadsheet->getSheetByName('product_how_tos');
        if (!$sheet) return; // Sheet tùy chọn

        $rows = $sheet->toArray();
        $headers = array_shift($rows);

        foreach ($rows as $rowIndex => $row) {
            if (empty($row[0])) continue;

            $sku = trim($row[0] ?? '');
            $title = trim($row[1] ?? '');
            $description = trim($row[2] ?? '');
            $steps = trim($row[3] ?? '');
            $supplies = trim($row[4] ?? '');

            if (empty($sku) || empty($title)) continue;

            $productId = $productMap[$sku] ?? null;
            if (!$productId) {
                $product = Product::where('sku', $sku)->first();
                if (!$product) {
                    $errors[] = [
                        'type' => 'PRODUCT_NOT_FOUND',
                        'sku' => $sku,
                        'message' => "Không tìm thấy sản phẩm với SKU '{$sku}'. Đã bỏ qua How-To này.",
                        'row' => $rowIndex + 2,
                        'sheet' => 'product_how_tos',
                    ];
                    continue;
                }
                $productId = $product->id;
                $productMap[$sku] = $productId;
            }

            // Xử lý steps và supplies (JSON)
            $stepsArray = null;
            if (!empty($steps)) {
                // Nếu là JSON string thì decode, nếu là text thì tách theo dòng
                $decoded = json_decode($steps, true);
                $stepsArray = $decoded ?: array_filter(array_map('trim', explode("\n", $steps)));
            }

            $suppliesArray = null;
            if (!empty($supplies)) {
                $decoded = json_decode($supplies, true);
                $suppliesArray = $decoded ?: array_filter(array_map('trim', explode(',', $supplies)));
            }

            // Update hoặc tạo How-To - dùng product_id + title để identify
            ProductHowTo::updateOrCreate(
                [
                    'product_id' => $productId,
                    'title' => $title,
                ],
                [
                    'description' => $description ?: null,
                    'steps' => $stepsArray,
                    'supplies' => $suppliesArray,
                    'is_active' => true,
                ]
            );
        }
    }

    private function buildProductsSheet(Spreadsheet $spreadsheet, $products, array $categoryMap, array $tagMap)
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('products');

        $headers = [
            'sku','name','slug','description','short_description','price','sale_price',
            'cost_price','stock_quantity','meta_title','meta_description','meta_keywords',
            'meta_canonical','primary_category_slug','category_slugs','tag_slugs',
            'is_featured','has_variants','created_by','is_active'
        ];
        $sheet->fromArray($headers, null, 'A1');

        $row = 2;
        foreach ($products as $product) {
            $primarySlug = optional($product->primaryCategory)->slug;

            $categorySlugs = '';
            if (!empty($product->category_ids)) {
                $slugs = array_map(function ($id) use ($categoryMap) {
                    return $categoryMap[$id] ?? null;
                }, $product->category_ids ?? []);
                $categorySlugs = implode(',', array_filter($slugs));
            }

            $tagNames = '';
            if (!empty($product->tag_ids)) {
                $names = array_map(function ($id) use ($tagMap) {
                    return $tagMap[$id] ?? null;
                }, $product->tag_ids ?? []);
                $tagNames = implode(', ', array_filter($names));
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
                is_array($product->meta_keywords) ? implode(',', $product->meta_keywords) : $product->meta_keywords,
                $product->meta_canonical,
                $primarySlug,
                $categorySlugs,
                $tagNames,
                $product->is_featured ? 1 : 0,
                $product->has_variants ? 1 : 0,
                $product->created_by,
                $product->is_active ? 1 : 0
            ], null, 'A' . $row);
            $row++;
        }
    }

    private function buildImagesSheet(Spreadsheet $spreadsheet, $products)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('images');

        $headers = ['sku','image_key','local_path','title','notes','alt','is_primary','order'];
        $sheet->fromArray($headers, null, 'A1');

        $row = 2;
        foreach ($products as $product) {
            foreach ($product->images as $image) {
                $sheet->fromArray([
                    $product->sku,
                    'IMG' . $image->id,
                    $image->url,
                    $image->title,
                    $image->notes,
                    $image->alt,
                    $image->is_primary ? 1 : 0,
                    $image->order
                ], null, 'A' . $row);
                $row++;
            }
        }
    }

    private function buildVariantsSheet(Spreadsheet $spreadsheet, $products)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('product_variants');

        $attributeKeyMap = [];
        foreach ($products as $product) {
            foreach ($product->variants as $variant) {
                $attributes = is_array($variant->attributes)
                    ? $variant->attributes
                    : json_decode($variant->attributes ?? '{}', true);

                if (empty($attributes) || !is_array($attributes)) {
                    continue;
                }

                foreach ($attributes as $key => $value) {
                    if ($value === null || $value === '') {
                        continue;
                    }
                    $slug = Str::slug((string) $key, '_');
                    if ($slug === '') {
                        $slug = 'attr_' . (count($attributeKeyMap) + 1);
                    }
                    if (!isset($attributeKeyMap[$slug])) {
                        $attributeKeyMap[$slug] = (string) $key;
                    }
                }
            }
        }

        if (empty($attributeKeyMap)) {
            $attributeKeyMap = [
                'color' => 'color',
                'size' => 'size',
            ];
        }

        $attributeHeaders = array_map(function ($slug) {
            return 'attributes_' . $slug;
        }, array_keys($attributeKeyMap));

        $headers = array_merge(['sku', 'price', 'stock_quantity'], $attributeHeaders, ['image_key']);
        $sheet->fromArray($headers, null, 'A1');

        $imageKeyMap = [];
        foreach ($products as $product) {
            foreach ($product->images as $image) {
                $imageKeyMap[$image->id] = 'IMG' . $image->id;
            }
        }

        $row = 2;
        foreach ($products as $product) {
            foreach ($product->variants as $variant) {
                $attributes = is_array($variant->attributes)
                    ? $variant->attributes
                    : json_decode($variant->attributes ?? '{}', true);
                $normalizedAttributes = [];
                if (is_array($attributes)) {
                    foreach ($attributes as $key => $value) {
                        $slug = Str::slug((string) $key, '_');
                        if ($slug === '') {
                            continue;
                        }
                        $normalizedAttributes[$slug] = $value;
                    }
                }

                $imageKey = $variant->image_id ? ($imageKeyMap[$variant->image_id] ?? null) : null;

                $rowData = [
                    $product->sku,
                    $variant->price,
                    $variant->stock_quantity,
                ];

                foreach (array_keys($attributeKeyMap) as $slug) {
                    $rowData[] = $normalizedAttributes[$slug] ?? '';
                }

                $rowData[] = $imageKey;

                $sheet->fromArray($rowData, null, 'A' . $row);
                $row++;
            }
        }
    }

    private function buildFaqsSheet(Spreadsheet $spreadsheet, $products)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('product_faqs');

        $headers = ['sku','question','answer','order'];
        $sheet->fromArray($headers, null, 'A1');

        $row = 2;
        foreach ($products as $product) {
            foreach ($product->faqs as $faq) {
                $sheet->fromArray([
                    $product->sku,
                    $faq->question,
                    $faq->answer,
                    $faq->order
                ], null, 'A' . $row);
                $row++;
            }
        }
    }

    private function buildHowTosSheet(Spreadsheet $spreadsheet, $products)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('product_how_tos');

        $headers = ['sku','title','description','steps','supplies'];
        $sheet->fromArray($headers, null, 'A1');

        $row = 2;
        foreach ($products as $product) {
            foreach ($product->howTos as $howTo) {
                $sheet->fromArray([
                    $product->sku,
                    $howTo->title,
                    $howTo->description,
                    !empty($howTo->steps) ? json_encode($howTo->steps, JSON_UNESCAPED_UNICODE) : null,
                    !empty($howTo->supplies) ? json_encode($howTo->supplies, JSON_UNESCAPED_UNICODE) : null,
                ], null, 'A' . $row);
                $row++;
            }
        }
    }

    /**
     * Chuẩn hóa key cho attributes variant
     */
    private function buildVariantKey(array $attributes): string
    {
        if (empty($attributes)) {
            return '[]';
        }

        $normalized = [];
        foreach ($attributes as $key => $value) {
            $normalized[mb_strtolower($key)] = mb_strtolower((string)$value);
        }

        ksort($normalized);

        return json_encode($normalized, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Upload ảnh từ local_path: copy từ imports sang clothes
     * Trả về tên file (chỉ tên file, không có đường dẫn)
     */
    private function uploadImage($localPath)
    {
        if (empty($localPath)) return null;

        // Nếu là URL thì không xử lý (trả về null để log lỗi)
        if (filter_var($localPath, FILTER_VALIDATE_URL)) {
            return null;
        }

        // Xác định đường dẫn file nguồn
        $sourcePath = null;
        
        // Nếu là đường dẫn tuyệt đối
        if (file_exists($localPath)) {
            $sourcePath = $localPath;
        } 
        // Nếu chỉ là tên file, tìm trong folder imports
        else {
            $importsPath = public_path('clients/assets/img/imports/' . basename($localPath));
            if (file_exists($importsPath)) {
                $sourcePath = $importsPath;
            }
            // Thử với đường dẫn đầy đủ trong imports
            else {
                $importsPathFull = public_path('clients/assets/img/imports/' . $localPath);
                if (file_exists($importsPathFull)) {
                    $sourcePath = $importsPathFull;
                }
            }
        }

        if (!$sourcePath || !file_exists($sourcePath)) {
            return null;
        }

        // Lấy tên file (giữ nguyên tên gốc hoặc dùng tên từ localPath)
        $filename = basename($localPath);
        if (empty($filename) || $filename === $localPath) {
            $filename = basename($sourcePath);
        }

        // Đảm bảo có extension
        if (empty(pathinfo($filename, PATHINFO_EXTENSION))) {
            $extension = pathinfo($sourcePath, PATHINFO_EXTENSION);
            if (empty($extension)) {
                $extension = 'jpg'; // Default
            }
            $filename = pathinfo($filename, PATHINFO_FILENAME) . '.' . $extension;
        }

        // Đường dẫn đích trong folder clothes
        $destination = 'clients/assets/img/clothes/' . $filename;
        $destinationPath = public_path($destination);

        // Tạo thư mục nếu chưa có
        $dir = dirname($destinationPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Copy file từ imports sang clothes
        if (copy($sourcePath, $destinationPath)) {
            return $filename; // Trả về chỉ tên file
        }

        return null;
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
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $timestamp = date('Y-m-d_H-i-s');
        $baseName = pathinfo($originalFileName, PATHINFO_FILENAME);
        $logFileName = "import_errors_{$baseName}_{$timestamp}.txt";
        $logPath = $logDir . '/' . $logFileName;

        $content = "========================================\n";
        $content .= "LOG LỖI IMPORT EXCEL\n";
        $content .= "========================================\n";
        $content .= "File Excel: {$originalFileName}\n";
        $content .= "Thời gian: " . date('Y-m-d H:i:s') . "\n";
        $content .= "Tổng số lỗi: " . count($errors) . "\n";
        $content .= "========================================\n\n";

        // Nhóm lỗi theo type
        $errorsByType = [];
        foreach ($errors as $error) {
            $type = $error['type'] ?? 'UNKNOWN';
            if (!isset($errorsByType[$type])) {
                $errorsByType[$type] = [];
            }
            $errorsByType[$type][] = $error;
        }

        // Ghi theo từng loại lỗi
        foreach ($errorsByType as $type => $typeErrors) {
            $content .= "\n" . str_repeat("=", 40) . "\n";
            $content .= "LOẠI LỖI: {$type} (" . count($typeErrors) . " lỗi)\n";
            $content .= str_repeat("=", 40) . "\n\n";

            foreach ($typeErrors as $index => $error) {
                $content .= "Lỗi #" . ($index + 1) . ":\n";
                $content .= "  - Sheet: " . ($error['sheet'] ?? 'N/A') . "\n";
                $content .= "  - Dòng: " . ($error['row'] ?? 'N/A') . "\n";
                $content .= "  - SKU: " . ($error['sku'] ?? 'N/A') . "\n";
                
                if (isset($error['category_slug'])) {
                    $content .= "  - Category Slug: " . $error['category_slug'] . "\n";
                }
                if (isset($error['image_key'])) {
                    $content .= "  - Image Key: " . $error['image_key'] . "\n";
                }
                if (isset($error['local_path'])) {
                    $content .= "  - Local Path: " . $error['local_path'] . "\n";
                }
                if (isset($error['file'])) {
                    $content .= "  - File: " . $error['file'] . "\n";
                }
                if (isset($error['line'])) {
                    $content .= "  - Line: " . $error['line'] . "\n";
                }
                
                $content .= "  - Mô tả: " . ($error['message'] ?? 'Không có mô tả') . "\n";
                $content .= "\n";
            }
        }

        // Ghi chi tiết từng lỗi
        $content .= "\n" . str_repeat("=", 40) . "\n";
        $content .= "CHI TIẾT TỪNG LỖI\n";
        $content .= str_repeat("=", 40) . "\n\n";

        foreach ($errors as $index => $error) {
            $content .= "[" . ($index + 1) . "] " . ($error['type'] ?? 'UNKNOWN') . "\n";
            $content .= "Sheet: " . ($error['sheet'] ?? 'N/A') . " | ";
            $content .= "Dòng: " . ($error['row'] ?? 'N/A') . " | ";
            $content .= "SKU: " . ($error['sku'] ?? 'N/A') . "\n";
            $content .= "Mô tả: " . ($error['message'] ?? 'Không có mô tả') . "\n";
            $content .= "\n";
        }

        file_put_contents($logPath, $content);

        return $logFileName;
    }
}

