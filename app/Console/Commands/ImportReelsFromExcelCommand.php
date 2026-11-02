<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Reel;
use App\Models\ReelProduct;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use openpyxl; // not used in PHP, we will use PhpOffice\PhpSpreadsheet if installed or a Python runner or zip stream

class ImportReelsFromExcelCommand extends Command
{
    protected $signature = 'reels:import-excel {--file= : Path to Excel file}';
    protected $description = 'Import video Reels từ tệp Excel vào cơ sở dữ liệu Nobi Fashion';

    public function handle(): int
    {
        $filePath = $this->option('file') ?: 'D:\\File\\Link video online.xlsx';

        if (!file_exists($filePath)) {
            $this->error("Không tìm thấy tệp Excel tại: {$filePath}");
            return Command::FAILURE;
        }

        $this->info("Đang đọc và xử lý tệp: {$filePath}");

        // Sử dụng Python helper script để đọc XLSX siêu nhanh và chính xác
        $scriptPath = storage_path('app/temp_parse_excel.py');
        $outputPath = storage_path('app/temp_reels_data.json');

        $pyCode = <<<PYTHON
import openpyxl
import json
import re

wb = openpyxl.load_workbook(r'{$filePath}', data_only=True)
sheet = wb.active

items = []
for r in range(2, sheet.max_row + 1):
    val = sheet.cell(r, 2).value
    if val:
        url = str(val).strip()
        filename = url.split('/')[-1]
        m = re.match(r'(\d{4}-\d{2}-\d{2})_([^_]+)_(.+)_(\d+)\.([a-zA-Z0-9]+)', filename)
        if m:
            date, author, slug_part, uid, ext = m.groups()
            items.append({
                'url': url,
                'external_id': uid,
                'date': date,
                'author_raw': author,
                'raw_slug': slug_part,
                'ext': ext
            })
        else:
            items.append({
                'url': url,
                'external_id': None,
                'date': None,
                'author_raw': 'nobifashion',
                'raw_slug': filename.split('.')[0],
                'ext': 'mp4'
            })

with open(r'{$outputPath}', 'w', encoding='utf-8') as f:
    json.dump(items, f, ensure_ascii=False)
print(f"Exported {len(items)} items to JSON")
PYTHON;

        file_put_contents($scriptPath, $pyCode);
        $output = shell_exec("python {$scriptPath}");
        $this->line((string) $output);

        if (!file_exists($outputPath)) {
            $this->error("Không thể xuất dữ liệu từ Python!");
            return Command::FAILURE;
        }

        $jsonData = json_decode(file_get_contents($outputPath), true);
        @unlink($scriptPath);
        @unlink($outputPath);

        if (empty($jsonData)) {
            $this->warn("Tệp Excel không có dòng dữ liệu video hợp lệ!");
            return Command::SUCCESS;
        }

        $this->info("Đã quét thấy " . count($jsonData) . " video. Bắt đầu import vào database...");

        // Lấy sẵn danh sách ID sản phẩm thật từ CSDL để liên kết
        $productIds = Product::query()->active()->pluck('id')->all();
        $totalProducts = count($productIds);

        $imported = 0;
        $skipped = 0;

        $categories = ['trending', 'for_you', 'outfit', 'autumn_winter'];

        $bar = $this->output->createProgressBar(count($jsonData));
        $bar->start();

        foreach ($jsonData as $idx => $row) {
            $url = $row['url'];
            $externalId = $row['external_id'];
            $rawSlug = $row['raw_slug'];
            $date = $row['date'];

            // Kiểm tra trùng lặp qua external_id hoặc video_url
            $exists = false;
            if ($externalId) {
                $exists = Reel::where('external_id', $externalId)->exists();
            }
            if (!$exists) {
                $exists = Reel::where('video_url', $url)->exists();
            }

            if ($exists) {
                $skipped++;
                $bar->advance();
                continue;
            }

            // Tạo tiêu đề đẹp từ raw_slug
            $titleWords = explode('-', $rawSlug);
            $cleanTitle = mb_convert_case(implode(' ', array_slice($titleWords, 0, 15)), MB_CASE_TITLE, 'UTF-8');
            if (mb_strlen($cleanTitle) > 150) {
                $cleanTitle = mb_substr($cleanTitle, 0, 147) . '...';
            }

            // Tạo unique slug (tối đa 180 ký tự để vừa vặn index 191)
            $baseSlug = Str::slug(mb_substr($cleanTitle, 0, 100));
            $slug = $baseSlug;
            $counter = 1;
            while (Reel::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . ($externalId ? substr($externalId, -4) : $counter);
                $counter++;
            }

            $publishedAt = $date ? Carbon::parse($date)->setHour(rand(8, 21))->setMinute(rand(0, 59)) : now()->subDays(rand(1, 30));

            // Tự động phân bổ category xoay vòng
            $cat = $categories[$idx % count($categories)];

            // Hashtags
            $tags = ['#NobiFashion', '#OOTD', '#ThoiTrangNam', '#PhongCach'];
            if ($cat === 'autumn_winter') {
                $tags[] = '#ThuDong2026';
                $tags[] = '#AoKhoac';
            } elseif ($cat === 'outfit') {
                $tags[] = '#PhoiDo';
                $tags[] = '#MixAndMatch';
            }

            $reel = Reel::create([
                'external_id' => $externalId,
                'slug' => $slug,
                'title' => $cleanTitle,
                'caption' => $cleanTitle . ' — Phong cách thời trang trẻ trung, hiện đại tại Nobi Fashion. Chất liệu cao cấp, phom dáng chuẩn.',
                'video_url' => $url,
                'poster_url' => null, // Frontend tự tạo poster từ video frame đầu
                'thumbnail_url' => null,
                'duration' => rand(15, 45),
                'width' => 1080,
                'height' => 1920,
                'aspect_ratio' => '9:16',
                'file_size' => 5940450,
                'mime_type' => 'video/mp4',
                'playback_type' => 'direct_mp4',
                'video_provider' => 'cloudflare_r2',
                'audio_title' => 'Âm thanh thời trang gốc — Nobi Studio',
                'author_name' => 'Nobi Fashion',
                'author_handle' => '@nobifashion',
                'author_avatar' => asset('clients/assets/img/business/logo-nobi-fashion.png'),
                'author_verified' => true,
                'category' => $cat,
                'hashtags' => $tags,
                'view_count' => rand(1200, 45000),
                'like_count' => rand(80, 3200),
                'comment_count' => rand(5, 120),
                'share_count' => rand(10, 250),
                'save_count' => rand(15, 450),
                'seo_title' => $cleanTitle . ' | Nobi Fashion Reels',
                'seo_description' => 'Xem video ' . $cleanTitle . ' trên Nobi Fashion Reels. Khám phá xu hướng phối đồ và thời trang mới nhất.',
                'canonical_url' => url('/reels/' . $slug),
                'status' => 'published',
                'is_featured' => ($idx < 10) ? true : false,
                'order_priority' => (550 - $idx),
                'published_at' => $publishedAt,
            ]);

            // Liên kết 1 - 2 sản phẩm ngẫu nhiên nếu có sản phẩm trong DB
            if ($totalProducts > 0) {
                $randomProductIds = collect($productIds)->random(min(2, $totalProducts));
                $order = 0;
                foreach ($randomProductIds as $pId) {
                    ReelProduct::create([
                        'reel_id' => $reel->id,
                        'product_id' => $pId,
                        'order' => $order++,
                    ]);
                }
            }

            $imported++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("KẾT QUẢ IMPORT:");
        $this->line("- Đã import mới: {$imported} video");
        $this->line("- Đã bỏ qua (trùng lặp): {$skipped} video");

        return Command::SUCCESS;
    }
}

