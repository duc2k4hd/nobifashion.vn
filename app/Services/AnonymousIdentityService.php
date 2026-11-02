<?php

namespace App\Services;

use App\Models\AnonymousUser;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie;

class AnonymousIdentityService
{
    public const COOKIE_NAME = 'nobi_anon_token';
    public const COOKIE_LIFETIME_DAYS = 365;

    /**
     * Lấy AnonymousUser từ Request (hoặc tự động tạo mới nếu chưa có)
     */
    public function resolveUser(Request $request, ?string &$outNewRawToken = null): AnonymousUser
    {
        $rawToken = $request->cookie(self::COOKIE_NAME);

        if ($rawToken && is_string($rawToken) && strlen($rawToken) >= 32) {
            $tokenHash = hash('sha256', $rawToken);
            $user = AnonymousUser::where('token_hash', $tokenHash)->first();

            if ($user) {
                // Cập nhật thời gian ghé thăm
                $user->update(['last_seen_at' => now()]);
                return $user;
            }
        }

        // Tạo danh tính ẩn danh mới
        $rawToken = Str::random(64);
        $tokenHash = hash('sha256', $rawToken);
        $meta = AnonymousNicknameGenerator::generateUniqueNickname();

        $user = AnonymousUser::create([
            'token_hash' => $tokenHash,
            'public_id' => 'anon_' . Str::lower(Str::random(16)),
            'nickname' => $meta['nickname'],
            'avatar_key' => $meta['avatar_key'],
            'avatar_color' => $meta['avatar_color'],
            'status' => 'active',
            'is_banned' => false,
            'last_seen_at' => now(),
        ]);

        $outNewRawToken = $rawToken;

        return $user;
    }

    /**
     * Tạo cookie HttpOnly bảo mật cao cho Anonymous Token
     */
    public function makeIdentityCookie(string $rawToken, Request $request): Cookie
    {
        $isSecure = $request->isSecure() || app()->environment('production');

        return cookie(
            self::COOKIE_NAME,
            $rawToken,
            60 * 24 * self::COOKIE_LIFETIME_DAYS, // Phút (1 năm)
            '/',
            null,
            $isSecure,
            true, // HttpOnly chống đọc qua XSS
            false,
            'lax'
        );
    }
}

