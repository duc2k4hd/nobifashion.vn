<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'ghn' => [
        'token' => env('APP_API_GHN', 'f3972491-a014-11f0-ad74-6a7dfba5d966'),
        'base_url' => env('APP_URL_GHN', 'https://online-gateway.ghn.vn/shiip/public-api/v2/'),
        'shop_id' => env('GHN_SHOP_ID', 2510601),
        'from_district_id' => env('GHN_FROM_DISTRICT_ID', 1588),
        'from_ward_code' => env('GHN_FROM_WARD_CODE', 30212),
        'service_id' => env('GHN_SERVICE_ID', 53320),
        'service_type_id' => env('GHN_SERVICE_TYPE_ID', 2),
        'from_name' => env('GHN_FROM_NAME', 'Nobi Fashion'),
        'from_phone' => env('GHN_FROM_PHONE', '0827786198'),
        'from_address' => env('GHN_FROM_ADDRESS', '39 NTT'),
        'return_phone' => env('GHN_RETURN_PHONE', '0827786198'),
        'return_address' => env('GHN_RETURN_ADDRESS', '39 NTT'),
        'return_district_id' => env('GHN_RETURN_DISTRICT_ID'),
        'return_ward_code' => env('GHN_RETURN_WARD_CODE', ''),
        // Các cấu hình nâng cao cho tính phí GHN, nếu không dùng có thể để 0
        'config_fee_id' => env('GHN_CONFIG_FEE_ID', 0),
        'extra_cost_id' => env('GHN_EXTRA_COST_ID', 0),
    ],

    'here' => [
        'api_key' => env('GEOCODE_API_KEY'),
    ],

    'tinymce' => [
        'key' => env('APP_KEY_TINYMCE')
    ],

    'google' => [
        'places_api_key' => env('GOOGLE_PLACES_API_KEY'),
    ],

    'pay_os' => [
        'client_id' => env('PAYOS_CLIENT_ID'),
        'api_key' => env('PAYOS_API_KEY'),
        'checksum_key' => env('PAYOS_CHECKSUM_KEY'),
        'base_url' => env('PAYOS_BASE_URL', 'https://api-merchant.payos.vn'),
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-2.0-flash'),
    ],
];
