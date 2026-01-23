<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use App\Models\Cart;
use App\Models\Product; // Giả sử bạn có model Product
use App\Models\ProductVariant; // Giả sử bạn có model ProductVariant

class CartItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('cart_items')->truncate();
        
        $faker = Faker::create();
        $cartIds = Cart::pluck('id')->toArray();
        $productIds = Product::pluck('id')->toArray();
        $productVariantIds = ProductVariant::pluck('id')->toArray();

        if (empty($cartIds) || empty($productIds)) {
            $this->command->info('Vui lòng seed dữ liệu cho Carts, Products và ProductVariants trước!');
            return;
        }

        // Tạo 100 dữ liệu mẫu
        for ($i = 0; $i < 100; $i++) {
            $quantity = $faker->numberBetween(1, 5);
            $price = $faker->numberBetween(50000, 500000);
            DB::table('cart_items')->insert([
                'cart_id' => $faker->randomElement($cartIds),
                'product_id' => $faker->randomElement($productIds),
                'product_variant_id' => $faker->randomElement($productVariantIds) ?? null,
                'quantity' => $quantity,
                'price' => $price,
                'total_price' => $quantity * $price,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}