<?php

namespace App\Services\ReelsAdmin;

use App\Models\Brand;
use App\Models\Product;
use App\Models\Reel;
use App\Models\ReelImportJob;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReelImportService
{
    /**
     * Phân tích cấu trúc file CSV hoặc Excel và trả về danh sách bản ghi preview
     */
    public function parseFile(UploadedFile $file, int $previewLimit = 10): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $rows = [];
        $headers = [];

        if ($extension === 'csv' || $extension === 'txt') {
            $handle = fopen($file->getRealPath(), 'r');
            if ($handle !== false) {
                // Đọc BOM UTF-8 nếu có
                $bom = fread($handle, 3);
                if ($bom !== "\xEF\xBB\xBF") {
                    rewind($handle);
                }

                $rawHeaders = fgetcsv($handle, 4096, ',');
                if ($rawHeaders) {
                    $headers = array_map(function ($h) {
                        return trim(strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', str_replace(' ', '_', $h))));
                    }, $rawHeaders);

                    $count = 0;
                    while (($data = fgetcsv($handle, 4096, ',')) !== false) {
                        if (count($data) === 1 && $data[0] === null) continue;
                        $row = [];
                        foreach ($headers as $index => $header) {
                            $row[$header] = isset($data[$index]) ? trim($data[$index]) : '';
                        }
                        $rows[] = $row;
                        $count++;
                        if ($count >= $previewLimit) break;
                    }
                }
                fclose($handle);
            }
        }

        return [
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'headers' => $headers,
            'rows' => $rows,
            'preview_rows' => $rows,
            'total_rows' => count($rows),
            'total_preview' => count($rows),
        ];
    }

    /**
     * Xử lý import batch dữ liệu từ file hoặc mảng rows
     */
    public function processImport(UploadedFile $file, array $options, int $adminId): ReelImportJob
    {
        $duplicateAction = $options['duplicate_action'] ?? 'skip'; // skip | update
        $defaultStatus = $options['status'] ?? 'published';
        $defaultCategory = $options['category'] ?? 'trending';

        $importJob = ReelImportJob::create([
            'file_name' => $file->getClientOriginalName(),
            'source_type' => 'csv',
            'status' => 'processing',
            'created_by' => $adminId,
            'started_at' => now(),
        ]);

        $errorsLog = [];
        $totalRows = 0;
        $processed = 0;
        $successful = 0;
        $skipped = 0;
        $failed = 0;

        $handle = fopen($file->getRealPath(), 'r');
        if ($handle !== false) {
            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") {
                rewind($handle);
            }

            $rawHeaders = fgetcsv($handle, 4096, ',');
            if ($rawHeaders) {
                $headers = array_map(function ($h) {
                    return trim(strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', str_replace(' ', '_', $h))));
                }, $rawHeaders);

                $batchData = [];
                $rowNumber = 1;

                while (($data = fgetcsv($handle, 4096, ',')) !== false) {
                    $rowNumber++;
                    if (count($data) === 1 && $data[0] === null) continue;

                    $totalRows++;
                    $row = [];
                    foreach ($headers as $index => $header) {
                        $row[$header] = isset($data[$index]) ? trim($data[$index]) : '';
                    }

                    // Validate các trường bắt buộc
                    $title = $row['title'] ?? ($row['tieu_de'] ?? '');
                    $videoUrl = $row['video_url'] ?? ($row['url_video'] ?? ($row['duong_dan_video'] ?? ''));

                    if (empty($title) || empty($videoUrl)) {
                        $failed++;
                        $errorsLog[] = [
                            'row' => $rowNumber,
                            'error' => 'Thiếu tiêu đề (title) hoặc đường dẫn video (video_url)',
                            'data' => $row,
                        ];
                        continue;
                    }

                    $externalId = $row['external_id'] ?? ($row['id_ngoai'] ?? null);
                    $slug = !empty($row['slug']) ? Str::slug($row['slug']) : Str::slug($title);

                    // Kiểm tra trùng lặp
                    $existing = null;
                    if (!empty($externalId)) {
                        $existing = Reel::where('external_id', $externalId)->first();
                    }
                    if (!$existing && !empty($slug)) {
                        $existing = Reel::where('slug', $slug)->first();
                    }

                    if ($existing) {
                        if ($duplicateAction === 'skip') {
                            $skipped++;
                            continue;
                        }
                    }

                    // Chuẩn hóa dữ liệu lưu
                    $hashtags = [];
                    if (!empty($row['hashtags'])) {
                        $rawTags = explode(',', $row['hashtags']);
                        foreach ($rawTags as $tag) {
                            $tag = trim($tag);
                            if ($tag !== '') {
                                $hashtags[] = str_starts_with($tag, '#') ? $tag : '#' . $tag;
                            }
                        }
                    }

                    // Tìm Brand nếu có
                    $brandId = null;
                    if (!empty($row['brand_id']) && is_numeric($row['brand_id'])) {
                        $brandId = (int) $row['brand_id'];
                    } elseif (!empty($row['brand']) || !empty($row['thuong_hieu'])) {
                        $brandName = trim($row['brand'] ?? $row['thuong_hieu']);
                        $brand = Brand::where('name', $brandName)->orWhere('slug', Str::slug($brandName))->first();
                        if ($brand) $brandId = $brand->id;
                    }

                    try {
                        DB::beginTransaction();

                        if ($existing) {
                            $existing->update([
                                'title' => $title,
                                'caption' => $row['caption'] ?? $row['mo_ta'] ?? $existing->caption,
                                'video_url' => $videoUrl,
                                'poster_url' => $row['poster_url'] ?? $existing->poster_url,
                                'thumbnail_url' => $row['thumbnail_url'] ?? $existing->thumbnail_url,
                                'duration' => !empty($row['duration']) ? (int) $row['duration'] : $existing->duration,
                                'brand_id' => $brandId ?: $existing->brand_id,
                                'category' => $row['category'] ?? $existing->category,
                                'hashtags' => !empty($hashtags) ? $hashtags : $existing->hashtags,
                                'status' => $row['status'] ?? $existing->status,
                            ]);
                            $successful++;
                        } else {
                            // Bảo đảm slug không trùng
                            $uniqueSlug = $slug;
                            $suffix = 1;
                            while (Reel::where('slug', $uniqueSlug)->exists()) {
                                $uniqueSlug = $slug . '-' . $suffix;
                                $suffix++;
                            }

                            $newReel = Reel::create([
                                'external_id' => !empty($externalId) ? $externalId : 'R2_' . Str::random(12),
                                'title' => $title,
                                'slug' => $uniqueSlug,
                                'caption' => $row['caption'] ?? ($row['mo_ta'] ?? null),
                                'video_url' => $videoUrl,
                                'poster_url' => $row['poster_url'] ?? null,
                                'thumbnail_url' => $row['thumbnail_url'] ?? null,
                                'duration' => !empty($row['duration']) ? (int) $row['duration'] : 0,
                                'width' => 1080,
                                'height' => 1920,
                                'aspect_ratio' => '9:16',
                                'playback_type' => 'direct_mp4',
                                'video_provider' => 'cloudflare_r2',
                                'author_name' => $row['author_name'] ?? 'Nobi Fashion',
                                'author_handle' => $row['author_handle'] ?? '@nobifashion',
                                'brand_id' => $brandId,
                                'category' => $row['category'] ?? $defaultCategory,
                                'hashtags' => $hashtags,
                                'status' => $row['status'] ?? $defaultStatus,
                                'published_at' => ($defaultStatus === 'published') ? now() : null,
                            ]);

                            // Liên kết sản phẩm nếu có cột product_ids hoặc sku_list
                            if (!empty($row['product_ids']) || !empty($row['skus'])) {
                                $this->attachProductsToReel($newReel, $row['product_ids'] ?? '', $row['skus'] ?? '');
                            }

                            $successful++;
                        }

                        DB::commit();
                    } catch (\Throwable $e) {
                        DB::rollBack();
                        $failed++;
                        $errorsLog[] = [
                            'row' => $rowNumber,
                            'error' => $e->getMessage(),
                            'data' => $row,
                        ];
                    }

                    $processed++;
                }
            }
            fclose($handle);
        }

        $importJob->update([
            'total_rows' => $totalRows,
            'processed_rows' => $processed,
            'successful_rows' => $successful,
            'failed_rows' => $failed,
            'skipped_rows' => $skipped,
            'status' => $failed === 0 ? 'completed' : ($successful > 0 ? 'completed' : 'failed'),
            'errors_log' => count($errorsLog) > 0 ? array_slice($errorsLog, 0, 100) : null,
            'completed_at' => now(),
        ]);

        return $importJob;
    }

    /**
     * Gắn sản phẩm vào video theo danh sách ID hoặc SKU
     */
    protected function attachProductsToReel(Reel $reel, string $productIdsStr, string $skusStr): void
    {
        $productIds = [];

        if (!empty($productIdsStr)) {
            $ids = array_filter(array_map('trim', explode(',', $productIdsStr)));
            $foundIds = Product::whereIn('id', $ids)->pluck('id')->toArray();
            $productIds = array_merge($productIds, $foundIds);
        }

        if (!empty($skusStr)) {
            $skus = array_filter(array_map('trim', explode(',', $skusStr)));
            $foundBySku = Product::whereIn('sku', $skus)->pluck('id')->toArray();
            $productIds = array_merge($productIds, $foundBySku);
        }

        $productIds = array_unique($productIds);
        if (!empty($productIds)) {
            $syncData = [];
            foreach ($productIds as $idx => $pid) {
                $syncData[$pid] = ['order' => $idx + 1];
            }
            $reel->products()->syncWithoutDetaching($syncData);
        }
    }
}

