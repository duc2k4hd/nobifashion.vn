<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use App\Models\Account;
use App\Models\Product; 
use Illuminate\Support\Str;// Giả sử bạn có model Product

class AffiliateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('affiliates')->truncate();
        
        $faker = Faker::create('vi_VN');
        $accountIds = Account::pluck('id')->toArray();
        $productIds = Product::pluck('id')->toArray();

        if (empty($accountIds) || empty($productIds)) {
            $this->command->info('Vui lòng seed dữ liệu cho Accounts và Products trước!');
            return;
        }

        // Tạo 100 dữ liệu mẫu
        for ($i = 0; $i < 100; $i++) {
            DB::table('affiliates')->insert([
                'account_id' => $faker->randomElement($accountIds),
                'product_id' => $faker->randomElement($productIds),
                'ref_code' => Str::random(10),
                'clicks' => $faker->numberBetween(10, 500),
                'conversions' => $faker->numberBetween(1, 50),
                'commission_total' => $faker->randomFloat(2, 100000, 5000000),
                'address' => $faker->address,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}