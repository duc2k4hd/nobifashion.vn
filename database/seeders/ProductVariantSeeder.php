<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductVariantSeeder extends Seeder
{
    public function run(): void
    {
        $sizes = ['S', 'M', 'L', 'XL', 'XXL'];
        $colors = ['Black', 'White', 'Red', 'Blue', 'Green', 'Gray', 'Yellow', 'Navy', 'Beige', 'Brown'];

        $variants = [];

        // Loop qua 100 sản phẩm
        for ($productId = 1; $productId <= 100; $productId++) {
            // Mỗi sản phẩm có 10 variants
            for ($i = 0; $i < 10; $i++) {
                $size = $sizes[array_rand($sizes)];
                $color = $colors[array_rand($colors)];

                $variants[] = [
                    'product_id'     => $productId,
                    'price'          => rand(100000, 1000000), // random 100k–1tr
                    'stock_quantity' => rand(0, 100),
                    'attributes'     => json_encode([
                        'size'  => $size,
                        'color' => $color,
                    ]),
                    'image_id'       => rand(1, 50), // giả sử có 50 ảnh trong bảng image
                    'status'         => 'active',
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ];
            }
        }

        DB::table('product_variants')->insert($variants);
    }
}
