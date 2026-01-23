<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('products')->truncate(); // Xóa dữ liệu cũ

        $faker = Faker::create('vi_VN');
        
        // Giả sử có 10 user và 30 danh mục
        $accountIds = range(1, 10); 
        $categoryIds = range(1, 30); 

        // Tạo 100 sản phẩm mẫu
        for ($i = 0; $i < 100; $i++) {
            $name = $faker->unique()->words(3, true) . ' ' . $faker->colorName;
            $price = $faker->randomFloat(2, 50000, 1000000);
            $salePrice = $faker->boolean(70) ? $price * $faker->randomFloat(2, 0.5, 0.9) : null;

            // Lấy ngẫu nhiên một danh mục chính và một vài danh mục phụ
            $primaryCategoryId = $faker->randomElement($categoryIds);
            $additionalCategoryIds = $faker->randomElements($categoryIds, $faker->numberBetween(0, 3));
            $allCategoryIds = array_unique(array_merge([$primaryCategoryId], $additionalCategoryIds));

            DB::table('products')->insert([
                'sku' => 'SKU' . str_pad($i + 1, 5, '0', STR_PAD_LEFT),
                'name' => 'Áo Thun ' . $name,
                'slug' => Str::slug('ao-thun-' . $name),
                'description' => $faker->paragraphs(3, true),
                'short_description' => $faker->sentence(10),
                'price' => $price,
                'sale_price' => $salePrice,
                'cost_price' => $price * $faker->randomFloat(2, 0.4, 0.7),
                'stock_quantity' => $faker->numberBetween(0, 200),
                'meta_title' => 'Mua ' . $name . ' chính hãng tại Nobifashion',
                'meta_description' => 'Sản phẩm ' . $name . ' với chất lượng tốt nhất và giá cả phải chăng.',
                'meta_keywords' => implode(',', $faker->words(5)),
                'meta_canonical' => 'https://yourstore.com/products/' . Str::slug('ao-thun-' . $name),
                'primary_category_id' => $primaryCategoryId,
                'category_ids' => json_encode($allCategoryIds),
                'tag_ids' => json_encode($faker->randomElements(range(1, 20), $faker->numberBetween(1, 5))),
                'is_featured' => $faker->boolean(20),
                'has_variants' => $faker->boolean(50),
                'locked_by' => null,
                'locked_at' => null,
                'created_by' => $faker->randomElement($accountIds),
                'is_active' => $faker->boolean(95),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}