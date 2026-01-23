<?php

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use App\Services\NewsletterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class NewsletterController extends Controller
{
    public function __construct(
        protected NewsletterService $newsletterService
    ) {
    }

    /**
     * Đăng ký nhận bản tin (AJAX)
     */
    public function subscribe(Request $request)
    {
        $ip = $request->ip();
        $cacheKey = 'newsletter:ip:' . md5($ip);

        if (cache()->has($cacheKey)) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn đã đăng ký trong 24h qua. Vui lòng thử lại vào ngày mai.',
            ], 429);
        }

        $validator = Validator::make($request->all(), [
            'email'  => 'required|email|max:255',
            'source' => 'nullable|string|max:500', // Tăng max để chứa URL đầy đủ
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => "Dữ liệu không hợp lệ",
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            // Lấy URL hiện tại làm source (full URL với query params)
            $source = $request->fullUrl() ?? $request->url() ?? 'unknown';
            
            // Truncate nếu quá dài (giới hạn 255 ký tự của cột string)
            if (strlen($source) > 255) {
                $source = substr($source, 0, 252) . '...';
            }
            
            $subscription = $this->newsletterService->subscribe(
                $request->email,
                $source,
                $request->ip(),
                $request->userAgent()
            );

            cache()->put($cacheKey, true, now()->addDay());

            return response()->json([
                "success" => true,
                "message" => "🎀 Đăng ký thành công! Vui lòng kiểm tra email để xác nhận đăng ký."
            ]);
        } catch (\Exception $e) {
            return response()->json([
                "success" => false,
                "message" => "Có lỗi xảy ra: " . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Xác thực email qua token
     */
    public function verify(string $token): View|RedirectResponse
    {
        $subscription = $this->newsletterService->verifyEmail($token);

        if (!$subscription) {
            return view('clients.pages.newsletter.verify-result', [
                'success' => false,
                'message' => 'Token không hợp lệ hoặc đã hết hạn.',
            ]);
        }

        return view('clients.pages.newsletter.verify-result', [
            'success' => true,
            'message' => 'Xác nhận đăng ký thành công! Cảm ơn bạn đã đăng ký nhận thông báo.',
            'subscription' => $subscription,
        ]);
    }

    /**
     * Hủy đăng ký
     */
    public function unsubscribe(string $token): View|RedirectResponse
    {
        $subscription = $this->newsletterService->unsubscribe($token);

        if (!$subscription) {
            return view('clients.pages.newsletter.unsubscribe-result', [
                'success' => false,
                'message' => 'Token không hợp lệ hoặc bạn đã hủy đăng ký trước đó.',
            ]);
        }

        return view('clients.pages.newsletter.unsubscribe-result', [
            'success' => true,
            'message' => 'Đã hủy đăng ký thành công. Chúng tôi rất tiếc khi bạn rời đi!',
            'subscription' => $subscription,
        ]);
    }
}
