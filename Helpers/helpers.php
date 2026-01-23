<?php

use App\Models\Setting;
use Illuminate\Support\Facades\Auth;

if (!function_exists('renderMeta')) {
    function renderMeta($text) {
        $shopName = Setting::get('subname', 'NOBI FASHION');

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
