<?php

namespace App\Services;

use App\Models\Contact;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SpamDetector
{
    /**
     * Kiểm tra liên hệ có phải spam không
     */
    public function detect(Contact $contact): bool
    {
        $score = 0;
        $reasons = [];

        // 1. Kiểm tra email rác
        if ($this->isSpamEmail($contact->email)) {
            $score += 3;
            $reasons[] = 'Email rác';
        }

        // 2. Kiểm tra số điện thoại không hợp lệ
        if ($contact->phone && !$this->isValidPhone($contact->phone)) {
            $score += 2;
            $reasons[] = 'Số điện thoại không hợp lệ';
        }

        // 3. Kiểm tra gửi quá nhiều trong thời gian ngắn (rate limiting)
        if ($this->isRateLimited($contact->email, $contact->ip_address)) {
            $score += 5;
            $reasons[] = 'Gửi quá nhiều trong thời gian ngắn';
        }

        // 4. Kiểm tra nội dung spam
        if ($this->isSpamContent($contact->subject, $contact->message)) {
            $score += 2;
            $reasons[] = 'Nội dung có dấu hiệu spam';
        }

        // 5. Kiểm tra IP blacklist
        if ($this->isBlacklistedIp($contact->ip_address)) {
            $score += 4;
            $reasons[] = 'IP bị chặn';
        }

        $threshold = config('contact.spam_threshold', 5);
        $isSpam = $score >= $threshold;

        if ($isSpam) {
            Log::warning('Spam detected', [
                'contact_id' => $contact->id,
                'email' => $contact->email,
                'ip' => $contact->ip_address,
                'score' => $score,
                'reasons' => $reasons,
            ]);
        }

        return $isSpam;
    }

    /**
     * Kiểm tra email rác
     */
    private function isSpamEmail(?string $email): bool
    {
        if (!$email) {
            return false;
        }

        $spamDomains = [
            'tempmail.com',
            '10minutemail.com',
            'guerrillamail.com',
            'mailinator.com',
            'throwaway.email',
        ];

        $domain = substr(strrchr($email, '@'), 1);
        return in_array(strtolower($domain), $spamDomains);
    }

    /**
     * Kiểm tra số điện thoại hợp lệ (VN format)
     */
    private function isValidPhone(?string $phone): bool
    {
        if (!$phone) {
            return true; // Phone không bắt buộc
        }

        // Loại bỏ khoảng trắng và ký tự đặc biệt
        $cleaned = preg_replace('/[^0-9]/', '', $phone);

        // Kiểm tra format VN: 10-11 số, bắt đầu bằng 0 hoặc +84
        if (preg_match('/^(0|\+84)[0-9]{9,10}$/', $cleaned)) {
            return true;
        }

        return false;
    }

    /**
     * Kiểm tra rate limiting (gửi quá nhiều trong thời gian ngắn)
     */
    private function isRateLimited(?string $email, ?string $ip): bool
    {
        $key = 'contact_rate_limit:' . ($email ?? $ip ?? 'unknown');
        $count = Cache::get($key, 0);

        // Cho phép tối đa N liên hệ trong 1 giờ từ cùng email/IP
        $limit = config('contact.rate_limit_per_hour', 5);
        if ($count >= $limit) {
            return true;
        }

        // Tăng counter
        Cache::put($key, $count + 1, now()->addHour());

        return false;
    }

    /**
     * Kiểm tra nội dung spam
     */
    private function isSpamContent(?string $subject, ?string $message): bool
    {
        $spamKeywords = [
            'viagra',
            'casino',
            'loan',
            'credit',
            'debt',
            'winner',
            'prize',
            'click here',
            'buy now',
            'limited time',
        ];

        $text = strtolower(($subject ?? '') . ' ' . ($message ?? ''));

        foreach ($spamKeywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Kiểm tra IP blacklist
     */
    private function isBlacklistedIp(?string $ip): bool
    {
        if (!$ip) {
            return false;
        }

        // Lấy blacklist từ config
        $blacklist = config('contact.ip_blacklist', []);

        return in_array($ip, $blacklist);
    }

    /**
     * Reset rate limit cho email/IP (dùng khi admin xác nhận không phải spam)
     */
    public function resetRateLimit(?string $email, ?string $ip): void
    {
        if ($email) {
            Cache::forget('contact_rate_limit:' . $email);
        }
        if ($ip) {
            Cache::forget('contact_rate_limit:' . $ip);
        }
    }
}

