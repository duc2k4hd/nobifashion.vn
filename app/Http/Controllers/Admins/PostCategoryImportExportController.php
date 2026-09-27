<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Models\PostCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PostCategoryImportExportController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:web', 'admin']);
    }

    /**
     * Danh sách tất cả các cột hỗ trợ xuất / nhập danh mục bài viết
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
            ['key' => 'Thứ tự sắp xếp', 'label' => 'Thứ tự sắp xếp (sort_order)', 'default_export' => true, 'default_import' => true],
            ['key' => 'Trạng thái', 'label' => 'Trạng thái (1: Hoạt động, 0: Tạm ẩn)', 'default_export' => true, 'default_import' => true],
            ['key' => 'Mô tả', 'label' => 'Mô tả danh mục bài viết', 'default_export' => false, 'default_import' => true],
            ['key' => 'Ảnh đại diện', 'label' => 'Ảnh đại diện (URL / Tên file)', 'default_export' => false, 'default_import' => true],
            ['key' => 'Meta Title', 'label' => 'SEO Meta Title', 'default_export' => false, 'default_import' => true],
            ['key' => 'Meta Description', 'label' => 'SEO Meta Description', 'default_export' => false, 'default_import' => true],
            ['key' => 'Meta Keywords', 'label' => 'SEO Meta Keywords', 'default_export' => false, 'default_import' => false],
            ['key' => 'Meta Canonical', 'label' => 'SEO Canonical URL', 'default_export' => false, 'default_import' => false],
            ['key' => 'Số bài viết', 'label' => 'Số bài viết (Chỉ xuất)', 'default_export' => false, 'default_import' => false],
            ['key' => 'Ngày tạo', 'label' => 'Ngày tạo (created_at)', 'default_export' => false, 'default_import' => false],
            ['key' => 'Ngày cập nhật', 'label' => 'Ngày cập nhật (updated_at)', 'default_export' => false, 'default_import' => false],
        ];
    }

    /**
     * Lấy dữ liệu danh mục bài viết dạng JSON cho client JS (SheetJS)
     */
    public function getExportData(Request $request): JsonResponse
    {
        $selectedColumns = $request->input('columns', ['ID', 'Tên danh mục', 'Slug', 'Danh mục cha (Tên)', 'Danh mục cha (Slug)', 'Thứ tự sắp xếp', 'Trạng thái']);
        if (!is_array($selectedColumns) || empty($selectedColumns)) {
            $selectedColumns = ['ID', 'Tên danh mục', 'Slug', 'Danh mục cha (Tên)', 'Danh mục cha (Slug)', 'Thứ tự sắp xếp', 'Trạng thái'];
        }

        $query = $this->buildFilterQuery($request);

        if (
            in_array('Danh mục cha (Tên)', $selectedColumns, true) ||
            in_array('Danh mục cha (Slug)', $selectedColumns, true)
        ) {
            $query->with('parent');
        }

        if (in_array('Số bài viết', $selectedColumns, true)) {
            $query->withCount('posts');
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
                'Thứ tự sắp xếp' => $category->sort_order,
                'Trạng thái' => $category->is_active ? 1 : 0,
                'Mô tả' => $category->description ?? '',
                'Ảnh đại diện' => $category->image ?? '',
                'Meta Title' => $category->meta_title ?? '',
                'Meta Description' => $category->meta_description ?? '',
                'Meta Keywords' => $category->meta_keywords ?? '',
                'Meta Canonical' => $category->meta_canonical ?? '',
                'Số bài viết' => $category->posts_count ?? 0,
                'Ngày tạo' => $category->created_at ? $category->created_at->format('Y-m-d H:i:s') : '',
                'Ngày cập nhật' => $category->updated_at ? $category->updated_at->format('Y-m-d H:i:s') : '',
            ];

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
     * Xuất danh mục bài viết dạng Direct Streaming CSV (0MB RAM, cực nhanh với hàng trăm nghìn bản ghi) hoặc XLSX
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
            in_array('Danh mục cha (Slug)', $selectedColumns, true)
        ) {
            $query->with('parent');
        }

        if (in_array('Số bài viết', $selectedColumns, true)) {
            $query->withCount('posts');
        }

        $timestamp = date('Y-m-d_His');

        // Xuất XLSX
        if ($format === 'xlsx') {
            if (!extension_loaded('zip') || !class_exists(\ZipArchive::class)) {
                $format = 'csv';
            } else {
                $filename = "danh_muc_bai_viet_{$timestamp}.xlsx";
                $spreadsheet = new Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();
                $sheet->setTitle('Post_Categories');

                $sheet->fromArray([$selectedColumns], null, 'A1');
                $lastColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($selectedColumns));
                $sheet->getStyle("A1:{$lastColLetter}1")->getFont()->setBold(true);
                $sheet->getStyle("A1:{$lastColLetter}1")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFF1F5F9');

                $rowIndex = 2;
                $query->orderBy('id', 'asc')->chunkById(1000, function ($categories) use ($sheet, $selectedColumns, &$rowIndex) {
                    $chunkData = [];
                    foreach ($categories as $cat) {
                        $chunkData[] = $this->formatCategoryRow($cat, $selectedColumns);
                    }
                    if (!empty($chunkData)) {
                        $sheet->fromArray($chunkData, null, "A{$rowIndex}");
                        $rowIndex += count($chunkData);
                    }
                });

                foreach (range(1, count($selectedColumns)) as $colIndex) {
                    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                    $sheet->getColumnDimension($colLetter)->setAutoSize(true);
                }

                $tempFile = tempnam(sys_get_temp_dir(), 'post_cat_xlsx_');
                $writer = new Xlsx($spreadsheet);
                $writer->save($tempFile);

                return response()->download($tempFile, $filename, [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ])->deleteFileAfterSend(true);
            }
        }

        // Xuất CSV dạng Streaming
        $filename = "danh_muc_bai_viet_{$timestamp}.csv";

        return new StreamedResponse(function () use ($query, $selectedColumns) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, $selectedColumns);

            $query->orderBy('id', 'asc')->chunkById(1000, function ($categories) use ($handle, $selectedColumns) {
                foreach ($categories as $cat) {
                    fputcsv($handle, $this->formatCategoryRow($cat, $selectedColumns));
                }
                fflush($handle);
            });

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
     * Tải file mẫu CSV / Excel
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
                '', // Để trống -> Tạo mới
                'Kiến Thức & Xu Hướng',
                'kien-thuc-va-xu-huong',
                '', // Danh mục gốc
                '',
                1,
                1,
                'Cập nhật những xu hướng thời trang mới nhất và mẹo phối đồ đỉnh cao',
                'kien-thuc-xu-huong.webp',
                'Kiến Thức Thời Trang & Xu Hướng Phối Đồ | Nobi Fashion',
                'Chuyên mục kiến thức thời trang, cẩm nang mặc đẹp và xu hướng thời trang hot nhất.',
            ],
            [
                '',
                'Mẹo Phối Đồ Nam',
                'meo-phoi-do-nam',
                'Kiến Thức & Xu Hướng',
                'kien-thuc-va-xu-huong',
                2,
                1,
                'Tổng hợp bí quyết phối đồ nam lịch lãm, trẻ trung mọi hoàn cảnh',
                'meo-phoi-do-nam.webp',
                'Mẹo Phối Đồ Nam Đẹp, Cuốn Hút | Nobi Fashion',
                'Hướng dẫn cách phối đồ nam chuẩn soái ca từ áo sơ mi, polo đến quần âu.',
            ],
            [
                '',
                'Phong Cách Nữ',
                'phong-cach-nu',
                'Kiến Thức & Xu Hướng',
                'kien-thuc-va-xu-huong',
                3,
                1,
                'Khám phá các phong cách thời trang nữ quyến rũ và thanh lịch',
                'phong-cach-nu.webp',
                'Phong Cách Thời Trang Nữ Hiện Đại | Nobi Fashion',
                'Bí quyết chọn đồ và mix match thời trang nữ sành điệu.',
            ],
            [
                '1', // Có ID -> Cập nhật danh mục có ID = 1
                'Tin Tức & Sự Kiện',
                'tin-tuc-va-su-kien',
                '',
                '',
                4,
                1,
                'Thông tin sự kiện, ưu đãi khuyến mãi mới nhất từ Nobi Fashion',
                'tin-tuc-su-kien.webp',
                'Tin Tức & Ưu Đãi Nổi Bật | Nobi Fashion',
                'Cập nhật các chương trình flash sale và sự kiện thời trang hot nhất.',
            ],
        ];

        $timestamp = date('Y-m-d');

        if ($format === 'xlsx' && extension_loaded('zip') && class_exists(\ZipArchive::class)) {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Mau_Danh_Muc_Bai_Viet');

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

            $tempFile = tempnam(sys_get_temp_dir(), 'sample_post_cat_');
            $writer = new Xlsx($spreadsheet);
            $writer->save($tempFile);

            return response()->download($tempFile, "danh_muc_bai_viet_mau_{$timestamp}.xlsx", [
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
            'Content-Disposition' => "attachment; filename=\"danh_muc_bai_viet_mau_{$timestamp}.csv\"",
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }

    /**
     * API xử lý batch import danh mục bài viết (Ultra Fast, đa luồng, chọn cột & ánh xạ thông minh)
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

        $isColSelected = function (string $colName) use ($isColumnFilterActive, $selectedColumns): bool {
            if (!$isColumnFilterActive) {
                return true;
            }
            return in_array($colName, $selectedColumns, true);
        };

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

        $categoriesById = !empty($ids)
            ? PostCategory::whereIn('id', $ids)->get()->keyBy('id')
            : collect();

        $categoriesBySlug = !empty($slugs)
            ? PostCategory::whereIn('slug', $slugs)->get()->keyBy('slug')
            : collect();

        $parentsById = !empty($parentIds)
            ? PostCategory::whereIn('id', $parentIds)->get()->keyBy('id')
            : collect();

        $parentsBySlug = !empty($parentSlugs)
            ? PostCategory::whereIn('slug', $parentSlugs)->get()->keyBy('slug')
            : collect();

        $parentsByName = !empty($parentNames)
            ? PostCategory::whereIn('name', $parentNames)->get()->keyBy(fn($c) => mb_strtolower(trim($c->name), 'UTF-8'))
            : collect();

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
                            $category = PostCategory::find($id);
                            if ($category) {
                                $categoriesById->put($category->id, $category);
                            }
                        }

                        if (!$category) {
                            throw new \Exception("Danh mục bài viết ID = {$id} không tồn tại trên hệ thống. (Theo quy tắc: Khi có ID bắt buộc phải là cập nhật).");
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
                                $category = PostCategory::where('slug', $slug)->first();
                                if ($category) {
                                    $categoriesBySlug->put($category->slug, $category);
                                }
                            }
                        }

                        if ($category) {
                            $isUpdate = true;
                        } else {
                            $isUpdate = false;
                        }
                    }

                    // Phân giải danh mục cha
                    $resolvedParentId = $this->resolveParentCategory(
                        $item,
                        $category?->id,
                        $parentsById,
                        $parentsBySlug,
                        $parentsByName,
                        $isColSelected
                    );

                    // Trạng thái (is_active)
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

                    // Thứ tự sắp xếp (sort_order)
                    $rawSortOrder = $this->extractValue($item, ['Thứ tự sắp xếp', 'Thứ tự', 'sort_order', 'order', 'Số thứ tự']);
                    $sortOrder = ($rawSortOrder !== null && $rawSortOrder !== '' && is_numeric($rawSortOrder))
                        ? (int) $rawSortOrder
                        : 0;

                    if ($isUpdate) {
                        // --- CẬP NHẬT ---
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

                        // So sánh dữ liệu thông minh
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
                        // --- TẠO MỚI ---
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
                            $slug = 'danh-muc-bai-viet-' . time() . '-' . Str::random(4);
                        }

                        $originalSlug = $slug;
                        $slugCounter = 1;
                        while (PostCategory::where('slug', $slug)->exists()) {
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

                        $newCategory = PostCategory::create($newCategoryData);

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
            Log::error('PostCategory import batch exception:', [
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

    private function extractValue(array $item, array $keys)
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $item)) {
                return $item[$key];
            }
        }

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
            return 'UNTOUCHED';
        }

        // 1. ID cha
        $rawPId = $this->extractValue($item, ['Danh mục cha (ID)', 'parent_id', 'Mã cha']);
        if (!empty($rawPId) && is_numeric($rawPId)) {
            $pId = (int) $rawPId;
            if ($currentCategoryId && $pId === $currentCategoryId) {
                throw new \Exception('Danh mục không thể làm cha của chính nó.');
            }

            $parent = $parentsById->get($pId);
            if (!$parent) {
                $parent = PostCategory::find($pId);
                if ($parent) {
                    $parentsById->put($parent->id, $parent);
                }
            }

            if ($parent) {
                return $parent->id;
            }
        }

        // 2. Slug cha
        $rawPSlug = trim((string) $this->extractValue($item, ['Danh mục cha (Slug)', 'parent_slug']));
        if (!empty($rawPSlug)) {
            $parent = $parentsBySlug->get($rawPSlug);
            if (!$parent) {
                $parent = PostCategory::where('slug', $rawPSlug)->first();
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

        // 3. Tên cha
        $rawPName = trim((string) $this->extractValue($item, ['Danh mục cha (Tên)', 'Danh mục cha', 'parent_name']));
        if (!empty($rawPName)) {
            $lowerName = mb_strtolower($rawPName, 'UTF-8');
            $parent = $parentsByName->get($lowerName);
            if (!$parent) {
                $parent = PostCategory::where('name', $rawPName)->first();
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
            $newParent = PostCategory::create([
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

        return null;
    }

    private function formatCategoryRow(PostCategory $cat, array $selectedColumns): array
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
                'Thứ tự sắp xếp' => $cat->sort_order,
                'Trạng thái' => $cat->is_active ? 1 : 0,
                'Mô tả' => $cat->description ?? '',
                'Ảnh đại diện' => $cat->image ?? '',
                'Meta Title' => $cat->meta_title ?? '',
                'Meta Description' => $cat->meta_description ?? '',
                'Meta Keywords' => $cat->meta_keywords ?? '',
                'Meta Canonical' => $cat->meta_canonical ?? '',
                'Số bài viết' => $cat->posts_count ?? 0,
                'Ngày tạo' => $cat->created_at ? $cat->created_at->format('Y-m-d H:i:s') : '',
                'Ngày cập nhật' => $cat->updated_at ? $cat->updated_at->format('Y-m-d H:i:s') : '',
                default => '',
            };
        }
        return $row;
    }

    private function buildFilterQuery(Request $request)
    {
        $query = PostCategory::query();

        if ($search = $request->input('search', $request->input('keyword'))) {
            $search = trim((string) $search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $sort = $request->input('sort', 'sort_order');
        $direction = strtolower((string) $request->input('direction', 'asc')) === 'desc' ? 'desc' : 'asc';

        if (in_array($sort, ['id', 'name', 'sort_order', 'created_at', 'updated_at'], true)) {
            $query->orderBy($sort, $direction);
        } else {
            $query->orderBy('sort_order', 'asc')->orderBy('id', 'asc');
        }

        return $query;
    }
}
