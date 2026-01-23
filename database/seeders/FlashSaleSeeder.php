<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class FlashSaleSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('flash_sales')->truncate();
        
        $faker = Faker::create('vi_VN');

        // Tạo 10 flash sale thay vì 100 (cho thực tế hơn)
        for ($i = 0; $i < 10; $i++) {
            $startTime = $faker->dateTimeBetween('-1 week', '+1 week');
            $endTime = (clone $startTime)->modify('+2 hours');

            DB::table('flash_sales')->insert([
                'title'       => 'Flash Sale ' . ($i + 1) . ': ' . $faker->words(3, true),
                'description' => $faker->paragraph,
                'banner'      => 'flash_sale_banner_' . ($i + 1) . '.png',
                'start_time'  => $startTime,
                'end_time'    => $endTime,
                'status'      => $faker->randomElement(['draft', 'active', 'expired']),
                'is_active'   => $faker->boolean(80),
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }
}
