<?php

namespace App\Console\Commands;

use App\Services\Media\MediaScannerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeduplicateMediaImagesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'media:deduplicate
                            {--dry-run : Chỉ kiểm tra và hiển thị các bản ghi thừa, không xóa}
                            {--force : Thực thi xóa trực tiếp không cần hỏi xác nhận}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Quét và xóa các bản ghi ảnh thừa (trùng lặp URL/path) trong DB, tuyệt đối bảo vệ bản ghi chính và file vật lý';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        $this->info("==========================================================");
        $this->info("   CÔNG CỤ DỌN DẸP BẢN GHI ẢNH TRÙNG LẶP TRONG DATABASE   ");
        $this->info("==========================================================");
        $this->line("⚠️  <comment>Nguyên tắc an toàn:</comment>");
        $this->line("   - <info>KHÔNG BAO GIỜ</info> xóa các bản ghi đang được dùng (bài viết, sản phẩm, v.v.).");
        $this->line("   - <info>KHÔNG BAO GIỜ</info> xóa file vật lý trên ổ cứng (chỉ xóa dòng thừa trong database).");
        $this->newLine();

        $totalImages = DB::table('images')->count();
        $assignedImages = DB::table('images')
            ->where(function ($q) {
                $q->whereNotNull('entity_type')
                  ->orWhereNotNull('product_id');
            })
            ->count();
        $unassignedImages = DB::table('images')
            ->whereNull('entity_type')
            ->whereNull('product_id')
            ->count();

        $this->line("📊 Tổng số bản ghi ảnh hiện tại: <info>" . number_format($totalImages) . "</info>");
        $this->line("   - Đang dùng (gắn đối tượng):     <info>" . number_format($assignedImages) . "</info> (Được bảo vệ 100%)");
        $this->line("   - Chưa gắn đối tượng (thư viện): <comment>" . number_format($unassignedImages) . "</comment>");
        $this->newLine();

        $this->comment("🔍 Đang phân tích các bản ghi thừa...");

        $assetExpr = "COALESCE(NULLIF(path, ''), NULLIF(url, ''))";

        // Nhóm 1: Bản ghi 'Chưa gắn đối tượng' mà đường dẫn file đã có 1 bản ghi 'Đang dùng'
        $assignedKeysQuery = DB::table('images')
            ->where(function ($q) {
                $q->whereNotNull('entity_type')
                  ->orWhereNotNull('product_id');
            })
            ->whereRaw("{$assetExpr} IS NOT NULL")
            ->selectRaw("DISTINCT {$assetExpr} AS asset_key");

        $type1Records = DB::table('images')
            ->whereNull('entity_type')
            ->whereNull('product_id')
            ->whereIn(DB::raw($assetExpr), $assignedKeysQuery)
            ->select(['id', 'name', 'path', 'url'])
            ->get();
        $type1Ids = $type1Records->pluck('id')->all();

        // Nhóm 2: Nhiều bản ghi 'Chưa gắn đối tượng' cùng trỏ vào 1 file (giữ lại 1 ID đại diện, xóa các ID thừa)
        $unassignedAssetsBase = DB::table('images')
            ->whereNull('entity_type')
            ->whereNull('product_id')
            ->whereNotIn('id', $type1Ids ?: [0])
            ->whereRaw("{$assetExpr} IS NOT NULL")
            ->selectRaw("id, {$assetExpr} AS asset_key");

        $duplicateKeys = DB::query()
            ->fromSub($unassignedAssetsBase, 't')
            ->select('asset_key')
            ->groupBy('asset_key')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('asset_key')
            ->all();

        $keepIds = [];
        if (!empty($duplicateKeys)) {
            $keepIds = DB::query()
                ->fromSub($unassignedAssetsBase, 't')
                ->whereIn('asset_key', $duplicateKeys)
                ->groupBy('asset_key')
                ->selectRaw('MIN(id) AS keep_id')
                ->pluck('keep_id')
                ->all();
        }

        $type2Records = collect();
        if (!empty($duplicateKeys)) {
            $type2Records = DB::table('images')
                ->whereNull('entity_type')
                ->whereNull('product_id')
                ->whereNotIn('id', array_merge($type1Ids ?: [0], $keepIds ?: [0]))
                ->whereIn(DB::raw($assetExpr), $duplicateKeys)
                ->select(['id', 'name', 'path', 'url'])
                ->get();
        }
        $type2Ids = $type2Records->pluck('id')->all();

        $allDuplicateIds = array_values(array_unique(array_merge($type1Ids, $type2Ids)));
        $totalDuplicates = count($allDuplicateIds);

        $this->line("Kết quả quét:");
        $this->line(" - Nhóm 1: Bản ghi chưa gắn bị trùng với bản ghi đang dùng: <comment>" . number_format(count($type1Ids)) . "</comment> bản ghi");
        $this->line(" - Nhóm 2: Bản ghi chưa gắn bị trùng lặp với nhau:            <comment>" . number_format(count($type2Ids)) . "</comment> bản ghi");
        $this->line(" ➡️  Tổng số bản ghi thừa cần xóa: <error>" . number_format($totalDuplicates) . "</error> bản ghi");
        $this->newLine();

        if ($totalDuplicates === 0) {
            $this->info("✅ Cơ sở dữ liệu sạch đẹp! Không tìm thấy bản ghi ảnh trùng lặp nào.");
            return Command::SUCCESS;
        }

        // Hiển thị mẫu
        $sampleRecords = $type1Records->concat($type2Records)->take(5);
        $this->comment("Ví dụ 5 bản ghi thừa sẽ được xử lý:");
        $headers = ['ID', 'Tên file', 'Đường dẫn / URL'];
        $rows = [];
        foreach ($sampleRecords as $r) {
            $rows[] = [$r->id, $r->name ?: '-', $r->path ?: $r->url];
        }
        $this->table($headers, $rows);
        $this->newLine();

        if ($isDryRun) {
            $this->warn("⚠️  Bạn đang chạy ở chế độ [DRY-RUN] (chỉ kiểm tra).");
            $this->info("Chưa có bản ghi nào bị xóa. Để thực hiện xóa thực tế, hãy chạy lệnh:");
            $this->line("   <comment>php artisan media:deduplicate</comment>");
            return Command::SUCCESS;
        }

        if (!$force) {
            if (!$this->confirm("Bạn có chắc chắn muốn xóa " . number_format($totalDuplicates) . " bản ghi ảnh thừa này khỏi database không?", true)) {
                $this->warn("Đã hủy thao tác.");
                return Command::SUCCESS;
            }
        }

        $this->info("Đang tiến hành xóa {$totalDuplicates} bản ghi thừa trong DB theo từng chunk...");
        $deleted = 0;
        $chunks = array_chunk($allDuplicateIds, 500);

        foreach ($chunks as $idx => $chunk) {
            $count = DB::table('images')->whereIn('id', $chunk)->delete();
            $deleted += $count;
            $this->line(" - Đã xóa batch " . ($idx + 1) . "/" . count($chunks) . " (" . $deleted . "/" . $totalDuplicates . " bản ghi)");
        }

        MediaScannerService::clearMissingCache();

        $this->newLine();
        $this->info("==========================================================");
        $this->info("✅ ĐÃ XÓA THÀNH CÔNG " . number_format($deleted) . " BẢN GHI THỪA KHỎI DATABASE!");
        $this->line(" - Toàn bộ file ảnh vật lý trên đĩa: <info>ĐƯỢC GIỮ NGUYÊN 100%</info>");
        $this->line(" - Toàn bộ bản ghi bài viết/sản phẩm: <info>ĐƯỢC GIỮ NGUYÊN 100%</info>");
        $this->line(" - Nhãn 'Dùng chung nhiều nơi' sai lệch: <info>ĐÃ ĐƯỢC GIẢI PHÓNG</info>");
        $this->info("==========================================================");

        return Command::SUCCESS;
    }
}
