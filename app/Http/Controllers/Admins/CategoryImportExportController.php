<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CategoryImportExportController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:web', 'admin']);
    }

    /**
     * Hiển thị trang giao diện nhập danh mục riêng biệt (Full Page Importer)
     */
    public function importForm()
    {
        return view('admins.categories.import');
    }

    /**
     * Danh sách tất cả các cột hỗ trợ xuất / nhập
     */
    public static function supportedColumns(): array
    {
        return [
            ['key' => 'ID', 'label' => 'ID danh mục', 'default_export' => true, 'default_import' => true],
            ['key' => 'Tên danh mục', 'label' => 'Tên danh mục', 'default_export' => true, 'default_import' => true],
            ['key' => 'Slug', 'label' => 'Slug (Đường dẫn)', 'default_export' => true, 'default_import' => true],
            ['key' => 'Danh mục cha (Tên)', 'label' => 'Danh mục cha (Tên)', 'default_export' => true, 'default_import' => true],
            ['key' => 'Danh mục cha (Slug)', 'label' => 'Danh mục cha (Slug)', 'default_export' => true, 'default_import' => true],
            ['key' => 'Danh mục cha (ID)', 'label' => 'Danh mục cha (ID)', 'default_export' => false, 'default_import' => false],
            ['key' => 'Đường dẫn đầy đủ', 'label' => 'Đường dẫn phân cấp (Cha > Con)', 'default_export' => false, 'default_import' => false],
            ['key' => 'Thứ tự sắp xếp', 'label' => 'Thứ tự sắp xếp (sort_order)', 'default_export' => true, 'default_import' => true],
            ['key' => 'Trạng thái', 'label' => 'Trạng thái (1: Hoạt động, 0: Tạm ẩn)', 'default_export' => true, 'default_import' => true],
            ['key' => 'Mô tả', 'label' => 'Mô tả danh mục', 'default_export' => false, 'default_import' => true],
            ['key' => 'Ảnh đại diện', 'label' => 'Ảnh đại diện (URL / Tên file)', 'default_export' => false, 'default_import' => true],
            ['key' => 'Meta Title', 'label' => 'SEO Meta Title', 'default_export' => false, 'default_import' => true],
            ['key' => 'Meta Description', 'label' => 'SEO Meta Description', 'default_export' => false, 'default_import' => true],
            ['key' => 'Meta Keywords', 'label' => 'SEO Meta Keywords', 'default_export' => false, 'default_import' => false],
            ['key' => 'Meta Canonical', 'label' => 'SEO Canonical URL', 'default_export' => false, 'default_import' => false],
            ['key' => 'Số sản phẩm', 'label' => 'Số sản phẩm (Chỉ xuất)', 'default_export' => false, 'default_import' => false],
            ['key' => 'Ngày tạo', 'label' => 'Ngày tạo (created_at)', 'default_export' => false, 'default_import' => false],
            ['key' => 'Ngày cập nhật', 'label' => 'Ngày cập nhật (updated_at)', 'default_export' => false, 'default_import' => false],
        ];
    }

    /**
     * Lấy dữ liệu danh mục để xuất qua client JS (SheetJS)
     */
    public function getExportData(Request $request): JsonResponse
    {
        $selectedColumns = $request->input('columns', ['ID', 'Tên danh mục', 'Slug', 'Danh mục cha (Tên)', 'Danh mục cha (Slug)', 'Thứ tự sắp xếp', 'Trạng thái']);
        if (!is_array($selectedColumns) || empty($selectedColumns)) {
            $selectedColumns = ['ID', 'Tên danh mục', 'Slug', 'Danh mục cha (Tên)', 'Danh mục cha (Slug)', 'Thứ tự sắp xếp', 'Trạng thái'];
        }

        $query = $this->buildFilterQuery($request);

        // Eager load quan hệ parent nếu cần
        if (
            in_array('Danh mục cha (Tên)', $selectedColumns, true) ||
            in_array('Danh mục cha (Slug)', $selectedColumns, true) ||
            in_array('Đường dẫn đầy đủ', $selectedColumns, true)
        ) {
            $query->with('parent');
        }

        if (in_array('Số sản phẩm', $selectedColumns, true)) {
            $query->withCount('primaryProducts');
        }

        $categories = $query->get();

        $data = $categories->map(function ($category) use ($selectedColumns) {
            $row = [
                'ID' => $category->id,
                'Tên danh mục' => $category->name,
                'Slug' => $category->slug,
                'Danh mục cha (Tên)' => $category->parent?->name ?? '',
                'Danh mục cha (Slug)' => $category->parent?->slug ?? '',
                'Danh mục cha (ID)' => $category->parent_id ?? '',
                'Đường dẫn đầy đủ' => $category->fullPath(),
                'Thứ tự sắp xếp' => $category->sort_order,
                'Trạng thái' => $category->is_active ? 1 : 0,
                'Mô tả' => $category->description ?? '',
                'Ảnh đại diện' => $category->image ?? '',
                'Meta Title' => $category->meta_title ?? '',
                'Meta Description' => $category->meta_description ?? '',
                'Meta Keywords' => $category->meta_keywords ?? '',
                'Meta Canonical' => $category->meta_canonical ?? '',
                'Số sản phẩm' => $category->primary_products_count ?? 0,
                'Ngày tạo' => $category->created_at ? $category->created_at->format('Y-m-d H:i:s') : '',
                'Ngày cập nhật' => $category->updated_at ? $category->updated_at->format('Y-m-d H:i:s') : '',
            ];

            // Chỉ lọc các cột được chọn
            $filtered = [];
            foreach ($selectedColumns as $col) {
                if (array_key_exists($col, $row)) {
                    $filtered[$col] = $row[$col];
                }
            }

            return $filtered;
        });

        return response()->json([
            'success' => true,
            'total' => $data->count(),
            'data' => $data,
        ]);
    }

    /**
     * Xuất danh mục dạng Direct Streaming CSV (siêu nhanh, hỗ trợ 100k+ bản ghi, 0MB RAM) hoặc XLSX
     */
    public function export(Request $request)
    {
        $selectedColumns = $request->input('columns', ['ID', 'Tên danh mục', 'Slug', 'Danh mục cha (Tên)', 'Danh mục cha (Slug)', 'Thứ tự sắp xếp', 'Trạng thái']);
        if (!is_array($selectedColumns) || empty($selectedColumns)) {
            $selectedColumns = ['ID', 'Tên danh mục', 'Slug', 'Danh mục cha (Tên)', 'Danh mục cha (Slug)', 'Thứ tự sắp xếp', 'Trạng thái'];
        }

        $format = strtolower((string) $request->input('format', 'csv'));
        if (!in_array($format, ['csv', 'xlsx'], true)) {
            $format = 'csv';
        }

        $query = $this->buildFilterQuery($request);

        if (
            in_array('Danh mục cha (Tên)', $selectedColumns, true) ||
            in_array('Danh mục cha (Slug)', $selectedColumns, true) ||
            in_array('Đường dẫn đầy đủ', $selectedColumns, true)
        ) {
            $query->with('parent');
        }

        if (in_array('Số sản phẩm', $selectedColumns, true)) {
            $query->withCount('primaryProducts');
        }

        $timestamp = date('Y-m-d_His');

        // Xuất XLSX nếu người dùng yêu cầu và số lượng vừa phải
        if ($format === 'xlsx') {
            if (!extension_loaded('zip') || !class_exists(\ZipArchive::class)) {
                $format = 'csv'; // Tự động fallback CSV
            } else {
                $filename = "danh_muc_{$timestamp}.xlsx";
                $spreadsheet = new Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();
                $sheet->setTitle('Danh mục');

                // Header
                $sheet->fromArray([$selectedColumns], null, 'A1');
                $lastColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($selectedColumns));
                $sheet->getStyle("A1:{$lastColLetter}1")->getFont()->setBold(true);
                $sheet->getStyle("A1:{$lastColLetter}1")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFF1F5F9');

                $rowIndex = 2;
                $chunkData = [];
                foreach ($query->lazy(1000) as $cat) {
                    $chunkData[] = $this->formatCategoryRow($cat, $selectedColumns);
                    if (count($chunkData) >= 1000) {
                        $sheet->fromArray($chunkData, null, "A{$rowIndex}");
                        $rowIndex += count($chunkData);
                        $chunkData = [];
                    }
                }
                if (!empty($chunkData)) {
                    $sheet->fromArray($chunkData, null, "A{$rowIndex}");
                    $rowIndex += count($chunkData);
                }

                // Auto-width
                foreach (range(1, count($selectedColumns)) as $colIndex) {
                    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                    $sheet->getColumnDimension($colLetter)->setAutoSize(true);
                }

                $tempFile = tempnam(sys_get_temp_dir(), 'cat_xlsx_');
                $writer = new Xlsx($spreadsheet);
                $writer->save($tempFile);

                return response()->download($tempFile, $filename, [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ])->deleteFileAfterSend(true);
            }
        }

        // Xuất CSV dạng Streaming cực nhanh, bộ nhớ cực thấp
        $filename = "danh_muc_{$timestamp}.csv";

        return new StreamedResponse(function () use ($query, $selectedColumns) {
            $handle = fopen('php://output', 'w');

            // Ghi UTF-8 BOM để Excel trên Windows hiển thị tiếng Việt chuẩn 100% không vỡ font
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Ghi header cột
            fputcsv($handle, $selectedColumns);

            // Stream toàn bộ bản ghi khớp bộ lọc siêu tốc, 0MB RAM
            foreach ($query->lazy(1000) as $cat) {
                fputcsv($handle, $this->formatCategoryRow($cat, $selectedColumns));
            }
            fflush($handle);

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    /**
     * Tải file mẫu CSV / Excel để người dùng tham khảo cấu trúc
     */
    public function downloadSample(Request $request)
    {
        $format = strtolower((string) $request->input('format', 'csv'));
        $columns = [
            'ID',
            'Tên danh mục',
            'Slug',
            'Danh mục cha (Tên)',
            'Danh mục cha (Slug)',
            'Thứ tự sắp xếp',
            'Trạng thái',
            'Mô tả',
            'Ảnh đại diện',
            'Meta Title',
            'Meta Description',
        ];

        $sampleData = [
            [
                '', // Để trống ID -> Tạo mới
                'Thời Trang Nam',
                'thoi-trang-nam',
                '', // Danh mục gốc
                '',
                1,
                1,
                'Bộ sưu tập thời trang nam cao cấp, phong cách hiện đại',
                'thoi-trang-nam.webp',
                'Thời Trang Nam Đẹp, Cao Cấp | Nobi Fashion',
                'Mua sắm thời trang nam cao cấp, phong cách trẻ trung lịch lãm.',
            ],
            [
                '',
                'Áo Polo Nam',
                'ao-polo-nam',
                'Thời Trang Nam', // Danh mục cha
                'thoi-trang-nam',
                2,
                1,
                'Áo polo nam chất liệu thoáng mát, co giãn tốt',
                'ao-polo-nam.webp',
                'Áo Polo Nam Chất Lượng Cao | Nobi Fashion',
                'Tổng hợp mẫu áo polo nam thời trang, lịch lãm, thoáng mát.',
            ],
            [
                '',
                'Quần Tây Nam',
                'quan-tay-nam',
                'Thời Trang Nam',
                'thoi-trang-nam',
                3,
                1,
                'Quần tây nam công sở chuẩn form, vải bền đẹp',
                'quan-tay-nam.webp',
                'Quần Tây Nam Công Sở | Nobi Fashion',
                'Quần tây nam form chuẩn hàn quốc, thanh lịch.',
            ],
            [
                '1', // Có ID -> Cập nhật danh mục có ID = 1
                'Thời Trang Nữ',
                'thoi-trang-nu',
                '',
                '',
                4,
                1,
                'Thời trang nữ duyên dáng, đón đầu xu hướng',
                'thoi-trang-nu.webp',
                'Thời Trang Nữ Đẹp & Thời Thượng | Nobi Fashion',
                'Mua sắm các mẫu váy, áo, quần nữ đẹp chất lượng cao.',
            ],
        ];

        $timestamp = date('Y-m-d');

        if ($format === 'xlsx' && extension_loaded('zip') && class_exists(\ZipArchive::class)) {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Mau_Danh_Muc');

            $sheet->fromArray([$columns], null, 'A1');
            $sheet->fromArray($sampleData, null, 'A2');

            $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($columns));
            $sheet->getStyle("A1:{$lastCol}1")->getFont()->setBold(true);
            $sheet->getStyle("A1:{$lastCol}1")->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFF1F5F9');

            foreach (range(1, count($columns)) as $colIndex) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                $sheet->getColumnDimension($colLetter)->setAutoSize(true);
            }

            $tempFile = tempnam(sys_get_temp_dir(), 'sample_cat_');
            $writer = new Xlsx($spreadsheet);
            $writer->save($tempFile);

            return response()->download($tempFile, "danh_muc_mau_{$timestamp}.xlsx", [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        }

        // CSV
        return new StreamedResponse(function () use ($columns, $sampleData) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, $columns);
            foreach ($sampleData as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"danh_muc_mau_{$timestamp}.csv\"",
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }

    /**
     * API xử lý batch import danh mục (Ultra Fast, hỗ trợ chọn cột & ánh xạ thông minh)
     */
    public function importBatch(Request $request): JsonResponse
    {
        $request->validate([
            'items' => 'required|array',
            'selected_columns' => 'nullable|array',
        ]);

        $items = $request->input('items');
        $selectedColumns = $request->input('selected_columns');
        $isColumnFilterActive = is_array($selectedColumns) && !empty($selectedColumns);

        $successCount = 0;
        $createdCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;
        $errors = [];

        // Helper kiểm tra xem cột có được chọn để nhập hay không
        $isColSelected = function (string $colName) use ($isColumnFilterActive, $selectedColumns): bool {
            if (!$isColumnFilterActive) {
                return true;
            }
            return in_array($colName, $selectedColumns, true);
        };

        // Cache setting site_url để tối ưu hóa event saving của Category không bị query DB lặp đi lặp lại
        Cache::remember('site_url_setting', 3600, fn() => Setting::where('key', 'site_url')->value('value') ?? '');

        // --- BƯỚC 1: TIỀN TẢI DỮ LIỆU ĐỂ GIẢM THIỂU QUERY TRONG BATCH ---
        $ids = [];
        $slugs = [];
        $parentIds = [];
        $parentSlugs = [];
        $parentNames = [];

        foreach ($items as $item) {
            $idVal = $this->extractValue($item, ['ID', 'id']);
            if (!empty($idVal) && is_numeric($idVal)) {
                $ids[] = (int) $idVal;
            }

            $slugVal = $this->extractValue($item, ['Slug', 'slug']);
            $nameVal = $this->extractValue($item, ['Tên danh mục', 'Tên', 'name', 'Title', 'Tiêu đề']);

            if (!empty($slugVal)) {
                $slugs[] = trim((string) $slugVal);
            } elseif (!empty($nameVal)) {
                $slugs[] = Str::slug(trim((string) $nameVal));
            }

            // Thu thập định danh danh mục cha
            $pId = $this->extractValue($item, ['Danh mục cha (ID)', 'parent_id', 'Mã cha']);
            if (!empty($pId) && is_numeric($pId)) {
                $parentIds[] = (int) $pId;
            }

            $pSlug = $this->extractValue($item, ['Danh mục cha (Slug)', 'parent_slug']);
            if (!empty($pSlug)) {
                $parentSlugs[] = trim((string) $pSlug);
            }

            $pName = $this->extractValue($item, ['Danh mục cha (Tên)', 'Danh mục cha', 'parent_name']);
            if (!empty($pName)) {
                $parentNames[] = trim((string) $pName);
            }
        }

        $ids = array_values(array_unique(array_filter($ids)));
        $slugs = array_values(array_unique(array_filter($slugs)));
        $parentIds = array_values(array_unique(array_filter($parentIds)));
        $parentSlugs = array_values(array_unique(array_filter($parentSlugs)));
        $parentNames = array_values(array_unique(array_filter($parentNames)));

        // Bulk load categories theo ID và theo Slug
        $categoriesById = !empty($ids)
            ? Category::whereIn('id', $ids)->get()->keyBy('id')
            : collect();

        $categoriesBySlug = !empty($slugs)
            ? Category::whereIn('slug', $slugs)->get()->keyBy('slug')
            : collect();

        // Bulk load parents
        $parentsById = !empty($parentIds)
            ? Category::whereIn('id', $parentIds)->get()->keyBy('id')
            : collect();

        $parentsBySlug = !empty($parentSlugs)
            ? Category::whereIn('slug', $parentSlugs)->get()->keyBy('slug')
            : collect();

        $parentsByName = !empty($parentNames)
            ? Category::whereIn('name', $parentNames)->get()->keyBy(fn($c) => mb_strtolower(trim($c->name), 'UTF-8'))
            : collect();

        // Gộp các categories đã load vào parent map để tái sử dụng
        foreach ($categoriesById as $cat) {
            $parentsById->put($cat->id, $cat);
            $parentsBySlug->put($cat->slug, $cat);
            $parentsByName->put(mb_strtolower(trim($cat->name), 'UTF-8'), $cat);
        }
        foreach ($categoriesBySlug as $cat) {
            $parentsById->put($cat->id, $cat);
            $parentsBySlug->put($cat->slug, $cat);
            $parentsByName->put(mb_strtolower(trim($cat->name), 'UTF-8'), $cat);
        }

        // --- BƯỚC 2: XỬ LÝ TỪNG ITEM TRONG BATCH BẰNG DATABASE TRANSACTION ---
        DB::beginTransaction();

        try {
            foreach ($items as $index => $item) {
                $itemName = trim((string) $this->extractValue($item, ['Tên danh mục', 'Tên', 'name', 'Title', 'Tiêu đề']));
                $idVal = $this->extractValue($item, ['ID', 'id']);
                $hasId = !empty($idVal) && is_numeric($idVal);
                $id = $hasId ? (int) $idVal : null;

                $itemIdentifier = $hasId ? "ID {$id}" : ($this->extractValue($item, ['Slug', 'slug']) ?: ($itemName ?: 'Dòng #' . ($index + 1)));

                try {
                    $category = null;
                    $isUpdate = false;

                    // QUY TẮC 1: Nếu có ID thì BẮT BUỘC đó là cập nhật
                    if ($hasId) {
                        $category = $categoriesById->get($id);
                        if (!$category) {
                            $category = Category::find($id);
                            if ($category) {
                                $categoriesById->put($category->id, $category);
                            }
                        }

                        if (!$category) {
                            throw new \Exception("Danh mục ID = {$id} không tồn tại trên hệ thống. (Theo quy tắc: Khi có ID bắt buộc phải là cập nhật).");
                        }
                        $isUpdate = true;
                    } else {
                        // QUY TẮC 2 & 3: Nếu không có ID, khớp theo Slug
                        $slug = trim((string) $this->extractValue($item, ['Slug', 'slug']));
                        if ($slug === '' && $itemName !== '') {
                            $slug = Str::slug($itemName);
                        }

                        if ($slug !== '') {
                            $category = $categoriesBySlug->get($slug);
                            if (!$category) {
                                $category = Category::where('slug', $slug)->first();
                                if ($category) {
                                    $categoriesBySlug->put($category->slug, $category);
                                }
                            }
                        }

                        if ($category) {
                            $isUpdate = true; // Khớp theo slug -> cập nhật
                        } else {
                            $isUpdate = false; // Không có ID và slug chưa có -> TẠO MỚI
                        }
                    }

                    // Xử lý Danh mục cha (Parent ID)
                    $resolvedParentId = $this->resolveParentCategory(
                        $item,
                        $category?->id,
                        $parentsById,
                        $parentsBySlug,
                        $parentsByName,
                        $isColSelected
                    );

                    // Xử lý Trạng thái (is_active)
                    $rawStatus = $this->extractValue($item, ['Trạng thái', 'status', 'is_active', 'Hiển thị']);
                    $isActive = true;
                    if ($rawStatus !== null && $rawStatus !== '') {
                        $lowerStatus = mb_strtolower(trim((string) $rawStatus), 'UTF-8');
                        if (in_array($lowerStatus, ['0', 'false', 'ẩn', 'an', 'tạm ẩn', 'tam an', 'inactive', 'off'], true)) {
                            $isActive = false;
                        } else {
                            $isActive = true;
                        }
                    }

                    // Xử lý Thứ tự sắp xếp (sort_order)
                    $rawSortOrder = $this->extractValue($item, ['Thứ tự sắp xếp', 'Thứ tự', 'sort_order', 'order', 'Số thứ tự']);
                    $sortOrder = ($rawSortOrder !== null && $rawSortOrder !== '' && is_numeric($rawSortOrder))
                        ? (int) $rawSortOrder
                        : 0;

                    if ($isUpdate) {
                        // --- CẬP NHẬT DANH MỤC HIỆN CÓ ---
                        $dataToUpdate = [];

                        if ($isColSelected('Tên danh mục') && $itemName !== '') {
                            $dataToUpdate['name'] = Str::limit($itemName, 250);
                        }

                        if ($isColSelected('Slug')) {
                            $newSlug = trim((string) $this->extractValue($item, ['Slug', 'slug']));
                            if ($newSlug !== '') {
                                $dataToUpdate['slug'] = Str::slug($newSlug);
                            }
                        }

                        if ($isColSelected('Mô tả')) {
                            $desc = $this->extractValue($item, ['Mô tả', 'description', 'Nội dung mô tả']);
                            if ($desc !== null) {
                                $dataToUpdate['description'] = (string) $desc;
                            }
                        }

                        if ($isColSelected('Ảnh đại diện')) {
                            $img = $this->extractValue($item, ['Ảnh đại diện', 'Ảnh', 'image', 'Hình ảnh']);
                            if ($img !== null) {
                                $dataToUpdate['image'] = (string) $img;
                            }
                        }

                        if ($isColSelected('Danh mục cha (Tên)') || $isColSelected('Danh mục cha (Slug)') || $isColSelected('Danh mục cha (ID)')) {
                            if ($resolvedParentId !== 'UNTOUCHED') {
                                $dataToUpdate['parent_id'] = $resolvedParentId;
                            }
                        }

                        if ($isColSelected('Thứ tự sắp xếp') && $rawSortOrder !== null && $rawSortOrder !== '') {
                            $dataToUpdate['sort_order'] = $sortOrder;
                        }

                        if ($isColSelected('Trạng thái') && $rawStatus !== null && $rawStatus !== '') {
                            $dataToUpdate['is_active'] = $isActive;
                        }

                        if ($isColSelected('Meta Title')) {
                            $val = $this->extractValue($item, ['Meta Title', 'meta_title', 'SEO Title']);
                            if ($val !== null) {
                                $dataToUpdate['meta_title'] = (string) $val;
                            }
                        }

                        if ($isColSelected('Meta Description')) {
                            $val = $this->extractValue($item, ['Meta Description', 'meta_description', 'SEO Description']);
                            if ($val !== null) {
                                $dataToUpdate['meta_description'] = (string) $val;
                            }
                        }

                        if ($isColSelected('Meta Keywords')) {
                            $val = $this->extractValue($item, ['Meta Keywords', 'meta_keywords', 'SEO Keywords']);
                            if ($val !== null) {
                                $dataToUpdate['meta_keywords'] = (string) $val;
                            }
                        }

                        if ($isColSelected('Meta Canonical')) {
                            $val = $this->extractValue($item, ['Meta Canonical', 'meta_canonical']);
                            if ($val !== null) {
                                $dataToUpdate['meta_canonical'] = (string) $val;
                            }
                        }

                        // So sánh dữ liệu thông minh để bỏ qua update nếu không có thay đổi
                        $hasDiff = false;
                        foreach ($dataToUpdate as $k => $v) {
                            if ($k === 'is_active') {
                                if ((bool) $category->is_active !== (bool) $v) {
                                    $hasDiff = true;
                                    break;
                                }
                            } elseif ($category->{$k} != $v) {
                                $hasDiff = true;
                                break;
                            }
                        }

                        if ($hasDiff && !empty($dataToUpdate)) {
                            $category->update($dataToUpdate);
                            $updatedCount++;
                        } else {
                            $skippedCount++;
                        }

                        $successCount++;
                    } else {
                        // --- TẠO MỚI DANH MỤC ---
                        if (empty($itemName)) {
                            throw new \Exception('Tên danh mục không được để trống khi tạo mới.');
                        }

                        $slug = trim((string) $this->extractValue($item, ['Slug', 'slug']));
                        if ($slug === '') {
                            $slug = Str::slug($itemName);
                        } else {
                            $slug = Str::slug($slug);
                        }

                        if (empty($slug)) {
                            $slug = 'danh-muc-' . time() . '-' . Str::random(4);
                        }

                        // Kiểm tra trùng slug trong DB
                        $originalSlug = $slug;
                        $slugCounter = 1;
                        while (Category::where('slug', $slug)->exists()) {
                            $slug = "{$originalSlug}-{$slugCounter}";
                            $slugCounter++;
                        }

                        $newCategoryData = [
                            'name' => Str::limit($itemName, 250),
                            'slug' => $slug,
                            'description' => $isColSelected('Mô tả') ? (string) ($this->extractValue($item, ['Mô tả', 'description']) ?? '') : null,
                            'image' => $isColSelected('Ảnh đại diện') ? (string) ($this->extractValue($item, ['Ảnh đại diện', 'Ảnh', 'image']) ?? '') : null,
                            'parent_id' => $resolvedParentId === 'UNTOUCHED' ? null : $resolvedParentId,
                            'sort_order' => $sortOrder,
                            'is_active' => $isActive,
                            'meta_title' => $isColSelected('Meta Title') ? (string) ($this->extractValue($item, ['Meta Title', 'meta_title']) ?? '') : null,
                            'meta_description' => $isColSelected('Meta Description') ? (string) ($this->extractValue($item, ['Meta Description', 'meta_description']) ?? '') : null,
                            'meta_keywords' => $isColSelected('Meta Keywords') ? (string) ($this->extractValue($item, ['Meta Keywords', 'meta_keywords']) ?? '') : null,
                            'meta_canonical' => $isColSelected('Meta Canonical') ? (string) ($this->extractValue($item, ['Meta Canonical', 'meta_canonical']) ?? '') : null,
                        ];

                        $newCategory = Category::create($newCategoryData);

                        // Đưa vào maps để các dòng tiếp theo có thể tham chiếu làm cha
                        $categoriesById->put($newCategory->id, $newCategory);
                        $categoriesBySlug->put($newCategory->slug, $newCategory);
                        $parentsById->put($newCategory->id, $newCategory);
                        $parentsBySlug->put($newCategory->slug, $newCategory);
                        $parentsByName->put(mb_strtolower(trim($newCategory->name), 'UTF-8'), $newCategory);

                        $createdCount++;
                        $successCount++;
                    }
                } catch (\Throwable $e) {
                    $errors[] = "[{$itemIdentifier}]: " . $e->getMessage();
                }
            }

            DB::commit();
        } catch (\Throwable $ex) {
            DB::rollBack();
            Log::error('Category import batch exception:', [
                'message' => $ex->getMessage(),
                'trace' => $ex->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Lỗi xử lý cơ sở dữ liệu: ' . $ex->getMessage(),
                'errors' => array_merge($errors, [$ex->getMessage()]),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'success_count' => $successCount,
            'created_count' => $createdCount,
            'updated_count' => $updatedCount,
            'skipped_count' => $skippedCount,
            'errors' => $errors,
        ]);
    }

    /**
     * Tìm giá trị theo danh sách tên cột tiềm năng (không phân biệt hoa thường / khoảng trắng)
     */
    private function extractValue(array $item, array $keys)
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $item)) {
                return $item[$key];
            }
        }

        // Thử tìm case-insensitive
        $itemNormalized = [];
        foreach ($item as $k => $v) {
            $itemNormalized[mb_strtolower(trim((string) $k), 'UTF-8')] = $v;
        }

        foreach ($keys as $key) {
            $lowerKey = mb_strtolower(trim($key), 'UTF-8');
            if (array_key_exists($lowerKey, $itemNormalized)) {
                return $itemNormalized[$lowerKey];
            }
        }

        return null;
    }

    /**
     * Phân giải danh mục cha từ ID, Slug hoặc Tên
     */
    private function resolveParentCategory(
        array $item,
        ?int $currentCategoryId,
        &$parentsById,
        &$parentsBySlug,
        &$parentsByName,
        callable $isColSelected
    ) {
        $hasParentIdCol = $isColSelected('Danh mục cha (ID)');
        $hasParentSlugCol = $isColSelected('Danh mục cha (Slug)');
        $hasParentNameCol = $isColSelected('Danh mục cha (Tên)') || $isColSelected('Danh mục cha');

        if (!$hasParentIdCol && !$hasParentSlugCol && !$hasParentNameCol) {
            return 'UNTOUCHED'; // Không chọn cột cha -> giữ nguyên
        }

        // 1. Kiểm tra theo ID cha
        $rawPId = $this->extractValue($item, ['Danh mục cha (ID)', 'parent_id', 'Mã cha']);
        if (!empty($rawPId) && is_numeric($rawPId)) {
            $pId = (int) $rawPId;
            if ($currentCategoryId && $pId === $currentCategoryId) {
                throw new \Exception('Danh mục không thể làm cha của chính nó.');
            }

            $parent = $parentsById->get($pId);
            if (!$parent) {
                $parent = Category::find($pId);
                if ($parent) {
                    $parentsById->put($parent->id, $parent);
                }
            }

            if ($parent) {
                return $parent->id;
            }
        }

        // 2. Kiểm tra theo Slug cha
        $rawPSlug = trim((string) $this->extractValue($item, ['Danh mục cha (Slug)', 'parent_slug']));
        if (!empty($rawPSlug)) {
            $parent = $parentsBySlug->get($rawPSlug);
            if (!$parent) {
                $parent = Category::where('slug', $rawPSlug)->first();
                if ($parent) {
                    $parentsBySlug->put($parent->slug, $parent);
                    $parentsById->put($parent->id, $parent);
                }
            }

            if ($parent) {
                if ($currentCategoryId && $parent->id === $currentCategoryId) {
                    throw new \Exception('Danh mục không thể làm cha của chính nó.');
                }
                return $parent->id;
            }
        }

        // 3. Kiểm tra theo Tên cha
        $rawPName = trim((string) $this->extractValue($item, ['Danh mục cha (Tên)', 'Danh mục cha', 'parent_name']));
        if (!empty($rawPName)) {
            $lowerName = mb_strtolower($rawPName, 'UTF-8');
            $parent = $parentsByName->get($lowerName);
            if (!$parent) {
                $parent = Category::where('name', $rawPName)->first();
                if ($parent) {
                    $parentsByName->put($lowerName, $parent);
                    $parentsById->put($parent->id, $parent);
                }
            }

            if ($parent) {
                if ($currentCategoryId && $parent->id === $currentCategoryId) {
                    throw new \Exception('Danh mục không thể làm cha của chính nó.');
                }
                return $parent->id;
            }

            // Tự động tạo danh mục cha nếu chưa có
            $parentSlug = Str::slug($rawPName);
            $newParent = Category::create([
                'name' => $rawPName,
                'slug' => $parentSlug,
                'is_active' => true,
                'sort_order' => 0,
            ]);

            $parentsById->put($newParent->id, $newParent);
            $parentsBySlug->put($newParent->slug, $newParent);
            $parentsByName->put($lowerName, $newParent);

            return $newParent->id;
        }

        // Nếu người dùng cố ý để trống tất cả thông tin cha -> xem như danh mục gốc (parent_id = null)
        return null;
    }

    /**
     * Chuẩn bị mảng 1 dòng dữ liệu cho 1 category theo cột đã chọn
     */
    private function formatCategoryRow(Category $cat, array $selectedColumns): array
    {
        $row = [];
        foreach ($selectedColumns as $col) {
            $row[] = match ($col) {
                'ID' => $cat->id,
                'Tên danh mục' => $cat->name,
                'Slug' => $cat->slug,
                'Danh mục cha (Tên)' => $cat->parent?->name ?? '',
                'Danh mục cha (Slug)' => $cat->parent?->slug ?? '',
                'Danh mục cha (ID)' => $cat->parent_id ?? '',
                'Đường dẫn đầy đủ' => $cat->fullPath(),
                'Thứ tự sắp xếp' => $cat->sort_order,
                'Trạng thái' => $cat->is_active ? 1 : 0,
                'Mô tả' => $cat->description ?? '',
                'Ảnh đại diện' => $cat->image ?? '',
                'Meta Title' => $cat->meta_title ?? '',
                'Meta Description' => $cat->meta_description ?? '',
                'Meta Keywords' => $cat->meta_keywords ?? '',
                'Meta Canonical' => $cat->meta_canonical ?? '',
                'Số sản phẩm' => $cat->primary_products_count ?? 0,
                'Ngày tạo' => $cat->created_at ? $cat->created_at->format('Y-m-d H:i:s') : '',
                'Ngày cập nhật' => $cat->updated_at ? $cat->updated_at->format('Y-m-d H:i:s') : '',
                default => '',
            };
        }
        return $row;
    }

    /**
     * Xây dựng query lọc danh mục dựa theo các tham số tìm kiếm
     */
    private function buildFilterQuery(Request $request)
    {
        $query = Category::query();

        // Lọc theo danh sách ID cụ thể (dành cho xuất danh mục đang chọn)
        $ids = $request->input('ids');
        if ($ids) {
            if (is_string($ids)) {
                $ids = array_filter(array_map('intval', explode(',', $ids)));
            }
            if (is_array($ids) && !empty($ids)) {
                $query->whereIn('id', $ids);
            }
        }

        // Lọc từ khóa
        if ($keyword = $request->input('keyword', $request->input('search'))) {
            $keyword = trim((string) $keyword);
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', '%' . $keyword . '%')
                    ->orWhere('slug', 'like', '%' . $keyword . '%')
                    ->orWhere('description', 'like', '%' . $keyword . '%');
            });
        }

        // Lọc trạng thái
        if ($status = $request->input('status')) {
            if ($status === 'active' || $status === '1') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive' || $status === '0') {
                $query->where('is_active', false);
            }
        }

        // Lọc cấp bậc (level: root hoặc child)
        if ($level = $request->input('level')) {
            if ($level === 'root') {
                $query->whereNull('parent_id');
            } elseif ($level === 'child') {
                $query->whereNotNull('parent_id');
            }
        }

        // Lọc theo danh mục cha
        if ($parentId = $request->input('parent_id')) {
            if ($parentId === 'root') {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $parentId);
            }
        }

        // Sắp xếp
        $sort = $request->input('sort', 'sort_order');
        $direction = strtolower((string) $request->input('direction', 'asc')) === 'desc' ? 'desc' : 'asc';

        if (in_array($sort, ['id', 'name', 'sort_order', 'created_at', 'updated_at'], true)) {
            if ($sort === 'sort_order') {
                $query->orderByRaw('CASE WHEN parent_id IS NULL THEN id ELSE parent_id END ASC, parent_id ASC, sort_order ' . $direction);
            } else {
                $query->orderBy($sort, $direction);
            }
        } else {
            $query->orderByRaw('CASE WHEN parent_id IS NULL THEN id ELSE parent_id END ASC, parent_id ASC, sort_order ASC');
        }

        return $query;
    }
}
