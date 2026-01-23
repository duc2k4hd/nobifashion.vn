<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use App\Models\Account;
use Illuminate\Support\Str;

class CartSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('carts')->truncate();
        
        $faker = Faker::create();
        $accountIds = Account::pluck('id')->toArray();

        if (empty($accountIds)) {
            $this->command->info('Vui lòng seed dữ liệu cho Accounts trước!');
            return;
        }

        // Tạo 100 dữ liệu mẫu
        for ($i = 0; $i < 100; $i++) {
            $quantity = $faker->numberBetween(1, 10);
            $price = $faker->numberBetween(100000, 1000000);
            DB::table('carts')->insert([
                'code' => 'CART' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'account_id' => $faker->randomElement($accountIds),
                'session_id' => Str::random(40),
                'total_price' => $quantity * $price,
                'total_quantity' => $quantity,
                'status' => $faker->randomElement(['active', 'ordered', 'abandoned']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}