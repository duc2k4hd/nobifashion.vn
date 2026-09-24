<?php

return [
    /*
    |--------------------------------------------------------------------------
    | You.com Answer & Search API Configuration
    |--------------------------------------------------------------------------
    |
    | Cấu hình tích hợp You.com Answer API (tổng hợp câu trả lời thông minh)
    | và YDC Index API (tìm kiếm trích xuất nội dung nguồn).
    |
    */

    'ydc_api_key' => env('YDC_API_KEY', ''),

    'answer_api_url' => env('YOU_ANSWER_API_URL', 'https://api.you.com/v1/answer'),

    'ydc_api_url' => env('YDC_API_URL', 'https://ydc-index.io/v1/search'),

    /*
    |--------------------------------------------------------------------------
    | Local / External AI LLM API (OpenAI Compatible)
    |--------------------------------------------------------------------------
    */
    'ai_enabled' => env('AI_CHAT_ENABLED', true),
    'ai_url' => env('AI_CHAT_URL', 'http://localhost:20128/v1/chat/completions'),
    'ai_key' => env('AI_CHAT_KEY', 'sk-44fb3720fce42fd6-0i6h03-3409eea0'),
    'ai_model' => env('AI_CHAT_MODEL', 'cx/gpt-5.6-luna'),
    'ai_timeout' => (int) env('AI_CHAT_TIMEOUT', 15),

    'include_domains' => array_values(array_filter(array_map('trim', explode(',', env('YDC_INCLUDE_DOMAINS', 'nobifashion.vn,www.coolmate.me,routine.vn,www.uniqlo.com'))))),

    'language' => env('YDC_LANGUAGE', 'VI'),

    'extraction_mode' => env('YDC_EXTRACTION_MODE', 'highlights'),

    'timeout' => (int) env('YDC_TIMEOUT', 12),

    /*
    |--------------------------------------------------------------------------
    | Cấu hình kiểm soát & an toàn Prompt (Prompt Security Guardrails)
    |--------------------------------------------------------------------------
    */
    'max_query_length' => 300,

    /*
    |--------------------------------------------------------------------------
    | Cấu hình tin nhắn chào mừng & câu hỏi gợi ý nhanh
    |--------------------------------------------------------------------------
    */
    'welcome_message' => 'Xin chào! 👋 Em là trợ lý thời trang của Nobi Fashion. Em có thể giúp bạn tìm kiếm thông tin bài viết, gợi ý phối đồ, tư vấn kích thước hoặc các sản phẩm hot nhất hôm nay!',

    'quick_suggestions' => [
        'Top shop bán quần áo uy tín tại Hà Nội',
        'Gợi ý phối đồ nam phong cách hot trend ' . date('Y'),
        'Tư vấn cách chọn size quần áo vừa vặn chuẩn',
        'Chính sách đổi trả & phí vận chuyển của shop',
    ],
];

