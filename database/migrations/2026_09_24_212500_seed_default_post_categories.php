<?php

use App\Models\PostCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $categories = [
            'Thời trang' => 'thoi-trang',
            'Phong cách' => 'phong-cach',
            'Làm đẹp' => 'lam-dep',
            'Xu hướng' => 'xu-huong',
            'Chọn & Mua' => 'chon-mua',
            'Mẹo hay' => 'meo-hay',
            'Kiến thức' => 'kien-thuc',
            'Khám phá' => 'kham-pha',
            'Lifestyle' => 'lifestyle',
            'Sống khỏe' => 'song-khoe',
            'Thể thao' => 'the-thao',
            'Ẩm thực' => 'am-thuc',
            'Du lịch' => 'du-lich',
            'Giải trí' => 'giai-tri',
            'Văn hóa' => 'van-hoa',
            'Công nghệ' => 'cong-nghe',
            'Sống số' => 'song-so',
            'Gia đình' => 'gia-dinh',
            'Mẹ & Bé' => 'me-va-be',
            'Nhà cửa' => 'nha-cua',
            'Học tập & Sự nghiệp' => 'hoc-tap-su-nghiep',
            'Tài chính & Tiêu dùng' => 'tai-chinh-tieu-dung',
            'Xe & Di chuyển' => 'xe-di-chuyen',
            'Tình yêu & Mối quan hệ' => 'tinh-yeu-moi-quan-he',
            'Sống xanh' => 'song-xanh',
            'Xã hội' => 'xa-hoi',
            'Cộng đồng' => 'cong-dong',
            'Khoa học' => 'khoa-hoc',
        ];

        $sortOrder = 10;
        foreach ($categories as $name => $slug) {
            $cat = PostCategory::where('slug', $slug)->first();
            if (!$cat) {
                PostCategory::create([
                    'name' => $name,
                    'slug' => $slug,
                    'description' => "Tổng hợp các bài viết, tin tức và chia sẻ hữu ích về chủ đề {$name}.",
                    'meta_title' => "{$name} - Blog & Tin tức",
                    'meta_description' => "Khám phá các bài viết hay nhất về chủ đề {$name} cùng bí quyết và xu hướng mới.",
                    'is_active' => true,
                    'sort_order' => $sortOrder,
                ]);
            }
            $sortOrder += 5;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $slugs = [
            'thoi-trang', 'phong-cach', 'lam-dep', 'xu-huong', 'chon-mua',
            'meo-hay', 'kien-thuc', 'kham-pha', 'lifestyle', 'song-khoe',
            'the-thao', 'am-thuc', 'du-lich', 'giai-tri', 'van-hoa',
            'cong-nghe', 'song-so', 'gia-dinh', 'me-va-be', 'nha-cua',
            'hoc-tap-su-nghiep', 'tai-chinh-tieu-dung', 'xe-di-chuyen',
            'tinh-yeu-moi-quan-he', 'song-xanh', 'xa-hoi', 'cong-dong', 'khoa-hoc',
        ];

        PostCategory::whereIn('slug', $slugs)->delete();
    }
};
