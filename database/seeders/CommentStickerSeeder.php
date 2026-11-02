<?php

namespace Database\Seeders;

use App\Models\CommentSticker;
use Illuminate\Database\Seeder;

class CommentStickerSeeder extends Seeder
{
    public function run(): void
    {
        $stickers = [
            // Category: cute
            [
                'code' => 'cat_heart',
                'name' => 'Mèo Thả Tim',
                'category' => 'cute',
                'asset_url' => '/clients/assets/img/stickers/cat-heart.svg',
                'sort_order' => 1,
            ],
            [
                'code' => 'panda_wave',
                'name' => 'Gấu Trúc Vẫy Tay',
                'category' => 'cute',
                'asset_url' => '/clients/assets/img/stickers/panda-wave.svg',
                'sort_order' => 2,
            ],
            [
                'code' => 'rabbit_dance',
                'name' => 'Thỏ Nhảy Múa',
                'category' => 'cute',
                'asset_url' => '/clients/assets/img/stickers/rabbit-dance.svg',
                'sort_order' => 3,
            ],
            [
                'code' => 'fox_wink',
                'name' => 'Cáo Nháy Mắt',
                'category' => 'cute',
                'asset_url' => '/clients/assets/img/stickers/fox-wink.svg',
                'sort_order' => 4,
            ],
            // Category: reaction
            [
                'code' => 'fire_hot',
                'name' => 'Quá Cháy',
                'category' => 'reaction',
                'asset_url' => '/clients/assets/img/stickers/fire-hot.svg',
                'sort_order' => 10,
            ],
            [
                'code' => 'heart_sparkle',
                'name' => 'Tim Lấp Lánh',
                'category' => 'reaction',
                'asset_url' => '/clients/assets/img/stickers/heart-sparkle.svg',
                'sort_order' => 11,
            ],
            [
                'code' => 'laugh_cry',
                'name' => 'Cười Ra Nước Mắt',
                'category' => 'reaction',
                'asset_url' => '/clients/assets/img/stickers/laugh-cry.svg',
                'sort_order' => 12,
            ],
            [
                'code' => 'mind_blown',
                'name' => 'Đỉnh Chóp',
                'category' => 'reaction',
                'asset_url' => '/clients/assets/img/stickers/mind-blown.svg',
                'sort_order' => 13,
            ],
            // Category: fashion
            [
                'code' => 'ootd_style',
                'name' => 'Outfit Đẹp Quá',
                'category' => 'fashion',
                'asset_url' => '/clients/assets/img/stickers/ootd-style.svg',
                'sort_order' => 20,
            ],
            [
                'code' => 'shopping_bag',
                'name' => 'Chốt Đơn Ngay',
                'category' => 'fashion',
                'asset_url' => '/clients/assets/img/stickers/shopping-bag.svg',
                'sort_order' => 21,
            ],
            [
                'code' => 'fashion_100',
                'name' => '100 Điểm',
                'category' => 'fashion',
                'asset_url' => '/clients/assets/img/stickers/fashion-100.svg',
                'sort_order' => 22,
            ],
            [
                'code' => 'sparkle_dress',
                'name' => 'Form Dáng Chuẩn',
                'category' => 'fashion',
                'asset_url' => '/clients/assets/img/stickers/sparkle-dress.svg',
                'sort_order' => 23,
            ],
        ];

        foreach ($stickers as $item) {
            CommentSticker::updateOrCreate(
                ['code' => $item['code']],
                $item
            );
        }
    }
}

