<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use App\Models\Account; // Giả sử bạn có model Account
use Illuminate\Support\Str;

class VoucherSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('vouchers')->truncate(); // Xóa dữ liệu cũ

        $faker = Faker::create('vi_VN');

        // Lấy danh sách ID tài khoản (nếu cần gán voucher cho user cụ thể)
        $accountIds = Account::pluck('id')->toArray();
        if (empty($accountIds)) {
            $this->command->info('Vui lòng seed dữ liệu cho bảng accounts trước!');
            return;
        }

        // Tạo 100 dữ liệu voucher mẫu
        for ($i = 0; $i < 100; $i++) {
            $type = $faker->randomElement(['percentage', 'fixed_amount', 'free_shipping']);
            $applicable_to = $faker->randomElement(['all_products', 'specific_products', 'specific_categories']);
            $start_at = $faker->dateTimeBetween('-1 month', 'now');
            $end_at = (clone $start_at)->modify('+' . $faker->numberBetween(1, 12) . ' months');
            $value = 0;
            $minOrderAmount = null;
            $maxDiscountAmount = null;

            switch ($type) {
                case 'percentage':
                    $value = $faker->numberBetween(5, 50);
                    $minOrderAmount = $faker->randomElement([500000, 1000000, 2000000]);
                    $maxDiscountAmount = $faker->randomElement([50000, 100000, 200000]);
                    break;
                case 'fixed_amount':
                    $value = $faker->randomElement([20000, 50000, 100000, 200000]);
                    $minOrderAmount = $faker->randomElement([200000, 500000, 1000000]);
                    break;
                case 'free_shipping':
                    $value = 0;
                    $minOrderAmount = $faker->randomElement([300000, 500000, 1000000]);
                    break;
            }

            DB::table('vouchers')->insert([
                'code' => 'VOUCHER' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'name' => 'Voucher ' . ($i + 1),
                'description' => $faker->sentence,
                'image' => 'promotion-1.png',
                'account_id' => $faker->boolean(20) ? $faker->randomElement($accountIds) : null,
                'type' => $type,
                'value' => $value,
                'usage_limit' => $faker->numberBetween(10, 500),
                'usage_count' => $faker->numberBetween(0, 50),
                'per_user_limit' => $faker->boolean(50) ? $faker->numberBetween(1, 3) : null,
                'min_order_amount' => $minOrderAmount,
                'max_discount_amount' => $maxDiscountAmount,
                'applicable_to' => $applicable_to,
                'applicable_ids' => json_encode($applicable_to !== 'all_products' ? $faker->randomElements(range(1, 20), 3) : null),
                'start_at' => $start_at,
                'end_at' => $end_at,
                'status' => $faker->randomElement(['active', 'expired', 'disabled', 'scheduled']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}