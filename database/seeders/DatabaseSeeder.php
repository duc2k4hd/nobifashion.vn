<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AccountSeeder::class,
            BannerSeeder::class,
            FlashSaleSeeder::class, // Chạy seeder flash_sales trước
            ProductSeeder::class,   // Chạy seeder products trước
            
            // Các seeder phụ thuộc vào flash_sales và products
            FlashSaleItemSeeder::class, 
            // AffiliateSeeder::class,
            CartSeeder::class,
            AddressSeeder::class,
            CommentSeeder::class,
            // Thêm các Seeder khác vào đây
            VoucherSeeder::class,
        ]);
    }
}