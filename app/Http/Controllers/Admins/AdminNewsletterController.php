<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\NewsletterFilterRequest;
use App\Http\Requests\Admin\NewsletterSendBulkRequest;
use App\Http\Requests\Admin\NewsletterStatusUpdateRequest;
use App\Models\AccountLog;
use App\Models\NewsletterSubscription;
use App\Services\AccountLogService;
use App\Services\NewsletterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class AdminNewsletterController extends Controller
{
    public function __construct(
        protected NewsletterService $newsletterService,
        protected AccountLogService $accountLogService
    ) {
    }

    /**
     * Danh sách newsletter subscriptions
     */
    public function index(NewsletterFilterRequest $request): View
    {
        $query = NewsletterSubscription::query();

        // Filter theo status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter theo source
        if ($request->filled('source')) {
            $query->where('source', $request->input('source'));
        }

        // Filter theo date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        $subscriptions = $query->latest()->paginate(20);

        // Thống kê
        $stats = [
            'total' => NewsletterSubscription::count(),
            'subscribed' => NewsletterSubscription::subscribed()->count(),
            'pending' => NewsletterSubscription::pending()->count(),
            'unsubscribed' => NewsletterSubscription::unsubscribed()->count(),
        ];

        // Danh sách sources
        $sources = NewsletterSubscription::select('source')
            ->whereNotNull('source')
            ->distinct()
            ->pluck('source')
            ->filter();

        return view('admins.newsletters.index', compact('subscriptions', 'stats', 'sources'));
    }

    /**
     * Chi tiết subscription
     */
    public function show($id): View
    {
        $subscription = NewsletterSubscription::findOrFail($id);

        // Lấy logs liên quan từ account_logs nếu có email giống nhau
        $relatedLogs = AccountLog::where('payload->email', $subscription->email)
            ->orWhere(function ($query) use ($subscription) {
                $query->where('type', 'like', 'newsletter.%')
                    ->whereJsonContains('payload->meta->email', $subscription->email);
            })
            ->latest()
            ->limit(50)
            ->get();

        return view('admins.newsletters.show', compact('subscription', 'relatedLogs'));
    }

    /**
     * Xóa subscription
     */
    public function destroy($id): JsonResponse|RedirectResponse
    {
        $subscription = NewsletterSubscription::findOrFail($id);
        $email = $subscription->email;

        $subscription->delete();

        // Log
        $this->accountLogService->record(
            'newsletter.deleted',
            0,
            null,
            [],
            ['email' => $email],
            false
        );

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Đã xóa đăng ký newsletter thành công.',
            ]);
        }

        return redirect()->route('admin.newsletters.index')
            ->with('success', 'Đã xóa đăng ký newsletter thành công.');
    }

    /**
     * Thay đổi trạng thái
     */
    public function changeStatus(
        $id,
        NewsletterStatusUpdateRequest $request
    ): JsonResponse|RedirectResponse {
        $subscription = NewsletterSubscription::findOrFail($id);
        $oldStatus = $subscription->status;
        $newStatus = $request->input('status');

        $subscription->update([
            'status' => $newStatus,
            'note' => $request->input('note'),
        ]);

        // Nếu chuyển sang subscribed và chưa verify, đánh dấu đã verify
        if ($newStatus === 'subscribed' && !$subscription->verified_at) {
            $subscription->update(['verified_at' => now()]);
        }

        // Log
        $this->accountLogService->record(
            'newsletter.status_changed',
            0,
            null,
            [
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ],
            [
                'email' => $subscription->email,
                'note' => $request->input('note'),
            ],
            false
        );

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Đã cập nhật trạng thái thành công.',
                'subscription' => $subscription->fresh(),
            ]);
        }

        return redirect()->back()
            ->with('success', 'Đã cập nhật trạng thái thành công.');
    }

    /**
     * Gửi lại email xác nhận
     */
    public function resendVerifyEmail($id): JsonResponse|RedirectResponse
    {
        // Rate limiting
        $key = 'newsletter_resend_' . $id;
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            $message = "Vui lòng đợi {$seconds} giây trước khi gửi lại email.";

            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 429);
            }

            return redirect()->back()->with('error', $message);
        }

        RateLimiter::hit($key, 60); // 1 phút

        $subscription = NewsletterSubscription::findOrFail($id);

        if ($subscription->status === 'unsubscribed') {
            $message = 'Không thể gửi email xác nhận cho người đã hủy đăng ký.';

            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 400);
            }

            return redirect()->back()->with('error', $message);
        }

        // Tạo token mới nếu chưa có
        if (!$subscription->verify_token) {
            $subscription->generateVerifyToken();
        }

        // Gửi email
        try {
            $this->newsletterService->sendVerifyEmail($subscription);

            // Log
            $this->accountLogService->record(
                'newsletter.verify_resent',
                0,
                null,
                [],
                [
                    'email' => $subscription->email,
                ],
                false
            );

            $message = 'Đã gửi lại email xác nhận thành công.';

            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                ]);
            }

            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            $message = 'Có lỗi xảy ra khi gửi email: ' . $e->getMessage();

            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 500);
            }

            return redirect()->back()->with('error', $message);
        }
    }

    /**
     * Gửi email hàng loạt
     */
    public function sendBulkEmail(NewsletterSendBulkRequest $request): JsonResponse|RedirectResponse
    {
        // Rate limiting cho chiến dịch
        $key = 'newsletter_campaign_' . auth()->id();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            $message = "Bạn đã gửi quá nhiều chiến dịch. Vui lòng đợi {$seconds} giây.";

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 429);
            }

            return redirect()->back()->with('error', $message);
        }

        RateLimiter::hit($key, 3600); // 1 giờ

        $query = NewsletterSubscription::subscribed();

        // Filter theo status
        if ($request->filled('filter_status') && $request->input('filter_status') !== 'all') {
            $query->where('status', $request->input('filter_status'));
        }

        // Filter theo source
        if ($request->filled('filter_source')) {
            $query->where('source', $request->input('filter_source'));
        }

        // Filter theo date range
        if ($request->filled('filter_date_from')) {
            $query->whereDate('created_at', '>=', $request->input('filter_date_from'));
        }
        if ($request->filled('filter_date_to')) {
            $query->whereDate('created_at', '<=', $request->input('filter_date_to'));
        }

        // Nếu có subscription_ids cụ thể
        if ($request->filled('subscription_ids') && is_array($request->input('subscription_ids'))) {
            $query->whereIn('id', $request->input('subscription_ids'));
        }

        $subscriptions = $query->get();
        $subscriptionIds = $subscriptions->pluck('id')->toArray();

        if (empty($subscriptionIds)) {
            $message = 'Không có người đăng ký nào phù hợp với bộ lọc.';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 400);
            }

            return redirect()->back()->with('error', $message);
        }

        // Gửi email
        try {
            // Prepare data for email template
            $emailData = [];
            if ($request->filled('content')) {
                $emailData['content'] = $request->input('content');
            }
            if ($request->filled('cta_url')) {
                $emailData['cta_url'] = $request->input('cta_url');
            }
            if ($request->filled('cta_text')) {
                $emailData['cta_text'] = $request->input('cta_text');
            }
            if ($request->filled('footer')) {
                $emailData['footer'] = $request->input('footer');
            }
            
            // Set email mặc định nếu không chọn từ form
            $emailAccountId = $request->input('email_account_id') 
                ? (int) $request->input('email_account_id') 
                : config('email_defaults.newsletter_marketing');
            
            $results = $this->newsletterService->sendMarketingEmail(
                $subscriptionIds,
                $request->input('subject'),
                $request->input('template'),
                $emailData,
                $emailAccountId
            );

            $message = sprintf(
                'Đã gửi email thành công tới %d/%d người đăng ký.',
                count($results['success']),
                $results['total']
            );

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'results' => $results,
                ]);
            }

            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            $message = 'Có lỗi xảy ra khi gửi email: ' . $e->getMessage();

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 500);
            }

            return redirect()->back()->with('error', $message);
        }
    }

    /**
     * Form gửi chiến dịch
     */
    public function showCampaignForm(): View
    {
        $stats = [
            'subscribed' => NewsletterSubscription::subscribed()->count(),
        ];

        $sources = NewsletterSubscription::select('source')
            ->whereNotNull('source')
            ->distinct()
            ->pluck('source')
            ->filter();

        return view('admins.newsletters.campaign', compact('stats', 'sources'));
    }
}

