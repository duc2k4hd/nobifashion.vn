<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use App\Models\FlashSale;
use App\Models\Product;

class FlashSaleItemSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('flash_sale_items')->truncate();
        
        $faker = Faker::create();

        $flashSales = FlashSale::all();
        $productIds = Product::whereBetween('id', [1, 100])->pluck('id')->toArray();

        if ($flashSales->isEmpty() || empty($productIds)) {
            $this->command->info('⚠️ Vui lòng seed FlashSales và Products trước!');
            return;
        }

        foreach ($flashSales as $index => $flashSale) {
            // Flash sale đầu tiên sẽ có ít nhất 30 items
            $itemCount = ($index === 0) ? 30 : $faker->numberBetween(5, 15);

            $selectedProducts = $faker->randomElements($productIds, $itemCount);

            foreach ($selectedProducts as $productId) {
                $originalPrice = $faker->randomFloat(2, 50000, 2000000);
                $salePrice     = $originalPrice * $faker->randomFloat(2, 0.5, 0.9);
                $stock         = $faker->numberBetween(10, 200);
                $sold          = $faker->numberBetween(0, $stock);

                DB::table('flash_sale_items')->insert([
                    'flash_sale_id' => $flashSale->id,
                    'product_id'    => $productId,
                    'original_price'=> $originalPrice,
                    'sale_price'    => $salePrice,
                    'stock'         => $stock,
                    'sold'          => $sold,
                    'max_per_user'  => $faker->numberBetween(1, 5),
                    'is_active'     => $faker->boolean(90),
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            }
        }
    }
}
