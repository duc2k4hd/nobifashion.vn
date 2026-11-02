<?php

namespace App\Http\Controllers\Admins\Reels;

use App\Http\Controllers\Controller;
use App\Models\ReelImportJob;
use App\Services\ReelsAdmin\ReelImportService;
use App\Services\ReelsAdmin\ReelsAdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReelImportController extends Controller
{
    protected ReelImportService $importService;
    protected ReelsAdminService $adminService;

    public function __construct(ReelImportService $importService, ReelsAdminService $adminService)
    {
        $this->importService = $importService;
        $this->adminService = $adminService;
    }

    /**
     * Màn hình Import Center & Lịch sử tác vụ import
     */
    public function index(): View
    {
        $jobs = ReelImportJob::with(['creator'])
            ->latest('id')
            ->paginate(15);

        return view('admins.reels.import.index', compact('jobs'));
    }

    /**
     * Tải file CSV mẫu chuẩn định dạng
     */
    public function sampleCsv(): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="nobi_reels_import_sample.csv"',
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');
            // Ghi BOM UTF-8 để mở tiếng Việt không bị lỗi font trên Excel
            fwrite($handle, "\xEF\xBB\xBF");

            // Header columns
            fputcsv($handle, [
                'title',
                'video_url',
                'caption',
                'category',
                'hashtags',
                'brand_id',
                'duration',
                'poster_url',
                'external_id',
                'product_ids',
                'status',
            ]);

            // Sample rows
            fputcsv($handle, [
                'Set Đồ Đi Biển Nữ Mùa Hè 2026',
                'https://cdn.nobifashion.vn/reels/summer_vibes_2026.mp4',
                'Gợi ý phối đồ đi biển siêu xinh và tôn dáng cho nàng #NobiSummer',
                'trending',
                '#NobiFashion,#Summer2026,#OutfitOfTheDay',
                '1',
                '15',
                'https://cdn.nobifashion.vn/reels/posters/summer_vibes_poster.jpg',
                'R2_NOBI_SUMMER_01',
                '1,2',
                'published',
            ]);

            fputcsv($handle, [
                'Áo Polo Nam Form Regular Cao Cấp',
                'https://cdn.nobifashion.vn/reels/polo_men_2026.mp4',
                'Chất liệu cotton mềm mịn, thoáng mát suốt cả ngày',
                'new',
                '#PoloNam,#NobiMen,#Fashion',
                '',
                '20',
                'https://cdn.nobifashion.vn/reels/posters/polo_men_poster.jpg',
                'R2_NOBI_POLO_02',
                '',
                'published',
            ]);

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * API xem trước dữ liệu khi chọn file (Preview)
     */
    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:20480'], // tối đa 20MB
        ]);

        $file = $request->file('file');
        $previewData = $this->importService->parseFile($file, 10);

        return response()->json([
            'success' => true,
            'data' => $previewData,
        ]);
    }

    /**
     * Xử lý thực hiện Import file
     */
    public function process(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:51200'], // tối đa 50MB
            'duplicate_action' => ['required', 'in:skip,update'],
            'status' => ['required', 'in:published,draft'],
            'category' => ['required', 'string'],
        ]);

        $file = $request->file('file');
        $adminId = auth('web')->id() ?: (auth('account')->id() ?: 1);

        try {
            $job = $this->importService->processImport(
                $file,
                [
                    'duplicate_action' => $request->input('duplicate_action'),
                    'status' => $request->input('status'),
                    'category' => $request->input('category'),
                ],
                $adminId
            );

            $this->adminService->logAudit('import_reels_file', [
                'job_id' => $job->id,
                'total' => $job->total_rows,
                'successful' => $job->successful_rows,
                'failed' => $job->failed_rows,
            ]);

            return redirect()->route('admin.reels.import.index')
                ->with('success', "Import hoàn tất! Đã thêm/cập nhật thành công {$job->successful_rows} video, bỏ qua {$job->skipped_rows}, lỗi {$job->failed_rows}.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Lỗi khi xử lý import: ' . $e->getMessage());
        }
    }
}

