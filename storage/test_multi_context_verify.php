<?php

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\CustomerChatService;

$service = app(CustomerChatService::class);

echo "=======================================================\n";
echo "TEST 1: KHÁCH HÀNG A (Đang quan tâm ÁO SƠ MI)\n";
echo "=======================================================\n";

$contextA = [
    ['role' => 'user', 'text' => 'Shop ơi mình cần xem mẫu áo sơ mi công sở nam'],
    ['role' => 'bot', 'text' => 'Dạ Nobi Fashion có nhiều mẫu áo sơ mi công sở chất lượng cao cấp ạ!'],
];

// Khách A hỏi câu rất ngắn tiếp theo: "giá bao nhiêu một cái"
$msgA = "giá bao nhiêu một cái";
$resA = $service->search($msgA, $contextA);
echo "Khách A hỏi: \"$msgA\"\n";
echo "Bot phản hồi:\n" . $resA['reply_text'] . "\n\n";

echo "=======================================================\n";
echo "TEST 2: KHÁCH HÀNG B (Đang quan tâm QUẦN JEAN & LO LẮNG SIZE)\n";
echo "=======================================================\n";

$contextB = [
    ['role' => 'user', 'text' => 'Mình đang xem mấy mẫu quần jean nam'],
    ['role' => 'bot', 'text' => 'Dạ quần jean nam tại Nobi Fashion chất denim co giãn cực tốt và tôn dáng ạ!'],
];

// Khách B hỏi câu lo lắng về số đo: "mình cao 1m72 nặng 68kg thì mặc vừa không"
$msgB = "mình cao 1m72 nặng 68kg thì mặc vừa không";
$resB = $service->search($msgB, $contextB);
echo "Khách B hỏi: \"$msgB\"\n";
echo "Bot phản hồi:\n" . $resB['reply_text'] . "\n\n";

echo "=======================================================\n";
echo "TEST 3: KHÁCH HÀNG C (E NGẠI MUA HÀNG ONLINE & CHẤT VẢI)\n";
echo "=======================================================\n";

$contextC = [
    ['role' => 'user', 'text' => 'Áo polo bên shop vải gì vậy, có xù lông hay nhăn không sợ mua về không giống ảnh'],
];
$msgC = "áo polo chất vải gì vậy có sợ xù lông không";
$resC = $service->search($msgC, $contextC);
echo "Khách C hỏi: \"$msgC\"\n";
echo "Bot phản hồi:\n" . $resC['reply_text'] . "\n\n";

echo "=======================================================\n";
echo "KIỂM TRA HOÀN TẤT THÀNH CÔNG!\n";
echo "=======================================================\n";
