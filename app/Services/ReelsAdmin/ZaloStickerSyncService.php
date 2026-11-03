<?php

namespace App\Services\ReelsAdmin;

use App\Models\CommentSticker;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ZaloStickerSyncService
{
    protected string $apiUrl = 'https://stickers.zaloapp.com/sticker';

    /**
     * Lấy danh sách các bộ Sticker từ Zalo (Tab: all)
     */
    public function getZaloPacks(bool $forceRefresh = false): array
    {
        $cacheKey = 'zalo_stickers_all_packs';
        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, 1800, function () {
            try {
                $response = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept' => 'application/json, text/plain, */*',
                    'Accept-Encoding' => 'gzip, deflate, br',
                ])->timeout(20)->get($this->apiUrl);

                if (!$response->successful()) {
                    return [];
                }

                $data = $response->json();
                $all = $data['value']['all'] ?? [];

                // Kiểm tra xem bộ nào đã được đồng bộ vào DB
                $existingCategories = CommentSticker::where('category', 'like', 'zalo-%')
                    ->distinct()
                    ->pluck('category')
                    ->toArray();

                $syncedMap = [];
                foreach ($existingCategories as $cat) {
                    $syncedMap[$cat] = true;
                }

                $formatted = [];
                foreach ($all as $item) {
                    $slug = Str::slug($item['name'] ?? 'pack');
                    if (empty($slug)) {
                        $slug = 'pack_' . substr($item['id'], 0, 8);
                    }
                    $category = 'zalo-' . $slug;

                    $formatted[] = [
                        'id' => $item['id'] ?? '',
                        'name' => $item['name'] ?? 'Bộ Sticker Zalo',
                        'slug' => $slug,
                        'category' => $category,
                        'thumbImg' => str_replace(' ', '%20', $item['thumbImg'] ?? ''),
                        'iconUrl' => str_replace(' ', '%20', $item['iconUrl'] ?? ''),
                        'totalImage' => $item['totalImage'] ?? '9 sticker',
                        'source' => $item['source'] ?? 'Zalo Store',
                        'is_synced' => isset($syncedMap[$category]),
                    ];
                }

                return $formatted;
            } catch (\Throwable $e) {
                \Log::error('Lỗi lấy danh sách Zalo Stickers: ' . $e->getMessage());
                return [];
            }
        });
    }

    /**
     * Tải và cắt 1 bộ Sticker Zalo thành 9 ô sticker con
     */
    public function syncPackById(string $packId): array
    {
        $packs = $this->getZaloPacks();
        $target = null;
        foreach ($packs as $p) {
            if ($p['id'] === $packId) {
                $target = $p;
                break;
            }
        }

        if (!$target) {
            return [
                'success' => false,
                'message' => "Không tìm thấy bộ sticker có ID: {$packId} trên Zalo.",
            ];
        }

        return $this->processCutAndSave($target);
    }

    /**
     * Tải ảnh lưới 3x3 và cắt thành 9 sticker con bằng GD
     */
    public function processCutAndSave(array $pack): array
    {
        $thumbUrl = $pack['thumbImg'];
        if (empty($thumbUrl)) {
            return ['success' => false, 'message' => 'Bộ sticker không có ảnh mẫu.'];
        }

        try {
            // Tải ảnh lưới 3x3 về bộ nhớ
            $imageContent = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
            ])->timeout(25)->get($thumbUrl)->body();

            if (empty($imageContent)) {
                return ['success' => false, 'message' => 'Không thể tải ảnh từ Zalo CDN.'];
            }

            $sourceImage = @imagecreatefromstring($imageContent);
            if (!$sourceImage) {
                return ['success' => false, 'message' => 'Dữ liệu ảnh tải về không hợp lệ.'];
            }

            $width = imagesx($sourceImage);
            $height = imagesy($sourceImage);

            if ($width < 30 || $height < 30) {
                imagedestroy($sourceImage);
                return ['success' => false, 'message' => 'Kích thước ảnh quá nhỏ để chia 3x3.'];
            }

            // Tính kích thước mỗi ô con
            $cellW = (int) floor($width / 3);
            $cellH = (int) floor($height / 3);

            $slug = $pack['slug'];
            $targetDir = public_path("clients/assets/img/stickers/zalo/{$slug}");
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }

            $createdCount = 0;
            $stickersInfo = [];

            // Duyệt 3 hàng x 3 cột (9 ô)
            for ($row = 0; $row < 3; $row++) {
                for ($col = 0; $col < 3; $col++) {
                    $index = ($row * 3) + $col + 1;
                    $destImage = imagecreatetruecolor($cellW, $cellH);

                    // Bảo toàn kênh Alpha (trong suốt)
                    imagealphablending($destImage, false);
                    imagesavealpha($destImage, true);
                    $transparent = imagecolorallocatealpha($destImage, 255, 255, 255, 127);
                    imagefilledrectangle($destImage, 0, 0, $cellW, $cellH, $transparent);

                    // Cắt ô từ ảnh gốc sang ảnh con
                    imagecopyresampled(
                        $destImage,
                        $sourceImage,
                        0,
                        0,
                        $col * $cellW,
                        $row * $cellH,
                        $cellW,
                        $cellH,
                        $cellW,
                        $cellH
                    );

                    $fileName = "sticker_{$index}.png";
                    $destPath = "{$targetDir}/{$fileName}";

                    // Xuất ảnh PNG chất lượng cao
                    imagepng($destImage, $destPath, 6);
                    imagedestroy($destImage);

                    // Đường dẫn tương đối
                    $assetUrl = "/clients/assets/img/stickers/zalo/{$slug}/{$fileName}";
                    $code = "zalo_{$slug}_{$index}";

                    // Lưu vào DB
                    CommentSticker::updateOrCreate(
                        ['code' => $code],
                        [
                            'name' => "{$pack['name']} #{$index}",
                            'asset_url' => $assetUrl,
                            'category' => $pack['category'],
                            'sort_order' => $index,
                            'is_active' => true,
                        ]
                    );

                    $createdCount++;
                    $stickersInfo[] = [
                        'code' => $code,
                        'name' => "{$pack['name']} #{$index}",
                        'url' => $assetUrl,
                    ];
                }
            }

            imagedestroy($sourceImage);

            return [
                'success' => true,
                'pack_id' => $pack['id'],
                'pack_name' => $pack['name'],
                'category' => $pack['category'],
                'created_count' => $createdCount,
                'stickers' => $stickersInfo,
                'message' => "Đã tách thành công {$createdCount} sticker con cho bộ \"{$pack['name']}\".",
            ];
        } catch (\Throwable $e) {
            \Log::error("Lỗi cắt sticker Zalo [{$pack['id']}]: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Lỗi xử lý ảnh: ' . $e->getMessage(),
            ];
        }
    }
}

