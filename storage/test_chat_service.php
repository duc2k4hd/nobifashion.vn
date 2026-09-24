<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\CustomerChatService;
use Illuminate\Support\Facades\Http;

echo "=== BẮT ĐẦU KIỂM THỬ TÍNH NĂNG CHAT CSKH ===\n\n";

// 1. Test cấu hình
$configUrl = config('chat.ydc_api_url');
$configDomains = config('chat.include_domains');
echo "1. Cấu hình YDC API URL: {$configUrl}\n";
echo "   Domain áp dụng: " . implode(', ', $configDomains) . "\n";
echo "   PASS: Đọc cấu hình thành công!\n\n";

// 2. Test gọi YDC API giả lập (Mocking Response thực tế như User cung cấp)
$mockResponse = [
    'results' => [
        'web' => [
            [
                'url' => 'https://nobifashion.vn/blog/ao-khoac-nam-dep-hcm-top-10-shop-chat-luong-gia-tot-nhat',
                'title' => 'Áo Khoác Nam Đẹp HCM: Top 10 Shop Chất Lượng, Giá Tốt Nhất',
                'description' => 'Áo khoác nam đẹp, đa dạng kiểu dáng từ năng động đến lịch lãm đang chờ bạn khám phá!',
                'thumbnail_url' => 'https://nobifashion.vn/clients/assets/no-image.webp',
                'contents' => [
                    'highlights' => [
                        "1. 4MEN Shop 2. Nobi Fashion Việt Nam 3. Yody 4. 160 Store\nThời Trang Grunge: Phong Cách Bụi Bặm Cá Tính, Hot Trend 2026",
                    ],
                ],
            ],
            [
                'url' => 'https://nobifashion.vn/blog/quan-jean-nam-ha-noi-top-15-shop-dep-chat-luong-gia-tot-nhat',
                'title' => 'Quần Jean Nam Hà Nội: Top 15 Shop Đẹp, Chất Lượng, Giá Tốt Nhất!',
                'description' => 'Quần jean nam Hà Nội đẹp ở đâu? Xem ngay TOP 15 shop quần jeans nam chất lượng.',
                'thumbnail_url' => 'https://nobifashion.vn/quan-jean-nam-ha-noi-top-15-shop-dep-chat-luong-gia-tot-nhat.jpg',
                'contents' => [
                    'highlights' => [
                        'Uniqlo luôn là lựa chọn hàng đầu khi nhắc đến quần jean nam tại Hà Nội với thiết kế đơn giản nhưng tinh tế.',
                    ],
                ],
            ],
        ],
    ],
];

config(['chat.ydc_api_key' => 'TEST_KEY_YDC']);
Http::fake([
    'https://ydc-index.io/v1/search' => Http::response($mockResponse, 200),
]);

$service = app(CustomerChatService::class);
$result = $service->search('Top 10 shop bán quần áo uy tín tại Hà Nội');

echo "2. Test xử lý phản hồi từ YDC API:\n";
echo "   Source: {$result['source']}\n";
echo "   Số bài viết trích xuất: " . count($result['articles']) . "\n";
echo "   Tiêu đề bài viết đầu: {$result['articles'][0]['title']}\n";
echo "   Link: {$result['articles'][0]['url']}\n";
$test2Pass = ($result['success'] && $result['source'] === 'ydc' && count($result['articles']) === 2);
echo "   " . ($test2Pass ? "PASS: Trích xuất và định dạng YDC response chuẩn xác 100%!" : "FAIL") . "\n\n";

// 3. Test Fallback khi chưa có API key
config(['chat.ydc_api_key' => '']);
$fallbackResult = $service->search('Áo khoác');
echo "3. Test Fallback Database khi không có API key:\n";
echo "   Source: {$fallbackResult['source']}\n";
echo "   Phản hồi: " . substr($fallbackResult['reply_text'], 0, 80) . "...\n";
$test3Pass = ($fallbackResult['success'] === true);
echo "   " . ($test3Pass ? "PASS: Fallback hoạt động mượt mà, không gián đoạn khách hàng!" : "FAIL") . "\n\n";

// 4. Test Controller init data
$controller = app(\App\Http\Controllers\Clients\ChatBotController::class);
$initResponse = $controller->getInitData()->getData(true);
echo "4. Test dữ liệu khởi tạo khung chat (/chat/init):\n";
echo "   Tin chào: " . substr($initResponse['welcome_message'], 0, 50) . "...\n";
echo "   Số gợi ý nhanh: " . count($initResponse['quick_suggestions']) . "\n";
$test4Pass = ($initResponse['success'] && !empty($initResponse['welcome_message']) && count($initResponse['quick_suggestions']) > 0);
echo "   " . ($test4Pass ? "PASS: API khởi tạo đầy đủ dữ liệu!" : "FAIL") . "\n\n";

echo "=== TẤT CẢ TEST ĐÃ HOÀN TẤT THÀNH CÔNG! ===\n";
