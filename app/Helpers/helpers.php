<?php

use App\Models\Setting;
use Illuminate\Support\Facades\Auth;

if (!function_exists('renderMeta')) {
    function renderMeta($text) {
        // Nếu chưa có settings trong config thì nạp vào
        if (!config()->has('settings')) {
            try {
                // Query DB 1 lần
                $settings = Setting::pluck('value', 'key')->toArray();
                config(['settings' => $settings]);
            } catch (\Exception $e) {
                // Nếu DB chưa migrate hoặc lỗi thì fallback rỗng
                config(['settings' => []]);
            }
        }

        

        // Lấy subname từ config, fallback mặc định
        $shopName = config('settings.subname', 'NOBI FASHION');

        return str_replace(
            [
                        '[NOBI]currentyear[NOBI]',
                        '[NOBI]subname[NOBI]'
                    ],
            [
                        date('n') >= 11 ? date('Y') + 1 : date('Y'),
                        $shopName
                    ],
            $text
        );
    }
}

if (!function_exists('replaceYearsWithPlaceholder')) {
    /**
     * Thay thế các năm từ 2010-2026 thành [NOBI]currentyear[NOBI]
     */
    function replaceYearsWithPlaceholder(string $text): string
    {
        $currentYear = date('Y');
        $placeholder = '[NOBI]currentyear[NOBI]';
        
        // Thay thế các năm từ 2010-2026
        for ($year = 2010; $year <= 2026; $year++) {
            $text = preg_replace('/\b' . $year . '\b/', $placeholder, $text);
        }
        
        return $text;
    }
}

