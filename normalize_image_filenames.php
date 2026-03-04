<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

echo "Bắt đầu chuẩn hóa tên tệp ảnh trong DB...\n";

function normalizeFilenames($table, $columns) {
    echo "Đang xử lý bảng: $table\n";
    $count = 0;
    
    DB::table($table)->orderBy('id')->chunk(500, function($rows) use ($table, $columns, &$count) {
        foreach ($rows as $row) {
            $updates = [];
            foreach ($columns as $column) {
                if (!empty($row->$column)) {
                    $filename = basename($row->$column);
                    if ($filename !== $row->$column) {
                        $updates[$column] = $filename;
                    }
                }
            }
            
            if (!empty($updates)) {
                DB::table($table)->where('id', $row->id)->update($updates);
                $count++;
            }
        }
    });
    
    echo "Đã cập nhật $count bản ghi trong bảng $table.\n";
}

// Danh sách các bảng và cột cần chuẩn hóa
$targets = [
    'images' => ['url', 'path', 'thumbnail_url', 'medium_url'],
    'posts' => ['thumbnail'],
    'categories' => ['image'],
    'flash_sales' => ['banner'],
    'vouchers' => ['image'],
    'banners' => ['image_desktop', 'image_mobile'],
    'profiles' => ['avatar', 'sub_avatar'],
];

foreach ($targets as $table => $columns) {
    if (Schema::hasTable($table)) {
        normalizeFilenames($table, $columns);
    } else {
        echo "Bảng $table không tồn tại, bỏ qua.\n";
    }
}

echo "Hoàn thành chuẩn hóa dữ liệu!\n";
