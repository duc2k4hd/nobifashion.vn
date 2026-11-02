<?php

namespace App\Services;

use App\Models\CommentModerationRule;
use Illuminate\Support\Facades\Cache;

class ReelCommentModerationService implements ReelCommentModeratorInterface
{
    /**
     * Danh sách cụm từ xúc phạm, chửi bậy, mất dạy 3 miền Bắc - Trung - Nam
     */
    protected array $compoundProfanities = [
        // 1. Xúc phạm cha mẹ, gia đình, tổ tiên (Bắc - Trung - Nam)
        'con mẹ mày', 'con me may', 'mẹ mày', 'me may', 'mẹ kiếp', 'me kiep',
        'con mẹ', 'con me', 'bà mẹ mày', 'ba me may', 'đĩ mẹ mày', 'di me may',
        'đĩ mẹ', 'di me', 'đụ mẹ mày', 'du me may', 'đụ mẹ', 'du me',
        'đụ mạ mày', 'du ma may', 'đụ mạ', 'du ma', 'đụ má mày', 'du ma may',
        'đụ má', 'du ma', 'đậu má mày', 'đậu má', 'dau ma', 'đậu mẹ mày', 'đậu mẹ',
        'địt mẹ mày', 'dit me may', 'địt mẹ', 'dit me', 'địt con mẹ mày', 'dit con me may',
        'địt con mẹ', 'dit con me', 'địt cụ mày', 'dit cu may', 'địt cụ', 'dit cu',
        'địt cha mày', 'dit cha may', 'địt cha', 'dit cha', 'địt bố mày', 'dit bo may',
        'địt bà mày', 'dit ba may', 'mả cha mày', 'ma cha may', 'mả cha', 'mả mẹ mày',
        'ma me may', 'mả mẹ', 'tổ cha mày', 'to cha may', 'tổ cha', 'tổ mẹ mày',
        'to me may', 'tổ mẹ', 'tổ sư mày', 'to su may', 'tổ sư cha mày', 'tổ sư bố mày',
        'tiên sư mày', 'tien su may', 'tiên sư cha mày', 'thằng bố mày', 'thang bo may',
        'con mẹ nó', 'mẹ nó', 'cha mày', 'cha mi', 'mẹ mi', 'mụ nội mày', 'thằng cha mày',
        'con đĩ mẹ', 'bà già mày', 'ông già mày',

        // 2. Tình dục thô tục & bộ phận sinh dục ghép
        'bú cặc', 'bu cac', 'bú lồn', 'bu lon', 'bú dái', 'bu dai', 'bú cu', 'bu cu',
        'mút cu', 'mut cu', 'liếm lồn', 'liem lon', 'liếm đít', 'liem dit',
        'đụ nhau', 'du nhau', 'địt nhau', 'dit nhau', 'chịch nhau', 'chich nhau',
        'nện nhau', 'bóp vú', 'sờ lồn', 'vãi lồn', 'vai lon', 'vãi lìn', 'vai lin',
        'vãi cặc', 'vai cac', 'vãi buồi', 'vai buoi', 'xàm lồn', 'xam lon',
        'xạo lồn', 'xao lon', 'hãm lồn', 'ham lon', 'ngu lồn', 'ngu lon',
        'nứng lồn', 'nung lon', 'nứng cặc', 'nung cac', 'thèm cặc', 'them cac',
        'thèm lồn', 'them lon', 'mặt lồn', 'mat lon', 'mặt cặc', 'mat cac',
        'đầu buồi', 'dau buoi', 'óc cặc', 'oc cac', 'như lồn', 'nhu lon',
        'như cặc', 'nhu cac', 'như buồi', 'nhu buoi', 'chim cặc', 'củ cặc', 'cu bự',

        // 3. Nhục mạ, sỉ vỗ, làm nhục nhân phẩm (Bắc - Trung - Nam)
        'con đĩ', 'con di', 'đĩ chó', 'di cho', 'đĩ ngựa', 'di ngua', 'đĩ điếm',
        'con phò', 'con pho', 'làm phò', 'lam pho', 'phò phạch', 'con điếm',
        'chó đẻ', 'cho de', 'đồ chó', 'do cho', 'con chó', 'chó hoang', 'đồ súc vật',
        'súc vật', 'suc vat', 'súc sinh', 'suc sinh', 'óc chó', 'oc cho',
        'óc lợn', 'oc lon', 'óc heo', 'oc heo', 'ngu như chó', 'ngu nhu cho',
        'ngu như bò', 'ngu nhu bo', 'đồ rác rưởi', 'thằng rác rưởi',
        'mất dạy', 'thằng mất dạy', 'con mất dạy', 'đồ vô học',
        'khốn nạn', 'thằng khốn', 'con khốn', 'tiện nhân', 'dâm tặc', 'biến thái',
        'thằng mặt lồn', 'con điếm già', 'đồ tiện tì',

        // 4. Chửi thề khác & Teencode cụm
        'ăn cứt', 'an cut', 'vãi cứt', 'vai cut', 'ăn đái', 'ăn ỉa', 'bố láo', 'bố lếu',
        'láo chó', 'xàm xí', 'conmemay', 'ditmemay', 'ditconmemay', 'dumamay', 'dume may',
        'vlol', 'vlon', 'clgt', 'cmnr'
    ];

    /**
     * Danh sách từ đơn tục tĩu (cần kiểm tra ranh giới từ)
     */
    protected array $singleProfanities = [
        'lồn', 'cặc', 'buồi', 'đụ', 'địt', 'chịch', 'xoạc', 'phịch', 'đéo', 'đéch', 'đách', 'đếu',
        'đĩ', 'phò', 'buoi', 'cac', 'lon', 'loz', 'lozz', 'cứt', 'cẹc', 'dái', 'ỉa', 'đái', 'nện'
    ];

    /**
     * Danh sách từ viết tắt / Teencode chửi bậy
     */
    protected array $teencodeProfanities = [
        'dm', 'đm', 'dmm', 'đmm', 'dcm', 'đcm', 'dkm', 'đkm', 'đcmm', 'dcmm', 'đkmm', 'dkmm',
        'vcl', 'vkl', 'vcc', 'cc', 'cl', 'vl', 'sv'
    ];

    /**
     * Danh sách từ tục tĩu tiếng Anh cốt lõi & Hate speech
     */
    protected array $englishProfanities = [
        'fuck', 'fucking', 'fucker', 'fck', 'fuk', 'f*ck', 'motherfucker', 'mf',
        'shit', 'bullshit', 'shitty',
        'bitch', 'bitches', 'b*tch', 'son of a bitch',
        'asshole', 'ass', 'dumbass', 'jackass',
        'dick', 'dickhead', 'cock', 'cocksucker',
        'pussy', 'cunt',
        'bastard', 'slut', 'whore', 'hooker',
        'nigger', 'nigga',
        'retard', 'stfu', 'wtf', 'wth', 'fag', 'faggot'
    ];

    /**
     * Danh sách whitelist từ hợp lệ dễ bị hiểu nhầm (False positive prevention)
     */
    protected array $whitelistWords = [
        'thích', 'lịch', 'phúc', 'chúc', 'đức', 'quốc', 'bức', 'mức', 'lực', 'thực',
        'phục', 'nước', 'được', 'bước', 'trước', 'áo len', 'áo polo', 'quần short',
        'chất lượng', 'áo có', 'màu sắc', 'còn không', 'con không', 'chắc chắn', 'đặc biệt'
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
     * Nhận diện từ tục tĩu đa tầng (Tiếng Việt 3 miền + Tiếng Anh + Teencode né lọc)
     */
    protected function detectProfanity(string $normalized, string $raw): ?string
    {
        // 1. Kiểm tra Whitelist trước (nếu chỉ chứa từ trong whitelist thì bỏ qua)
        foreach ($this->whitelistWords as $white) {
            if ($raw === $white || $normalized === $white) {
                return null;
            }
        }

        $noSign = $this->stripVietnameseAccents($normalized);
        $noPunctNoSign = preg_replace('/[_\-\.\,\*\+\#\@\s\!\?\:\;\~\`\^\&]+/u', '', $noSign);

        // 2. Kiểm tra tiếng Anh theo ranh giới từ
        foreach ($this->englishProfanities as $word) {
            $pattern = '/(?<=^|[\s\p{P}])' . preg_quote($word, '/') . '(?=$|[\s\p{P}])/iu';
            if (preg_match($pattern, $normalized)) {
                return $word;
            }
        }

        // 3. Kiểm tra cụm từ xúc phạm (Compound profanities) theo ranh giới từ
        foreach ($this->compoundProfanities as $phrase) {
            $phraseNorm = mb_strtolower($phrase, 'UTF-8');
            $phraseNoSign = $this->stripVietnameseAccents($phraseNorm);
            $phraseCompact = preg_replace('/\s+/u', '', $phraseNoSign);

            // Regex khớp ranh giới từ cho cụm từ có dấu
            $patternNorm = '/(?<=^|[\s\p{P}])' . preg_quote($phraseNorm, '/') . '(?=$|[\s\p{P}])/iu';
            if (preg_match($patternNorm, $normalized)) {
                return $phrase;
            }

            // Regex khớp ranh giới từ cho cụm từ không dấu (ví dụ: con me may)
            $words = preg_split('/\s+/u', $phraseNoSign);
            $patternNoSign = '/(?<=^|[\s\p{P}])' . implode('[\s\p{P}]+', array_map(fn($w) => preg_quote($w, '/'), $words)) . '(?=$|[\s\p{P}])/iu';
            if (preg_match($patternNoSign, $noSign)) {
                return $phrase;
            }

            // Dạng viết liền né lọc (VD: c.o.n.m.e.m.a.y hoặc conmemay) với cụm từ đặc trưng >= 6 ký tự
            if (mb_strlen($phraseCompact) >= 6 && in_array($phraseCompact, ['conmemay', 'ditmemay', 'ditconmemay', 'dumamay', 'duoccho', 'chode'])) {
                if (mb_strpos($noPunctNoSign, $phraseCompact) !== false) {
                    return $phrase;
                }
            }
        }

        // 4. Kiểm tra từ đơn tục tĩu theo ranh giới từ
        foreach ($this->singleProfanities as $word) {
            $pattern = '/(?<=^|[\s\p{P}])' . preg_quote($word, '/') . '(?=$|[\s\p{P}])/iu';
            if (preg_match($pattern, $normalized)) {
                return $word;
            }

            $wordNoSign = $this->stripVietnameseAccents($word);
            $patternNoSign = '/(?<=^|[\s\p{P}])' . preg_quote($wordNoSign, '/') . '(?=$|[\s\p{P}])/iu';
            if (preg_match($patternNoSign, $noSign)) {
                return $word;
            }
        }

        // 5. Kiểm tra teencode theo ranh giới từ
        foreach ($this->teencodeProfanities as $code) {
            $pattern = '/(?<=^|[\s\p{P}])' . preg_quote($code, '/') . '(?=$|[\s\p{P}])/iu';
            if (preg_match($pattern, $noSign)) {
                return $code;
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

