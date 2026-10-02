<?php

/**
 * Service xử lý nén và ép gọn nội dung HTML bài viết.
 * Được sử dụng bởi:
 * - Artisan Command: php artisan posts:compress-html
 * - Tool Web Admin: /admin/tools (ép gọn HTML bài viết)
 * - Tự động khi Xuất CSV: PostImportExportController::exportCsv
 * - Tự động khi Nhập CSV/Excel: PostImportExportController::joinImportedContent
 */

namespace App\Services;

class HtmlCompressorService
{
    /**
     * Nén và ép gọn nội dung HTML bài viết:
     * - Loại bỏ comment HTML
     * - Chuyển toàn bộ \r\n, \r, \n, \t thành khoảng trắng đơn
     * - Rút gọn nhiều khoảng trắng liên tiếp trong văn bản thành 1 khoảng trắng
     * - Ép khoảng trắng giữa các thẻ đóng và mở: >   < thành ><
     * - Ép khoảng trắng thừa bên trong thẻ: <p > thành <p>, < /p> thành </p>
     * - Đảm bảo cấu trúc HTML, thẻ ảnh, link và nội dung tiếng Việt UTF-8 nguyên vẹn 100%
     */
    public static function compress(?string $html): string
    {
        if ($html === null || $html === '') {
            return '';
        }

        // 1. Loại bỏ các chú thích HTML thừa (giữ lại comment điều kiện nếu có)
        $html = preg_replace('/<!--(?!\s*(?:\[if [^\]]+]|<!|>))(?:(?!-->).)*-->/s', '', $html);

        // 2. Thay thế toàn bộ ký tự tab và xuống dòng bằng 1 dấu cách
        $html = str_replace(["\r\n", "\r", "\n", "\t"], ' ', $html);

        // 3. Rút gọn nhiều khoảng trắng liên tiếp thành 1 khoảng trắng duy nhất
        $html = preg_replace('/\s{2,}/u', ' ', $html);

        // 4. Ép khoảng trắng giữa các thẻ: >   < thành ><
        $html = preg_replace('/>\s+</u', '><', $html);

        // 5. Loại bỏ khoảng trắng dư ở đầu và cuối thẻ: <p > thành <p>, < /p> thành </p>
        $html = preg_replace('/\s+>/u', '>', $html);
        $html = preg_replace('/<\s+/u', '<', $html);

        return trim($html);
    }
}
