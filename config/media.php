<?php

return [
    'directories' => [
        'banners' => 'clients/assets/img/banners',
        'brands' => 'clients/assets/img/brands',
        'business' => 'clients/assets/img/business',
        'categories' => 'clients/assets/img/categories',
        'clothes' => 'clients/assets/img/clothes',
        'frame' => 'clients/assets/img/frame',
        'icon' => 'clients/assets/img/icon',
        'imports' => 'clients/assets/img/imports',
        'other' => 'clients/assets/img/other',
        'posts' => 'clients/assets/img/posts',
        'users' => 'clients/assets/img/users',
        'vouchers' => 'clients/assets/img/vouchers',
        
        'accounts_avatars' => 'admins/img/accounts',
        'accounts_banners' => 'admins/img/banners',
        'general_banners' => 'admins/img/general',
        'icons_banners' => 'admins/img/icons',
    ],
    'cleanup_directories' => [
        'banners',
        'brands',
        'categories',
        'clothes',
        'posts',
    ],
    'request_limits' => [
        'upload_file_max_kb' => 5120,
        'upload_batch_safety_ratio' => 0.9,
        'delete_items_per_request' => 200,
    ],
];


