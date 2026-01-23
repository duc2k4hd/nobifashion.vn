<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use App\Models\Account;

class AddressSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('addresses')->truncate();

        $faker = Faker::create('vi_VN');
        $accountIds = Account::pluck('id')->toArray();

        // Tạo 100 dữ liệu mẫu
        foreach ($accountIds as $accountId) {
            DB::table('addresses')->insert([
                'account_id' => $accountId,
                'full_name' => $faker->name,
                'phone_number' => $faker->phoneNumber,
                'detail_address' => $faker->streetAddress,
                'ward' => $faker->streetName,
                'district' => $faker->city,
                'province' => $faker->city,
                'postal_code' => $faker->postcode,
                'country' => 'Vietnam',
                'latitude' => $faker->latitude,
                'longitude' => $faker->longitude,
                'address_type' => $faker->randomElement(['home', 'work']),
                'notes' => $faker->sentence,
                'is_default' => $faker->boolean,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}