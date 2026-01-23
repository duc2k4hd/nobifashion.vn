<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('accounts')->truncate(); // Xóa dữ liệu cũ trước khi thêm mới

        // Tạo 100 dữ liệu mẫu
        for ($i = 0; $i < 100; $i++) {
            DB::table('accounts')->insert([
                'name' => 'Test User ' . ($i + 1),
                'email' => 'user' . $i + 1 . '@example.com',
                'password' => Hash::make('password'),
                'role' => 'user',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}