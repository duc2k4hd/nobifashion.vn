<?php

namespace App\Services;

use App\Models\NewsletterSubscription;
use App\Services\AccountLogService;
use App\Services\MailConfigService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class NewsletterService
{
    public function __construct(
        protected AccountLogService $accountLogService
    ) {
    }

    /**
     * Đăng ký newsletter
     */
    public function subscribe(
        string $email,
        ?string $source = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): NewsletterSubscription {
        return DB::transaction(function () use ($email, $source, $ipAddress, $userAgent) {
            // Kiểm tra xem email đã tồn tại chưa
            $subscription = NewsletterSubscription::withTrashed()
                ->where('email', $email)
                ->first();

            if ($subscription) {
                // Nếu đã bị xóa mềm, restore lại
                if ($subscription->trashed()) {
                    $subscription->restore();
                }

                // Nếu đang unsubscribed, chuyển về pending
                if ($subscription->status === 'unsubscribed') {
                    $subscription->update([
                        'status' => 'pending',
                        'verify_token' => $this->generateToken(),
                        'source' => $source ?? $subscription->source,
                        'ip_address' => $ipAddress ?? $subscription->ip_address,
                        'user_agent' => $userAgent ?? $subscription->user_agent,
                        'verified_at' => null,
                    ]);
                } else {
                    // Cập nhật thông tin nếu cần
                    $subscription->update([
                        'source' => $source ?? $subscription->source,
                        'ip_address' => $ipAddress ?? $subscription->ip_address,
                        'user_agent' => $userAgent ?? $subscription->user_agent,
                    ]);
                }
            } else {
                // Tạo mới
                $subscription = NewsletterSubscription::create([
                    'email' => $email,
                    'status' => 'pending',
                    'verify_token' => $this->generateToken(),
                    'source' => $source,
                    'ip_address' => $ipAddress,
                    'user_agent' => $userAgent,
                ]);
            }

            // Gửi email xác nhận
            $this->sendVerifyEmail($subscription);

            // Log
            $this->accountLogService->record(
                'newsletter.subscribed',
                0, // Không có account_id
                null,
                [],
                [
                    'email' => $email,
                    'source' => $source,
                    'ip' => $ipAddress,
                ],
                false
            );

            return $subscription->fresh();
        });
    }

    /**
     * Xác thực email qua token
     */
    public function verifyEmail(string $token): ?NewsletterSubscription
    {
        $subscription = NewsletterSubscription::where('verify_token', $token)
            ->where('status', 'pending')
            ->first();

        if (!$subscription) {
            return null;
        }

        $subscription->markAsVerified();

        // Log
        $this->accountLogService->record(
            'newsletter.verified',
            0,
            null,
            [],
            [
                'email' => $subscription->email,
            ],
            false
        );

        return $subscription;
    }

    /**
     * Hủy đăng ký
     */
    public function unsubscribe(string $tokenOrEmail): ?NewsletterSubscription
    {
        $subscription = NewsletterSubscription::where(function ($query) use ($tokenOrEmail) {
            $query->where('verify_token', $tokenOrEmail)
                ->orWhere('email', $tokenOrEmail);
        })
        ->where('status', '!=', 'unsubscribed')
        ->first();

        if (!$subscription) {
            return null;
        }

        $subscription->markAsUnsubscribed();

        // Log
        $this->accountLogService->record(
            'newsletter.unsubscribed',
            0,
            null,
            [],
            [
                'email' => $subscription->email,
            ],
            false
        );

        return $subscription;
    }

    /**
     * Gửi email xác nhận
     */
    public function sendVerifyEmail(NewsletterSubscription $subscription): void
    {
        if (!$subscription->verify_token) {
            $subscription->generateVerifyToken();
        }

        $verifyUrl = route('newsletter.verify', ['token' => $subscription->verify_token]);

        $emailAccountId = config('email_defaults.newsletter_verify');
        MailConfigService::sendWithAccount($emailAccountId, function () use ($subscription, $verifyUrl) {
            Mail::to($subscription->email)->send(
                new \App\Mail\NewsletterVerifyMail($subscription, $verifyUrl)
            );
        });
    }

    /**
     * Gửi email marketing hàng loạt
     */
    public function sendMarketingEmail(
        array $subscriptionIds,
        string $subject,
        string $template = 'marketing',
        array $data = [],
        ?int $emailAccountId = null
    ): array {
        $subscriptions = NewsletterSubscription::whereIn('id', $subscriptionIds)
            ->subscribed()
            ->get();

        $results = [
            'success' => [],
            'failed' => [],
            'total' => $subscriptions->count(),
        ];

        foreach ($subscriptions as $subscription) {
            try {
                // Tạo unsubscribe token nếu chưa có
                if (!$subscription->verify_token) {
                    $subscription->generateVerifyToken();
                }
                
                // Merge data với subscription info
                $emailData = array_merge($data, [
                    'unsubscribe_url' => route('newsletter.unsubscribe', ['token' => $subscription->verify_token]),
                ]);
                
                MailConfigService::sendWithAccount($emailAccountId, function () use ($subscription, $subject, $template, $emailData, $emailAccountId) {
                    Mail::to($subscription->email)->send(
                        new \App\Mail\NewsletterMarketingMail(
                            $subscription,
                            $subject,
                            $template,
                            $emailData,
                            $emailAccountId
                        )
                    );
                });

                $results['success'][] = $subscription->email;

                // Log từng email thành công
                $this->accountLogService->record(
                    'newsletter.marketing_sent',
                    0,
                    null,
                    [],
                    [
                        'email' => $subscription->email,
                        'subject' => $subject,
                        'template' => $template,
                    ],
                    false
                );
            } catch (\Exception $e) {
                $results['failed'][] = [
                    'email' => $subscription->email,
                    'error' => $e->getMessage(),
                ];

                // Log lỗi
                $this->accountLogService->record(
                    'newsletter.marketing_failed',
                    0,
                    null,
                    [],
                    [
                        'email' => $subscription->email,
                        'subject' => $subject,
                        'error' => $e->getMessage(),
                    ],
                    false
                );
            }
        }

        // Log tổng kết chiến dịch
        $this->accountLogService->record(
            'newsletter.campaign_completed',
            0,
            null,
            [],
            [
                'template' => $template,
                'subject' => $subject,
                'sent_to' => $results['success'],
                'total_sent' => count($results['success']),
                'total_failed' => count($results['failed']),
                'status' => count($results['failed']) === 0 ? 'success' : 'partial',
            ],
            false
        );

        return $results;
    }

    /**
     * Tạo token ngẫu nhiên
     */
    protected function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }
}

