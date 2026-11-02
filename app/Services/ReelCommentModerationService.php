<?php

namespace App\Services;

use App\Models\CommentModerationRule;
use Illuminate\Support\Facades\Cache;

class ReelCommentModerationService implements ReelCommentModeratorInterface
{
    /**
     * Danh sách từ tục tĩu tiếng Việt cốt lõi (xử lý cả có dấu và không dấu)
     */
    protected array $vietnameseProfanities = [
        'đụ', 'địt', 'lồn', 'cặc', 'buồi', 'đéo', 'đếch', 'đậu má', 'đụ má', 'đĩ',
        'chó đẻ', 'mẹ kiếp', 'bú cặc', 'bú lồn', 'vcl', 'clgt', 'dkm', 'đkm', 'đcm',
        'vl', 'vcl', 'vcc', 'đmm', 'vkl', 'đm', 'dm', 'đcmm', 'cc', 'sv', 'óc chó'
    ];

    /**
     * Danh sách từ tục tĩu tiếng Anh cốt lõi
     */
    protected array $englishProfanities = [
        'fuck', 'fucking', 'fucker', 'shit', 'bitch', 'asshole', 'dick', 'pussy',
        'cunt', 'bastard', 'slut', 'whore', 'motherfucker', 'nigger', 'nigga', 'cock'
    ];

    /**
     * Danh sách whitelist từ hợp lệ dễ bị hiểu nhầm (False positive prevention)
     */
    protected array $whitelistWords = [
        'thích', 'lịch', 'phúc', 'chúc', 'đức', 'quốc', 'bức', 'mức', 'lực', 'thực',
        'phục', 'nước', 'được', 'bước', 'trước', 'áo len', 'áo polo', 'quần short', 'chất lượng'
    ];

    /**
     * Kiểm duyệt tổng hợp
     */
    public function moderate(string $content, array $context = []): array
    {
        $rawContent = trim($content);

        // 1. Kiểm tra rỗng
        if ($rawContent === '') {
            return [
                'decision' => 'BLOCK',
                'reason' => 'EMPTY_CONTENT',
                'flagged_word' => null,
                'user_message' => 'Nội dung bình luận không được để trống.',
            ];
        }

        // 2. Kiểm tra độ dài tối đa (500 ký tự)
        if (mb_strlen($rawContent) > 500) {
            return [
                'decision' => 'BLOCK',
                'reason' => 'CONTENT_TOO_LONG',
                'flagged_word' => null,
                'user_message' => 'Bình luận tối đa 500 ký tự. Vui lòng rút ngắn nội dung.',
            ];
        }

        // 3. Chuẩn hóa văn bản chống né bộ lọc
        $normalized = $this->normalizeText($rawContent);

        // 4. KIỂM TRA CHẶN LINK / URL (Tuyệt đối không cho phép gửi liên kết)
        if ($this->containsLink($normalized) || $this->containsLink($rawContent)) {
            return [
                'decision' => 'BLOCK',
                'reason' => 'URL_DETECTED',
                'flagged_word' => 'Link/URL',
                'user_message' => 'Bạn không thể gửi liên kết trong bình luận. Vui lòng chỉ sử dụng văn bản, emoji và sticker.',
            ];
        }

        // 5. Kiểm tra mã HTML / Script Injection
        if ($this->containsHtmlOrScript($rawContent)) {
            return [
                'decision' => 'BLOCK',
                'reason' => 'SCRIPT_INJECTION',
                'flagged_word' => 'HTML/Script',
                'user_message' => 'Nội dung chứa ký tự không an toàn.',
            ];
        }

        // 6. Kiểm tra spam lặp ký tự vô nghĩa (VD: aaaaaaaa, hhhhhhh, !!!!!!!!)
        if (preg_match('/(.)\\1{7,}/u', $rawContent)) {
            return [
                'decision' => 'BLOCK',
                'reason' => 'CHAR_SPAM',
                'flagged_word' => null,
                'user_message' => 'Vui lòng không gửi bình luận lặp ký tự vô nghĩa.',
            ];
        }

        // 7. Kiểm tra từ cấm tiếng Việt & Tiếng Anh
        $profanityMatch = $this->detectProfanity($normalized, $rawContent);
        if ($profanityMatch) {
            return [
                'decision' => 'BLOCK',
                'reason' => 'PROFANITY_DETECTED',
                'flagged_word' => $profanityMatch,
                'user_message' => 'Bình luận chứa từ ngữ chưa phù hợp với chuẩn mực cộng đồng.',
            ];
        }

        // 8. Kiểm tra qua các quy tắc động từ Database (Nếu có cấu hình thêm)
        $dbRuleMatch = $this->checkDatabaseRules($normalized, $rawContent);
        if ($dbRuleMatch) {
            return $dbRuleMatch;
        }

        return [
            'decision' => 'ALLOW',
            'reason' => null,
            'flagged_word' => null,
            'user_message' => null,
        ];
    }

    /**
     * Chuẩn hóa văn bản:
     * - Loại bỏ Zero-width characters
     * - Chuyển chữ hoa thành thường
     * - Đưa các ký tự leetspeak phổ biến về chữ cái chuẩn (@ -> a, 0 -> o, 1 -> i, 3 -> e...)
     * - Thu gọn khoảng trắng thừa
     */
    public function normalizeText(string $text): string
    {
        // 1. Loại bỏ ký tự ẩn / Zero-width characters
        $text = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}\x{00AD}]/u', '', $text);

        // 2. Chuyển Unicode chữ thường
        $text = mb_strtolower($text, 'UTF-8');

        // 3. Thay thế một số leetspeak phổ biến
        $leets = [
            '@' => 'a',
            '0' => 'o',
            '1' => 'i',
            '3' => 'e',
            '5' => 's',
            '7' => 't',
            '!' => 'i',
            '$' => 's',
        ];
        $text = strtr($text, $leets);

        // 4. Thu gọn khoảng trắng
        $text = preg_replace('/\s+/u', ' ', $text);

        return trim($text);
    }

    /**
     * Nhận diện Link / URL:
     * Hỗ trợ phát hiện https://, http://, www., domain với TLD phổ biến, link có dấu cách giả mạo (f a c e b o o k . c o m)
     */
    public function containsLink(string $text): bool
    {
        // 1. Kiểm tra scheme thông thường
        if (preg_match('~(https?://|www\.|ftp://)~iu', $text)) {
            return true;
        }

        // 2. Kiểm tra tên miền kèm TLD phổ biến (.vn, .com, .net, .org, .top, .xyz, .online, .site, .io, .me, .cc, .gg...)
        $tldRegex = '~(?:[a-zA-Z0-9-]+\.)+(?:com|vn|net|org|edu|gov|xyz|top|site|online|store|shop|me|io|cc|gg|tv|info|biz|link|vip|pro)\b~iu';
        if (preg_match($tldRegex, $text)) {
            return true;
        }

        // 3. Kiểm tra link né bộ lọc bằng khoảng trắng (VD: "n o b i f a s h i o n . v n" hoặc "bit . ly")
        $noSpaces = preg_replace('/[\s\._\-\*]+/u', '', $text);
        if (preg_match('~(?:facebook|zalo|tiktok|telegram|t\.me|bit\.ly|tinyurl|shopee|lazada)~iu', $noSpaces)) {
            // Nếu có từ khóa mạng xã hội đi kèm dấu chấm hoặc slash
            if (preg_match('~(?:facebook|zalo|tiktok|t\.me|bitly|shopee)\.(?:com|vn|me)~iu', $noSpaces) || str_contains($text, 'zalo.me') || str_contains($text, 't.me')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Kiểm tra HTML/Script
     */
    protected function containsHtmlOrScript(string $text): bool
    {
        return preg_match('/<[^>]*>|javascript:|onerror=|onload=/iu', $text) === 1;
    }

    /**
     * Nhận diện từ tục tĩu đa tầng (Tiếng Việt + Tiếng Anh)
     */
    protected function detectProfanity(string $normalized, string $raw): ?string
    {
        // Kiểm tra Whitelist trước (nếu chỉ chứa từ trong whitelist thì bỏ qua)
        foreach ($this->whitelistWords as $white) {
            if ($raw === $white || $normalized === $white) {
                return null;
            }
        }

        // 1. Kiểm tra tiếng Anh theo ranh giới từ (\b)
        foreach ($this->englishProfanities as $word) {
            $pattern = '/\b' . preg_quote($word, '/') . '\b/iu';
            if (preg_match($pattern, $normalized)) {
                return $word;
            }
        }

        // 2. Kiểm tra tiếng Việt (có dấu + không dấu + teencode cố tình viết dạng: c_ặ_c, đ.ụ, d_k_m)
        $noPunctuation = preg_replace('/[_\-\.\,\*\+\#\@\s]/u', '', $normalized);

        $allProfanities = array_merge($this->vietnameseProfanities);

        foreach ($allProfanities as $badWord) {
            // Chuẩn hóa bad word không dấu để so khớp
            $badNoSign = $this->stripVietnameseAccents($badWord);
            $badNoPunct = preg_replace('/\s+/u', '', $badWord);
            $badNoPunctNoSign = preg_replace('/\s+/u', '', $badNoSign);

            // Khớp chính xác cụm từ có dấu
            if (mb_strpos($normalized, $badWord) !== false) {
                return $badWord;
            }

            // Khớp dạng viết dính liền né lọc (VD: du ma -> duma, đụ má -> đụmá)
            if (mb_strlen($badNoPunct) >= 3 && mb_strpos($noPunctuation, $badNoPunct) !== false) {
                return $badWord;
            }

            // Khớp không dấu dạng dính liền (nếu từ cấm có độ dài >= 3 để tránh chặn nhầm)
            if (mb_strlen($badNoPunctNoSign) >= 4 && mb_strpos($noPunctuation, $badNoPunctNoSign) !== false) {
                return $badWord;
            }
        }

        return null;
    }

    /**
     * Kiểm tra thêm từ bảng Rules trong Database (được cache 10 phút)
     */
    protected function checkDatabaseRules(string $normalized, string $raw): ?array
    {
        try {
            $rules = Cache::remember('comment_moderation_rules_active', 600, function () {
                return CommentModerationRule::active()->get();
            });

            foreach ($rules as $rule) {
                $matched = false;
                if ($rule->rule_type === 'regex') {
                    $matched = @preg_match($rule->pattern, $raw) === 1;
                } else {
                    $keyword = mb_strtolower($rule->pattern, 'UTF-8');
                    $matched = mb_strpos($normalized, $keyword) !== false;
                }

                if ($matched) {
                    return [
                        'decision' => strtoupper($rule->action),
                        'reason' => 'RULE_' . strtoupper($rule->rule_type),
                        'flagged_word' => $rule->pattern,
                        'user_message' => $rule->action === 'block' 
                            ? 'Bình luận chứa nội dung chưa phù hợp quy định.' 
                            : null,
                    ];
                }
            }
        } catch (\Throwable $e) {}

        return null;
    }

    /**
     * Bỏ dấu tiếng Việt phục vụ so khớp ngữ nghĩa
     */
    protected function stripVietnameseAccents(string $str): string
    {
        $accents = [
            'a' => ['à','á','ạ','ả','ã','â','ầ','ấ','ậ','ẩ','ẫ','ă','ằ','ắ','ặ','ẳ','ẵ'],
            'e' => ['è','é','ẹ','ẻ','ẽ','ê','ề','ế','ệ','ể','ễ'],
            'i' => ['ì','í','ị','ỉ','ĩ'],
            'o' => ['ò','ó','ọ','ỏ','õ','ô','ồ','ố','ộ','ổ','ỗ','ơ','ờ','ớ','ợ','ở','ỡ'],
            'u' => ['ù','ú','ụ','ủ','ũ','ư','ừ','ứ','ự','ử','ữ'],
            'y' => ['ỳ','ý','ỵ','ỷ','ỹ'],
            'd' => ['đ'],
        ];

        foreach ($accents as $nonAccent => $accentList) {
            $str = str_replace($accentList, $nonAccent, $str);
        }

        return $str;
    }
}

