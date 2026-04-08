<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Image;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ProductSeeder extends Seeder
{
    public function run()
    {
        // 1. Xóa dữ liệu cũ (Dùng DB::statement để tránh lỗi khóa ngoại nếu có)
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('product_variants')->truncate();
        DB::table('images')->where('product_id', '!=', null)->delete();
        DB::table('products')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 2. Lấy danh sách ảnh từ thư mục public/clients/assets/img/clothes
        $imagePath = public_path('clients/assets/img/clothes');
        $allImages = [];
        if (File::exists($imagePath)) {
            $files = File::files($imagePath);
            foreach ($files as $file) {
                if (in_array(strtolower($file->getExtension()), ['jpg', 'jpeg', 'png', 'webp'])) {
                    $allImages[] = $file->getFilename();
                }
            }
        }

        if (empty($allImages)) {
            $allImages = ['no-image.webp'];
        }

        // 3. Lấy một tài khoản hợp lệ để gán cho created_by
        $adminAccount = \App\Models\Account::where('account_status', \App\Models\Account::STATUS_ACTIVE)->first() ?? \App\Models\Account::first();
        if (!$adminAccount) {
            $this->command->info('Không tìm thấy tài khoản nào trong hệ thống.');
            return;
        }

        // 4. Lấy tất cả danh mục lá (không có con)
        $leafCategories = Category::whereDoesntHave('children')->get();

        if ($leafCategories->isEmpty()) {
            $this->command->info('Không tìm thấy danh mục lá nào. Vui lòng chạy CategorySeeder trước.');
            return;
        }

        $colors = ['Đen', 'Trắng', 'Xám', 'Xanh Navy', 'Be', 'Rêu', 'Đỏ Đô'];
        $sizes = ['S', 'M', 'L', 'XL', '2XL'];

        foreach ($leafCategories as $category) {
            $count = rand(5, 8); // Tạo 5-8 sản phẩm mỗi danh mục
            
            for ($i = 1; $i <= $count; $i++) {
                $productName = $category->name . ' ' . Str::random(3) . ' ' . str_pad($i, 2, '0', STR_PAD_LEFT);
                $skuBase = strtoupper(Str::slug($category->name, ''));
                $sku = $skuBase . '-' . strtoupper(Str::random(4)) . '-' . str_pad($i, 3, '0', STR_PAD_LEFT);
                $price = rand(250, 850) * 1000;
                $salePrice = rand(0, 10) > 6 ? $price * 0.85 : null; // 40% cơ hội giảm giá

                // Tạo Product
                $product = Product::create([
                    'sku' => $sku,
                    'name' => $productName,
                    'slug' => Str::slug($productName) . '-' . Str::random(5),
                    'short_description' => 'Mô tả ngắn cho sản phẩm ' . $productName . '. Chất liệu cao cấp, form dáng chuẩn đẹp, phù hợp mặc đi làm, đi chơi.',
                    'description' => '<h3>Thông tin chi tiết</h3><p>Sản phẩm ' . $productName . ' là một trong những thiết kế mới nhất nằm trong bộ sưu tập thời trang của Nobi Fashion. Với tiêu chí mang lại sự thoải mái và tự tin cho người mặc, chúng tôi sử dụng chất liệu vải tuyển chọn, đường may tỉ mỉ.</p><ul><li>Chất liệu: Cotton/Linen cao cấp</li><li>Đặc tính: Co giãn tốt, thấm hút mồ hôi, bền màu</li><li>Phong cách: Hiện đại, trẻ trung</li></ul>',
                    'price' => $price,
                    'sale_price' => $salePrice,
                    'cost_price' => $price * 0.6,
                    'stock_quantity' => rand(50, 200),
                    'primary_category_id' => $category->id,
                    'category_ids' => [$category->id],
                    'is_featured' => rand(0, 10) > 8,
                    'has_variants' => true,
                    'created_by' => $adminAccount->id,
                    'is_active' => true,
                    'meta_title' => $productName . ' - Nobi Fashion',
                    'meta_description' => 'Mua ngay ' . $productName . ' tại Nobi Fashion. Cam kết hàng chính hãng, giao hàng toàn quốc, đổi trả miễn phí.',
                    'meta_keywords' => [$category->name, 'thời trang cao cấp', 'nobi fashion'],
                ]);

                // Tạo Ảnh (3-5 ảnh mỗi SP)
                $numPhotos = rand(3, 5);
                $productPhotos = collect($allImages)->random(min($numPhotos, count($allImages)));
                
                foreach ($productPhotos as $idx => $photoName) {
                    Image::create([
                        'product_id' => $product->id,
                        'url' => $photoName, // Chỉ lưu tên file theo logic frontend
                        'is_primary' => $idx === 0,
                        'order' => $idx,
                        'context' => 'product'
                    ]);
                }

                // Tạo Biến thể (Variants)
                $selectedColors = collect($colors)->random(rand(1, 3));
                foreach ($selectedColors as $color) {
                    foreach ($sizes as $size) {
                        ProductVariant::create([
                            'product_id' => $product->id,
                            'sku' => $sku . '-' . strtoupper(Str::slug($color, '')) . '-' . $size,
                            'name' => $productName . ' (' . $color . ' / ' . $size . ')',
                            'price' => $price,
                            'sale_price' => $salePrice,
                            'stock_quantity' => rand(10, 50),
                            'attributes' => [
                                'color' => $color,
                                'size' => $size,
                            ],
                            'is_active' => true,
                        ]);
                    }
                }
            }
            
            $this->command->info("Đã tạo " . $count . " sản phẩm cho danh mục: " . $category->name);
        }
    }
}