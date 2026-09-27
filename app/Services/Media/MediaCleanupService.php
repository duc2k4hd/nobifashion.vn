<?php

namespace App\Services\Media;

use App\Models\Image;
use Illuminate\Support\Facades\DB;

class MediaCleanupService
{
    public function __construct(
        protected MediaScannerService $scanner,
        protected FileHelperService $files
    ) {
    }

    /**
     * Preview các bản ghi trong CSDL bị thiếu file vật lý trên ổ đĩa
     * (TUYỆT ĐỐI KHÔNG xóa file vật lý hay bản ghi chưa gán)
     *
     * @return array<string, mixed>
     */
    public function preview(): array
    {
        MediaScannerService::clearMissingCache();
        $missingIds = $this->scanner->getMissingFileImageIds();
        $count = count($missingIds);

        $samples = [];
        if ($count > 0) {
            $sampleRecords = Image::query()
                ->whereIn('id', array_slice($missingIds, 0, 10))
                ->get(['id', 'name', 'path', 'url', 'entity_type', 'entity_id']);

            foreach ($sampleRecords as $record) {
                $samples[] = [
                    'id' => $record->id,
                    'file_name' => $record->name ?: basename($record->path ?: $record->url ?: ''),
                    'path' => $record->path ?: $record->url,
                    'entity_type' => $record->entity_type,
                    'entity_id' => $record->entity_id,
                ];
            }
        }

        return [
            'success' => true,
            'dry_run' => true,
            'missing_count' => $count,
            'database_rows_to_delete' => $count,
            'physical_files_to_delete' => 0,
            'samples' => $samples,
            'message' => $count > 0
                ? "Phát hiện {$count} bản ghi trong CSDL bị thiếu file vật lý trên ổ đĩa."
                : 'Không có bản ghi nào bị thiếu file vật lý.',
        ];
    }

    /**
     * Thực hiện xóa các bản ghi trong DB bị thiếu file vật lý
     * (Chia batch an toàn, tuyệt đối không chạm vào file ổ cứng)
     *
     * @return array<string, mixed>
     */
    public function cleanup(): array
    {
        MediaScannerService::clearMissingCache();
        $missingIds = $this->scanner->getMissingFileImageIds();
        $totalToDelete = count($missingIds);
        $deletedCount = 0;

        if ($totalToDelete > 0) {
            foreach (array_chunk($missingIds, 500) as $chunk) {
                $deletedCount += DB::table('images')->whereIn('id', $chunk)->delete();
            }
        }

        MediaScannerService::clearMissingCache();

        return [
            'success' => true,
            'dry_run' => false,
            'deleted_rows' => $deletedCount,
            'database_rows_to_delete' => 0,
            'physical_files_to_delete' => 0,
            'message' => $deletedCount > 0
                ? "Đã dọn dẹp thành công {$deletedCount} bản ghi thiếu file vật lý khỏi CSDL."
                : 'Không có bản ghi nào cần dọn dẹp.',
        ];
    }
}
