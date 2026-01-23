<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Banner Positions
    |--------------------------------------------------------------------------
    |
    | Định nghĩa các vị trí banner có thể sử dụng trong hệ thống.
    | Key là giá trị lưu trong database, value là nhãn hiển thị.
    |
    */
    'positions' => [
        'home' => 'Trang chủ',
        'shop' => 'Trang shop',
        'home_banner' => 'Banner con',
    ],

    /*
    |--------------------------------------------------------------------------
    | Position Badge Colors
    |--------------------------------------------------------------------------
    |
    | Màu sắc cho badge hiển thị vị trí banner.
    | Format: 'background-color' => 'text-color'
    |
    */
    'position_badges' => [
        'home' => [
            'bg' => '#e0f2fe',
            'text' => '#0369a1',
        ],
        'shop' => [
            'bg' => '#fef9c3',
            'text' => '#a16207',
        ],
        'home_banner' => [
            'bg' => '#f3e8ff',
            'text' => '#7c3aed',
        ],
    ],
];

