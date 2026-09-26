<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('post_categories')) {
            return;
        }

        $officialCategories = [
            'thoi-trang' => 'Thời trang',
            'phong-cach' => 'Phong cách',
            'lam-dep' => 'Làm đẹp',
            'xu-huong' => 'Xu hướng',
            'chon-mua' => 'Chọn & Mua',
            'meo-hay' => 'Mẹo hay',
            'kien-thuc' => 'Kiến thức',
            'kham-pha' => 'Khám phá',
            'lifestyle' => 'Lifestyle',
            'song-khoe' => 'Sống khỏe',
            'the-thao' => 'Thể thao',
            'am-thuc' => 'Ẩm thực',
            'du-lich' => 'Du lịch',
            'giai-tri' => 'Giải trí',
            'van-hoa' => 'Văn hóa',
            'cong-nghe' => 'Công nghệ',
            'song-so' => 'Sống số',
            'gia-dinh' => 'Gia đình',
            'me-va-be' => 'Mẹ & Bé',
            'nha-cua' => 'Nhà cửa',
            'hoc-tap-su-nghiep' => 'Học tập & Sự nghiệp',
            'tai-chinh-tieu-dung' => 'Tài chính & Tiêu dùng',
            'xe-di-chuyen' => 'Xe & Di chuyển',
            'tinh-yeu-moi-quan-he' => 'Tình yêu & Mối quan hệ',
            'song-xanh' => 'Sống xanh',
            'xa-hoi' => 'Xã hội',
            'cong-dong' => 'Cộng đồng',
            'khoa-hoc' => 'Khoa học',
        ];

        // 1. Đảm bảo toàn bộ 28 danh mục bài viết chuẩn tồn tại
        $sortOrder = 10;
        foreach ($officialCategories as $slug => $name) {
            $cat = DB::table('post_categories')->where('slug', $slug)->first();
            if (!$cat) {
                DB::table('post_categories')->insert([
                    'name' => $name,
                    'slug' => $slug,
                    'description' => "Tổng hợp các bài viết, tin tức và chia sẻ hữu ích về chủ đề {$name}.",
                    'meta_title' => "{$name} - Blog & Tin tức",
                    'meta_description' => "Khám phá các bài viết hay nhất về chủ đề {$name} cùng bí quyết và xu hướng mới.",
                    'is_active' => true,
                    'sort_order' => $sortOrder,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            $sortOrder += 5;
        }

        // Lấy ID danh mục Thời trang và Thể thao chuẩn
        $thoiTrangCat = DB::table('post_categories')->where('slug', 'thoi-trang')->first();
        $theThaoCat = DB::table('post_categories')->where('slug', 'the-thao')->first();
        $defaultCatId = $thoiTrangCat ? $thoiTrangCat->id : 1;
        $theThaoCatId = $theThaoCat ? $theThaoCat->id : $defaultCatId;

        // 2. Tìm tất cả danh mục bài viết KHÔNG nằm trong danh sách 28 danh mục (đây là danh mục sản phẩm bị gán nhầm)
        $invalidCats = DB::table('post_categories')
            ->whereNotIn('slug', array_keys($officialCategories))
            ->get();

        if ($invalidCats->isNotEmpty()) {
            foreach ($invalidCats as $badCat) {
                $targetId = (str_contains($badCat->slug, 'the-thao')) ? $theThaoCatId : $defaultCatId;

                // Cập nhật tất cả bài viết đang trỏ tới danh mục sản phẩm nhầm này sang danh mục bài viết chuẩn
                if (Schema::hasTable('posts')) {
                    DB::table('posts')
                        ->where('category_id', $badCat->id)
                        ->update(['category_id' => $targetId]);
                }

                // Xóa danh mục sản phẩm nhầm khỏi bảng post_categories
                DB::table('post_categories')->where('id', $badCat->id)->delete();
            }
        }

        // Đảm bảo không còn bài viết nào có category_id bị null hoặc không tồn tại trong post_categories
        if (Schema::hasTable('posts')) {
            $validIds = DB::table('post_categories')->pluck('id')->toArray();
            if (!empty($validIds)) {
                DB::table('posts')
                    ->where(function ($q) use ($validIds) {
                        $q->whereNull('category_id')
                          ->orWhereNotIn('category_id', $validIds);
                    })
                    ->update(['category_id' => $defaultCatId]);
            }
        }

        // Xóa cache danh mục sidebar để hiển thị mới ngay lập tức
        Cache::forget('blog:sidebar:categories');
        Cache::forget('blog:featured');
        Cache::forget('blog:recent');
        Cache::forget('blog:popular');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
