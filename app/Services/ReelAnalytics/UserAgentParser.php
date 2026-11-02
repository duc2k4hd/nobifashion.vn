<?php

namespace App\Services\ReelAnalytics;

class UserAgentParser
{
    /**
     * Phân tích chuỗi User-Agent thành thông tin chi tiết
     */
    public static function parse(?string $userAgent): array
    {
        $ua = $userAgent ? trim($userAgent) : '';

        return [
            'device_type' => self::detectDeviceType($ua),
            'browser' => self::detectBrowser($ua),
            'platform' => self::detectPlatform($ua),
        ];
    }

    public static function detectDeviceType(string $ua): string
    {
        if (empty($ua)) return 'desktop';

        if (preg_match('/(tablet|ipad|playbook|silk)|(android(?!.*mobi))/i', $ua)) {
            return 'tablet';
        }

        if (preg_match('/(iphone|ipod|mobile|android.*mobile|blackberry|iemobile|opera mini|webos)/i', $ua)) {
            return 'mobile';
        }

        return 'desktop';
    }

    public static function detectBrowser(string $ua): string
    {
        if (empty($ua)) return 'Unknown';

        if (stripos($ua, 'coc_coc') !== false || stripos($ua, 'CocCoc') !== false) {
            return 'Cốc Cốc';
        }
        if (stripos($ua, 'Zalo') !== false) {
            return 'Zalo App';
        }
        if (stripos($ua, 'FBAV') !== false || stripos($ua, 'FBAN') !== false) {
            return 'Facebook App';
        }
        if (stripos($ua, 'musical_ly') !== false || stripos($ua, 'BytedanceWebview') !== false) {
            return 'TikTok App';
        }
        if (stripos($ua, 'SamsungBrowser') !== false) {
            return 'Samsung Browser';
        }
        if (stripos($ua, 'Edg') !== false) {
            return 'Microsoft Edge';
        }
        if (stripos($ua, 'OPR') !== false || stripos($ua, 'Opera') !== false) {
            return 'Opera';
        }
        if (stripos($ua, 'Chrome') !== false && stripos($ua, 'Chromium') === false) {
            return 'Chrome';
        }
        if (stripos($ua, 'Safari') !== false && stripos($ua, 'Chrome') === false) {
            return 'Safari';
        }
        if (stripos($ua, 'Firefox') !== false) {
            return 'Firefox';
        }

        return 'Other';
    }

    public static function detectPlatform(string $ua): string
    {
        if (empty($ua)) return 'Unknown';

        if (preg_match('/(iphone|ipad|ipod)/i', $ua)) {
            return 'iOS';
        }
        if (stripos($ua, 'Android') !== false) {
            return 'Android';
        }
        if (stripos($ua, 'Windows NT 10.0') !== false) {
            return 'Windows 10/11';
        }
        if (stripos($ua, 'Windows NT') !== false) {
            return 'Windows';
        }
        if (stripos($ua, 'Macintosh') !== false || stripos($ua, 'Mac OS X') !== false) {
            return 'macOS';
        }
        if (stripos($ua, 'Linux') !== false) {
            return 'Linux';
        }

        return 'Other';
    }
}
