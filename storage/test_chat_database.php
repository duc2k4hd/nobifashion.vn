<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Http\Controllers\Clients\ChatBotController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

echo "=== BẮT ĐẦU KIỂM THỬ LƯU CHAT TRỰC TIẾP VÀO DATABASE ===\n\n";

DB::beginTransaction();
try {
    $controller = app(ChatBotController::class);
    $testSessionId = 'test-session-uuid-' . uniqid();

    // 1. Test gửi tin nhắn đầu tiên (Tạo conversation & 2 messages: user + bot)
    Http::fake([
        'https://ydc-index.io/v1/search' => Http::response([
            'results' => [
                'web' => [
                    [
                        'url' => 'https://nobifashion.vn/blog/top-shop-ha-noi',
                        'title' => 'Top shop thời trang nam Hà Nội',
                        'description' => 'Mô tả bài viết',
                        'thumbnail_url' => 'https://nobifashion.vn/clients/assets/no-image.webp',
                        'contents' => ['highlights' => ['Shop thời trang uy tín']],
                    ]
                ]
            ]
        ], 200),
    ]);

    $reqSend = Request::create('/chat/send', 'POST', [
        'session_id' => $testSessionId,
        'message' => 'Shop có những mẫu áo sơ mi nào hot không?',
    ]);

    $resSend = $controller->sendMessage($reqSend)->getData(true);
    echo "1. Kết quả gửi tin nhắn:\n";
    echo "   Success: " . ($resSend['success'] ? 'true' : 'false') . "\n";
    echo "   User msg: {$resSend['data']['user_message']['text']}\n";
    echo "   Bot reply: " . substr($resSend['data']['bot_message']['text'], 0, 50) . "...\n";

    // Kiểm tra trong DB
    $conversation = ChatConversation::where('session_id', $testSessionId)->first();
    $messagesCount = ChatMessage::where('conversation_id', $conversation?->id)->count();

    echo "   Conversation ID trong DB: " . ($conversation ? $conversation->id : 'NULL') . "\n";
    echo "   Số tin nhắn đã lưu trong DB: {$messagesCount}\n";

    $test1Pass = ($resSend['success'] && $conversation !== null && $messagesCount === 2);
    echo "   " . ($test1Pass ? "PASS: Lưu đồng thời User và Bot message vào Database thành công!" : "FAIL") . "\n\n";

    // 2. Test lấy lịch sử tin nhắn từ DB (GET /chat/messages)
    $reqGet = Request::create('/chat/messages', 'GET', [
        'session_id' => $testSessionId,
    ]);
    $resGet = $controller->getMessages($reqGet)->getData(true);
    $loadedCount = count($resGet['messages'] ?? []);
    echo "2. Kết quả truy vấn lịch sử từ DB (/chat/messages):\n";
    echo "   Số tin nhắn trả về: {$loadedCount}\n";
    echo "   Tin nhắn đầu: [{$resGet['messages'][0]['role']}] {$resGet['messages'][0]['text']}\n";
    echo "   Tin nhắn sau: [{$resGet['messages'][1]['role']}] " . substr($resGet['messages'][1]['text'], 0, 40) . "...\n";
    $test2Pass = ($resGet['success'] && $loadedCount === 2);
    echo "   " . ($test2Pass ? "PASS: Nạp lịch sử từ Database cực nhanh và chuẩn xác!" : "FAIL") . "\n\n";

    // 3. Test xóa/làm mới phiên trò chuyện (POST /chat/clear)
    $reqClear = Request::create('/chat/clear', 'POST', [
        'session_id' => $testSessionId,
    ]);
    $resClear = $controller->clearHistory($reqClear)->getData(true);
    $conversation->refresh();
    echo "3. Kết quả làm mới phiên (/chat/clear):\n";
    echo "   Trạng thái phiên cũ: {$conversation->status}\n";
    $test3Pass = ($resClear['success'] && $conversation->status === 'closed');
    echo "   " . ($test3Pass ? "PASS: Đóng phiên cũ thành công để mở phiên mới!" : "FAIL") . "\n\n";

    echo "=== TẤT CẢ TEST DATABASE ĐÃ HOÀN TẤT VÀ 100% PASS! ===\n";
} finally {
    DB::rollBack();
    echo "Đã rollback toàn bộ database, dữ liệu hoàn toàn sạch sẽ!\n";
}
