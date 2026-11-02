<?php

namespace App\Services;

use App\Models\AnonymousUser;

class AnonymousNicknameGenerator
{
    /**
     * Danh sách loài động vật dễ thương & phong phú
     */
    protected static array $animals = [
        'Heo', 'Sóc', 'Gấu', 'Mèo', 'Cún', 'Cáo', 'Thỏ', 'Hổ', 'Báo', 'Sói',
        'Vịt', 'Ngỗng', 'Cá Heo', 'Cá Mập', 'Cá Nóc', 'Nhím', 'Chồn', 'Gấu Trúc',
        'Rái Cá', 'Cú Đêm', 'Chim Cánh Cụt', 'Bạch Tuộc', 'Hải Cẩu', 'Koala',
        'Lười', 'Chuột Túi', 'Hươu Cao Cổ', 'Ngựa Vằn', 'Sư Tử', 'Voi Con',
        'Khỉ Con', 'Gà Con', 'Chim Sẻ', 'Bồ Nông', 'Cừu Bông', 'Lạc Đà', 'Tê Giác',
        'Chinchilla', 'Capybara', 'Alpaca', 'Hồng Hạc', 'Bồ Câu', 'Cá Voi'
    ];

    /**
     * Danh sách tính từ đáng yêu, tinh nghịch, hài hước
     */
    protected static array $adjectives = [
        'Nâu', 'Trắng', 'Đen', 'Vàng', 'Hồng', 'Mập', 'Tròn', 'Lười', 'Ngáo',
        'Ngủ Gật', 'Tinh Nghịch', 'Dễ Thương', 'Mơ Mộng', 'Ham Ăn', 'Vui Vẻ',
        'Bí Ẩn', 'Lém Lỉnh', 'Nhút Nhát', 'Tò Mò', 'Đáng Yêu', 'Xinh Xắn',
        'Hạt Tiêu', 'Lon Ton', 'Lắc Lư', 'Hiền Lành', 'Tung Tăng', 'Tí Hon',
        'Béo Múp', 'Cáu Kỉnh', 'Năng Động', 'Ấm Áp', 'Tươi Rói', 'Chăm Chỉ',
        'Thích Ngủ', 'Yêu Đời', 'Ngơ Ngác', 'Múp Míp', 'Chubby', 'Cute'
    ];

    /**
     * Bảng màu pastel thời trang cho avatar
     */
    protected static array $pastelColors = [
        '#ef4444', '#f97316', '#f59e0b', '#10b981', '#06b6d4',
        '#3b82f6', '#6366f1', '#8b5cf6', '#ec4899', '#f43f5e',
        '#14b8a6', '#84cc16', '#0ea5e9', '#a855f7', '#d946ef'
    ];

    /**
     * Khóa icon avatar tương ứng với động vật
     */
    protected static array $avatarMap = [
        'Mèo' => 'cat', 'Cún' => 'dog', 'Gấu' => 'bear', 'Gấu Trúc' => 'panda',
        'Thỏ' => 'rabbit', 'Cáo' => 'fox', 'Hổ' => 'tiger', 'Sư Tử' => 'lion',
        'Heo' => 'pig', 'Khỉ Con' => 'monkey', 'Chim Cánh Cụt' => 'penguin',
        'Bạch Tuộc' => 'octopus', 'Cá Heo' => 'dolphin', 'Cá Mập' => 'shark',
        'Vịt' => 'duck', 'Sóc' => 'squirrel', 'Cú Đêm' => 'owl', 'Capybara' => 'capybara'
    ];

    /**
     * Tạo một biệt danh ngẫu nhiên đảm bảo chưa từng tồn tại trong Database
     */
    public static function generateUniqueNickname(): array
    {
        $maxAttempts = 15;
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            $attempt++;

            $animal = self::$animals[array_rand(self::$animals)];
            $adjective = self::$adjectives[array_rand(self::$adjectives)];
            
            // Hậu tố số 2-4 chữ số
            $number = str_pad((string) random_int(1, 999), 2, '0', STR_PAD_LEFT);
            $nickname = "{$animal} {$adjective} {$number}";

            // Kiểm tra trùng lặp
            if (!AnonymousUser::where('nickname', $nickname)->exists()) {
                $avatarKey = self::$avatarMap[$animal] ?? 'sparkles';
                $avatarColor = self::$pastelColors[array_rand(self::$pastelColors)];

                return [
                    'nickname' => $nickname,
                    'avatar_key' => $avatarKey,
                    'avatar_color' => $avatarColor,
                ];
            }
        }

        // Fallback mở rộng số 4 chữ số
        $animal = self::$animals[array_rand(self::$animals)];
        $adjective = self::$adjectives[array_rand(self::$adjectives)];
        $number = random_int(1000, 9999);
        $nickname = "{$animal} {$adjective} {$number}";
        $avatarKey = self::$avatarMap[$animal] ?? 'sparkles';
        $avatarColor = self::$pastelColors[array_rand(self::$pastelColors)];

        return [
            'nickname' => $nickname,
            'avatar_key' => $avatarKey,
            'avatar_color' => $avatarColor,
        ];
    }
}

