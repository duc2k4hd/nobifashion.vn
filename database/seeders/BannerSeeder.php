<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use Illuminate\Support\Str;

class BannerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('banners')->truncate();
        
        $faker = Faker::create();

        // Tạo 100 dữ liệu mẫu
        for ($i = 0; $i < 100; $i++) {
            $title = 'Banner ' . $faker->unique()->sentence(3);
            DB::table('banners')->insert([
                'title' => $title,
                'image_desktop' => 'banner-1300x500-2.png',
                'image_mobile' => 'banner-1300x500-2.png',
                'link' => $faker->url,
                'description' => $faker->sentence(10),
                'position' => $faker->randomElement(['home', 'shop']),
                'taget' => '_blank',
                'start_at' => $faker->dateTimeBetween('-1 month', 'now'),
                'end_at' => $faker->dateTimeBetween('now', '+1 year'),
                'is_active' => $faker->boolean,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}