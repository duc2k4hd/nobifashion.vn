<?php

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\Setting;
use App\Services\CustomerChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ChatBotController extends Controller
{
    public function __construct(
        protected CustomerChatService $chatService
    ) {}

    /**
     * Lấy dữ liệu khởi tạo khung chat (Tin chào, gợi ý nhanh, thông tin thương hiệu)
     */
    public function getInitData(): JsonResponse
    {
        $setting = null;
        try {
            $setting = Setting::first();
        } catch (\Throwable) {
            // Giữ ứng dụng luôn hoạt động ổn định kể cả khi DB gặp lỗi tạm thời
        }

        return response()->json([
            'success' => true,
            'welcome_message' => config('chat.welcome_message'),
            'quick_suggestions' => config('chat.quick_suggestions'),
            'contact' => [
                'phone' => $setting->contact_phone ?? '0827 786 198',
                'zalo' => $setting->contact_zalo ?? '0827 786 198',
                'brand_name' => config('app.name', 'Nobi Fashion Việt Nam'),
            ],
        ]);
    }

    /**
     * Lấy danh sách tin nhắn từ Database theo session_id (hỗ trợ phân trang cuộn lên)
     */
    public function getMessages(Request $request): JsonResponse
    {
        $sessionId = trim((string) $request->input('session_id', ''));
        if ($sessionId === '') {
            return response()->json([
                'success' => true,
                'messages' => [],
                'has_more' => false,
            ]);
        }

        $conversation = ChatConversation::where('session_id', $sessionId)
            ->where('status', 'active')
            ->first();

        if (! $conversation) {
            return response()->json([
                'success' => true,
                'messages' => [],
                'has_more' => false,
            ]);
        }

        $query = ChatMessage::where('conversation_id', $conversation->id);

        // Hỗ trợ tải tin nhắn cũ hơn khi cuộn lên
        if ($request->filled('before_id')) {
            $query->where('id', '<', (int) $request->input('before_id'));
        }

        $limit = 25;
        $messagesDesc = $query->latest('id')->limit($limit + 1)->get();
        $hasMore = $messagesDesc->count() > $limit;
        $messages = $messagesDesc->slice(0, $limit)->reverse()->values();

        $formatted = $messages->map(function ($msg) {
            return [
                'id' => $msg->id,
                'role' => $msg->sender_type,
                'text' => $msg->message,
                'time' => $msg->created_at->format('H:i'),
                'articles' => $msg->metadata['articles'] ?? [],
            ];
        });

        return response()->json([
            'success' => true,
            'messages' => $formatted,
            'has_more' => $hasMore,
        ]);
    }

    /**
     * Gửi tin nhắn và lưu trực tiếp cả 2 chiều vào Database
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:1000',
            'session_id' => 'required|string|max:64',
        ]);

        $sessionId = trim((string) $request->input('session_id'));
        $messageText = trim((string) $request->input('message'));

        $conversation = $this->getOrCreateConversation($sessionId, $request);

        // 1. Lưu tin nhắn của khách vào Database
        $userMsg = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'sender_type' => 'user',
            'message' => $messageText,
        ]);

        // Lấy ngữ cảnh các lượt chat gần nhất của phiên này để duy trì mạch hội thoại theo từng khách hàng
        $recentContext = ChatMessage::where('conversation_id', $conversation->id)
            ->where('id', '<', $userMsg->id)
            ->latest('id')
            ->limit(5)
            ->get()
            ->reverse()
            ->map(fn ($m) => [
                'role' => $m->sender_type,
                'text' => Str::limit($m->message, 300),
                'articles' => $m->metadata['articles'] ?? [],
            ])
            ->values()
            ->all();

        // 2. Tìm kiếm phản hồi thấu hiểu ngữ cảnh và tâm lý từ Service
        $searchResult = $this->chatService->search($messageText, $recentContext);
        $replyText = $searchResult['reply_text'] ?? 'Dạ, em đã nhận được câu hỏi từ bạn!';
        $articles = $searchResult['articles'] ?? [];

        // 3. Lưu tin nhắn của Bot vào Database
        $botMsg = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'sender_type' => 'bot',
            'message' => $replyText,
            'metadata' => [
                'source' => $searchResult['source'] ?? 'default',
                'articles' => $articles,
            ],
        ]);

        // Cập nhật thời gian tương tác cuối
        $conversation->update(['last_message_at' => now()]);

        return response()->json([
            'success' => true,
            'data' => [
                'user_message' => [
                    'id' => $userMsg->id,
                    'role' => 'user',
                    'text' => $userMsg->message,
                    'time' => $userMsg->created_at->format('H:i'),
                ],
                'bot_message' => [
                    'id' => $botMsg->id,
                    'role' => 'bot',
                    'text' => $botMsg->message,
                    'time' => $botMsg->created_at->format('H:i'),
                    'articles' => $articles,
                ],
            ],
        ]);
    }

    /**
     * Xóa lịch sử phiên hiện tại (Đóng conversation cũ để tạo phiên mới)
     */
    public function clearHistory(Request $request): JsonResponse
    {
        $sessionId = trim((string) $request->input('session_id', ''));
        if ($sessionId !== '') {
            ChatConversation::where('session_id', $sessionId)->update(['status' => 'closed']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã làm mới cuộc trò chuyện thành công.',
        ]);
    }

    /**
     * Lấy hoặc tạo phiên hội thoại
     */
    protected function getOrCreateConversation(string $sessionId, Request $request): ChatConversation
    {
        $conversation = ChatConversation::where('session_id', $sessionId)
            ->where('status', 'active')
            ->first();

        $accountId = Auth::guard('web')->id();

        if (! $conversation) {
            $conversation = ChatConversation::create([
                'session_id' => $sessionId,
                'account_id' => $accountId,
                'ip_address' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 500),
                'status' => 'active',
                'last_message_at' => now(),
            ]);
        } elseif ($accountId && ! $conversation->account_id) {
            // Liên kết account nếu khách vừa đăng nhập trong cùng session
            $conversation->update(['account_id' => $accountId]);
        }

        return $conversation;
    }
}
