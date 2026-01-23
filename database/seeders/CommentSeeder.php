<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use App\Models\Account;
use App\Models\Product; // Giả sử bạn có model Product

class CommentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('comments')->truncate();
        
        $faker = Faker::create('vi_VN');
        $accountIds = Account::pluck('id')->toArray();
        $productIds = Product::pluck('id')->toArray(); // Giả sử commentable_type là Product

        if (empty($accountIds) || empty($productIds)) {
            $this->command->info('Vui lòng seed dữ liệu cho Accounts và Products trước!');
            return;
        }

        // Tạo 100 dữ liệu mẫu
        for ($i = 0; $i < 100; $i++) {
            DB::table('comments')->insert([
                'account_id' => $faker->randomElement($accountIds),
                'content' => $faker->paragraph,
                'is_approved' => $faker->boolean(90),
                'commentable_id' => $faker->randomElement($productIds),
                'commentable_type' => 'App\\Models\\Product', // Cần thay đổi nếu commentable_type khác
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}