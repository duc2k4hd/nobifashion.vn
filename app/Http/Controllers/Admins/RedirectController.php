<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Models\Redirect;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RedirectController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:web', 'admin']);
    }

    /**
     * Danh sách chuyển hướng 301
     */
    public function index(Request $request): View
    {
        $query = Redirect::query();

        // 1. Tìm kiếm theo từ khóa
        if ($request->filled('search')) {
            $keyword = trim((string) $request->input('search'));
            $query->where(function ($q) use ($keyword) {
                if (is_numeric($keyword)) {
                    $q->orWhere('id', (int) $keyword);
                }
                $q->orWhere('old_url', 'LIKE', "%{$keyword}%")
                  ->orWhere('new_url', 'LIKE', "%{$keyword}%")
                  ->orWhere('note', 'LIKE', "%{$keyword}%");
            });
        }

        // 2. Lọc theo trạng thái kích hoạt
        if ($request->filled('status')) {
            $status = $request->input('status');
            if ($status === 'active' || $status === '1') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive' || $status === '0') {
                $query->where('is_active', false);
            }
        }

        // 3. Sắp xếp
        $sort = $request->input('sort', 'latest');
        match ($sort) {
            'hits_desc' => $query->orderByDesc('hits')->orderByDesc('id'),
            'hits_asc' => $query->orderBy('hits')->orderByDesc('id'),
            'oldest' => $query->orderBy('id', 'asc'),
            default => $query->orderByDesc('id'),
        };

        // 4. Phân trang
        $perPage = (int) $request->input('limit', 50);
        if (!in_array($perPage, [50, 100, 300, 1000], true)) {
            $perPage = 50;
        }

        $redirects = $query->paginate($perPage)->withQueryString();

        // 5. Thống kê nhanh (cache 60s để tránh query count liên tục)
        $stats = Cache::remember('admin:redirects:stats', 60, function () {
            return [
                'total' => DB::table('redirects')->count(),
                'active' => DB::table('redirects')->where('is_active', true)->count(),
                'total_hits' => DB::table('redirects')->sum('hits'),
            ];
        });

        return view('admins.redirects.index', [
            'redirects' => $redirects,
            'filters' => $request->all(),
            'stats' => $stats,
        ]);
    }

    /**
     * Thêm mới chuyển hướng
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'old_url' => 'required|string|max:500',
            'new_url' => 'required|string|max:500',
            'status_code' => 'nullable|integer|in:301,302',
            'is_active' => 'nullable|boolean',
            'note' => 'nullable|string|max:255',
        ], [
            'old_url.required' => 'Vui lòng nhập Link cũ hoặc slug cũ.',
            'new_url.required' => 'Vui lòng nhập Link mới đích đến.',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        $oldUrl = trim($request->input('old_url'));
        $newUrl = trim($request->input('new_url'));
        $normalizedOld = Redirect::normalizePath($oldUrl);
        $hash = md5($normalizedOld);

        // Kiểm tra trùng lặp link cũ
        $exists = Redirect::where('old_url_hash', $hash)->exists();
        if ($exists) {
            $msg = 'Link cũ này đã tồn tại trong danh sách chuyển hướng!';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->with('error', $msg)->withInput();
        }

        $redirect = Redirect::create([
            'old_url' => $oldUrl,
            'old_url_hash' => $hash,
            'new_url' => $newUrl,
            'status_code' => (int) $request->input('status_code', 301),
            'is_active' => $request->boolean('is_active', true),
            'note' => $request->input('note'),
        ]);

        Cache::forget('admin:redirects:stats');

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Đã tạo chuyển hướng thành công!', 'redirect' => $redirect]);
        }

        return back()->with('success', 'Đã tạo chuyển hướng thành công!');
    }

    /**
     * Cập nhật chuyển hướng
     */
    public function update(Request $request, Redirect $redirect): RedirectResponse|JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'old_url' => 'required|string|max:500',
            'new_url' => 'required|string|max:500',
            'status_code' => 'nullable|integer|in:301,302',
            'is_active' => 'nullable|boolean',
            'note' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        $oldUrl = trim($request->input('old_url'));
        $newUrl = trim($request->input('new_url'));
        $normalizedOld = Redirect::normalizePath($oldUrl);
        $hash = md5($normalizedOld);

        // Kiểm tra xem hash có trùng với bản ghi khác không
        $conflict = Redirect::where('old_url_hash', $hash)
            ->where('id', '!=', $redirect->id)
            ->exists();

        if ($conflict) {
            $msg = 'Link cũ này đã được sử dụng bởi một bản ghi chuyển hướng khác!';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->with('error', $msg)->withInput();
        }

        $redirect->update([
            'old_url' => $oldUrl,
            'old_url_hash' => $hash,
            'new_url' => $newUrl,
            'status_code' => (int) $request->input('status_code', 301),
            'is_active' => $request->boolean('is_active', true),
            'note' => $request->input('note'),
        ]);

        Cache::forget('admin:redirects:stats');

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Đã cập nhật chuyển hướng thành công!']);
        }

        return back()->with('success', 'Đã cập nhật chuyển hướng thành công!');
    }

    /**
     * Bật/tắt trạng thái kích hoạt qua AJAX
     */
    public function toggleActive(Redirect $redirect): JsonResponse
    {
        $redirect->is_active = !$redirect->is_active;
        $redirect->save();

        Cache::forget('admin:redirects:stats');

        return response()->json([
            'success' => true,
            'is_active' => $redirect->is_active,
            'message' => $redirect->is_active ? 'Đã kích hoạt chuyển hướng.' : 'Đã tạm dừng chuyển hướng.',
        ]);
    }

    /**
     * Xóa 1 chuyển hướng
     */
    public function destroy(Redirect $redirect): RedirectResponse|JsonResponse
    {
        $redirect->delete();
        Cache::forget('admin:redirects:stats');

        if (request()->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Đã xóa chuyển hướng thành công!']);
        }

        return back()->with('success', 'Đã xóa chuyển hướng thành công!');
    }

    /**
     * Xóa hàng loạt
     */
    public function bulkDestroy(Request $request): RedirectResponse|JsonResponse
    {
        $ids = $request->input('ids');
        if (empty($ids) || !is_array($ids)) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Chưa chọn mục nào để xóa.'], 422);
            }
            return back()->with('error', 'Chưa chọn mục nào để xóa.');
        }

        $deletedCount = Redirect::whereIn('id', $ids)->delete();
        Redirect::clearRedirectCache();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Đã xóa thành công {$deletedCount} chuyển hướng!",
                'count' => $deletedCount,
            ]);
        }

        return back()->with('success', "Đã xóa thành công {$deletedCount} chuyển hướng!");
    }

    /**
     * Nhập dữ liệu theo batch (Hỗ trợ hàng triệu dòng qua client chunking, siêu tiết kiệm RAM)
     */
    public function importBatch(Request $request): JsonResponse
    {
        $rows = $request->input('rows');
        if (!is_array($rows) || empty($rows)) {
            return response()->json(['success' => false, 'message' => 'Không có dữ liệu trong lô gửi lên.'], 422);
        }

        $mapping = $request->input('mapping', []);
        $idCol = $mapping['id'] ?? 'ID';
        $oldUrlCol = $mapping['old_url'] ?? 'Link cũ';
        $newUrlCol = $mapping['new_url'] ?? 'Link mới';
        $noteCol = $mapping['note'] ?? 'Ghi chú';

        $insertedCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;

        $now = now()->toDateTimeString();
        $batchUpserts = [];
        $hashesInThisBatch = [];

        foreach ($rows as $row) {
            // Lấy ID nếu có
            $id = isset($row[$idCol]) ? trim((string) $row[$idCol]) : '';
            $oldUrl = isset($row[$oldUrlCol]) ? trim((string) $row[$oldUrlCol]) : '';
            $newUrl = isset($row[$newUrlCol]) ? trim((string) $row[$newUrlCol]) : '';
            $note = isset($row[$noteCol]) ? trim((string) $row[$noteCol]) : null;

            if ($oldUrl === '' || $newUrl === '') {
                $skippedCount++;
                continue;
            }

            $normalizedOld = Redirect::normalizePath($oldUrl);
            $hash = md5($normalizedOld);

            // Tránh trùng lặp hash trong chính batch này
            if (isset($hashesInThisBatch[$hash])) {
                $skippedCount++;
                continue;
            }
            $hashesInThisBatch[$hash] = true;

            $hasValidId = is_numeric($id) && (int) $id > 0;

            if ($hasValidId) {
                // Quy tắc: Nếu có ID -> Cập nhật theo ID
                $updated = DB::table('redirects')
                    ->where('id', (int) $id)
                    ->update([
                        'old_url' => $oldUrl,
                        'old_url_hash' => $hash,
                        'new_url' => $newUrl,
                        'status_code' => 301,
                        'note' => $note,
                        'updated_at' => $now,
                    ]);

                if ($updated) {
                    $updatedCount++;
                } else {
                    // ID không tìm thấy trong DB -> Tạo mới
                    $batchUpserts[] = [
                        'old_url' => $oldUrl,
                        'old_url_hash' => $hash,
                        'new_url' => $newUrl,
                        'status_code' => 301,
                        'is_active' => 1,
                        'hits' => 0,
                        'note' => $note,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            } else {
                // Không có ID -> Tạo mới (hoặc cập nhật nếu link cũ đã tồn tại)
                $batchUpserts[] = [
                    'old_url' => $oldUrl,
                    'old_url_hash' => $hash,
                    'new_url' => $newUrl,
                    'status_code' => 301,
                    'is_active' => 1,
                    'hits' => 0,
                    'note' => $note,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        // Thực thi upsert theo lô cực nhanh bằng 1 câu lệnh MySQL duy nhất
        if (!empty($batchUpserts)) {
            DB::table('redirects')->upsert(
                $batchUpserts,
                ['old_url_hash'], // Khóa duy nhất để xác định trùng
                ['old_url', 'new_url', 'note', 'updated_at'] // Các cột cần cập nhật nếu đã có
            );
            $insertedCount += count($batchUpserts);
        }

        Redirect::clearRedirectCache();

        return response()->json([
            'success' => true,
            'inserted' => $insertedCount,
            'updated' => $updatedCount,
            'skipped' => $skippedCount,
            'total_batch' => count($rows),
        ]);
    }

    /**
     * Xuất file Excel (.xlsx) hoặc CSV dạng Streaming
     */
    public function export(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $selectedColumns = $request->input('columns', ['ID', 'Link cũ', 'Link mới', 'Lượt kích hoạt', 'Trạng thái', 'Ngày tạo']);
        if (!is_array($selectedColumns) || empty($selectedColumns)) {
            $selectedColumns = ['ID', 'Link cũ', 'Link mới', 'Lượt kích hoạt', 'Trạng thái', 'Ngày tạo'];
        }

        $format = strtolower((string) $request->input('format', 'xlsx'));
        if (!in_array($format, ['xlsx', 'csv'], true)) {
            $format = 'xlsx';
        }

        $query = Redirect::query();

        if ($request->filled('search')) {
            $keyword = trim((string) $request->input('search'));
            $query->where(function ($q) use ($keyword) {
                if (is_numeric($keyword)) {
                    $q->orWhere('id', (int) $keyword);
                }
                $q->orWhere('old_url', 'LIKE', "%{$keyword}%")
                  ->orWhere('new_url', 'LIKE', "%{$keyword}%")
                  ->orWhere('note', 'LIKE', "%{$keyword}%");
            });
        }

        if ($request->filled('status')) {
            $status = $request->input('status');
            if ($status === 'active' || $status === '1') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive' || $status === '0') {
                $query->where('is_active', false);
            }
        }

        $timestamp = date('Y-m-d_His');

        // 1. XUẤT ĐỊNH DẠNG MICROSOFT EXCEL (.XLSX)
        if ($format === 'xlsx') {
            // Kiểm tra xem server có extension zip hay không
            if (!extension_loaded('zip') || !class_exists(\ZipArchive::class)) {
                // Tự động chuyển sang định dạng CSV để đảm bảo tải thành công 100% không bị file hỏng
                $format = 'csv';
            } else {
                $filename = "redirects_export_{$timestamp}.xlsx";
                $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();
                $sheet->setTitle('Chuyển Hướng 301');

                // Ghi Header
                $sheet->fromArray([$selectedColumns], null, 'A1');

                // Định dạng Header đẹp (in đậm, nền màu nhạt)
                $lastColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($selectedColumns));
                $headerRange = "A1:{$lastColLetter}1";
                $sheet->getStyle($headerRange)->getFont()->setBold(true);
                $sheet->getStyle($headerRange)->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFF1F5F9');

                $rowIndex = 2;
                $query->orderBy('id', 'asc')->chunkById(1000, function ($redirects) use ($sheet, $selectedColumns, &$rowIndex) {
                    $chunkData = [];
                    foreach ($redirects as $item) {
                        $row = [];
                        foreach ($selectedColumns as $col) {
                            $row[] = match ($col) {
                                'ID' => $item->id,
                                'Link cũ' => $item->old_url,
                                'Link mới' => $item->new_url,
                                'Mã HTTP' => $item->status_code,
                                'Trạng thái' => $item->is_active ? 'Kích hoạt' : 'Tạm dừng',
                                'Lượt kích hoạt' => $item->hits,
                                'Lần truy cập cuối' => $item->last_accessed_at ? $item->last_accessed_at->format('d/m/Y H:i:s') : 'Chưa có',
                                'Ghi chú' => $item->note ?? '',
                                'Ngày tạo' => $item->created_at ? $item->created_at->format('d/m/Y H:i:s') : '',
                                default => '',
                            };
                        }
                        $chunkData[] = $row;
                    }

                    if (!empty($chunkData)) {
                        $sheet->fromArray($chunkData, null, "A{$rowIndex}");
                        $rowIndex += count($chunkData);
                    }
                });

                // Auto-fit column widths
                foreach (range(1, count($selectedColumns)) as $colIndex) {
                    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                    $sheet->getColumnDimension($colLetter)->setAutoSize(true);
                }

                $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_');
                $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
                $writer->save($tempFile);

                return response()->download($tempFile, $filename, [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ])->deleteFileAfterSend(true);
            }
        }

        // 2. XUẤT ĐỊNH DẠNG CSV (UTF-8 BOM)
        $filename = "redirects_export_{$timestamp}.csv";

        return response()->stream(function () use ($query, $selectedColumns) {
            $handle = fopen('php://output', 'w');

            // Ghi UTF-8 BOM để Excel hiển thị tiếng Việt chuẩn
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Ghi Header
            fputcsv($handle, $selectedColumns);

            // Stream từng chunk 1000 dòng để RAM luôn ở mức dưới 15MB
            $query->orderBy('id', 'asc')->chunkById(1000, function ($redirects) use ($handle, $selectedColumns) {
                foreach ($redirects as $item) {
                    $row = [];
                    foreach ($selectedColumns as $col) {
                        $row[] = match ($col) {
                            'ID' => $item->id,
                            'Link cũ' => $item->old_url,
                            'Link mới' => $item->new_url,
                            'Mã HTTP' => $item->status_code,
                            'Trạng thái' => $item->is_active ? 'Kích hoạt' : 'Tạm dừng',
                            'Lượt kích hoạt' => $item->hits,
                            'Lần truy cập cuối' => $item->last_accessed_at ? $item->last_accessed_at->format('d/m/Y H:i:s') : 'Chưa có',
                            'Ghi chú' => $item->note ?? '',
                            'Ngày tạo' => $item->created_at ? $item->created_at->format('d/m/Y H:i:s') : '',
                            default => '',
                        };
                    }
                    fputcsv($handle, $row);
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
}
