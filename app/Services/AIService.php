<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIService
{
    protected string $apiKey;
    protected string $model;

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY', '');
        $this->model = env('GEMINI_MODEL', 'gemini-2.0-flash');
    }

    /**
     * Viết lại tiêu đề chuẩn SEO
     */
    public function rewriteTitle(string $originalTitle): string
    {
        $prompt = "Viết lại tiêu đề sau thành tiêu đề chuẩn SEO từ 70-80 ký tự (tính cả dấu câu):\n\n{$originalTitle}\n\nYêu cầu:\n- Tiêu đề chuẩn SEO, đúng intent, đúng từ khóa, đúng ngữ cảnh\n- Từ khóa có volume cao\n- Tiêu đề tự nhiên, dễ đọc, bắt mắt\n- KHÔNG chứa thương hiệu khác\n- KHÔNG chứa giá\n- KHÔNG chứa ngày tháng năm\n- KHÔNG chứa những thứ không liên quan\n- Chỉ trả về tiêu đề, không giải thích gì thêm";

        return $this->callAI($prompt);
    }

    /**
     * Viết lại mô tả chuẩn SEO
     */
    public function rewriteDescription(string $originalDescription): string
    {
        $prompt = "Viết lại mô tả sau thành mô tả chuẩn SEO từ 180-200 ký tự (tính cả dấu câu):\n\n{$originalDescription}\n\nYêu cầu:\n- Mô tả chuẩn SEO, đúng intent, đúng từ khóa, đúng ngữ cảnh\n- Từ khóa có volume cao\n- Mô tả tự nhiên, dễ đọc\n- KHÔNG chứa thương hiệu khác\n- KHÔNG chứa giá\n- KHÔNG chứa ngày tháng năm\n- KHÔNG chứa những thứ không liên quan\n- PHẢI có CTA của Nobi Fashion Việt Nam ở cuối\n- Chỉ trả về mô tả, không giải thích gì thêm";

        return $this->callAI($prompt);
    }

    /**
     * Viết lại nội dung bài viết
     */
    public function rewriteContent(string $htmlContent): string
    {
        $prompt = "Viết lại bài viết bằng HTML sau và trả về HTML đúng ngữ cảnh, đúng intent:\n\n{$htmlContent}\n\nYêu cầu:\n- Viết bài chuẩn SEO từ 800-1000 từ\n- Đúng từ khóa, đúng ngữ cảnh\n- Nội dung tự nhiên, hấp dẫn, dễ đọc\n- Bài viết chuẩn SEO, dễ lên top\n- Bài viết không dùng AI (viết tự nhiên như người viết)\n- Loại bỏ những phần có chữ 'Xem thêm' hoặc 'Xem thêm:'\n- Trả về HTML thuần túy, KHÔNG có DOCTYPE, KHÔNG có XML declaration, KHÔNG có markdown code block\n- Chỉ trả về HTML content thuần túy, không giải thích, không markdown, không code block\n- Chỉ dùng các thẻ: p, h2, h3, h4, h5, h6, ul, ol, li, strong, em, b, i, u, a, br, blockquote\n- Ví dụ: <p>Nội dung</p><h2>Tiêu đề</h2><p>Nội dung tiếp</p>";

        $result = $this->callAI($prompt);
        
        // Loại bỏ markdown code block nếu có
        $result = preg_replace('/```html\s*/i', '', $result);
        $result = preg_replace('/```\s*$/i', '', $result);
        $result = trim($result);
        
        return $result;
    }

    /**
     * Viết lại nội dung bài viết cho Routine (500-1000 từ, giữ nguyên img vị trí)
     */
    public function rewriteContentForRoutine(string $htmlContent): string
    {
        $prompt = "Viết lại bài viết bằng HTML sau và trả về HTML đúng ngữ cảnh, đúng intent:\n\n{$htmlContent}\n\nYêu cầu:\n- Viết bài chuẩn SEO từ 500-1000 từ và có thể nhiều hơn\n- Viết đúng những gì tôi gửi\n- Đặc biệt là img nào sẽ đi đúng với phần đó, không được thay đổi vị trí\n- Chỉ được thay đổi nội dung thật hay, giọng thật tự nhiên\n- Bài viết chuẩn SEO, dễ lên top\n- Bài viết không dùng AI (viết tự nhiên như người viết)\n- Trả về HTML thuần túy, KHÔNG có DOCTYPE, KHÔNG có XML declaration, KHÔNG có markdown code block\n- Chỉ trả về HTML content thuần túy, không giải thích, không markdown, không code block\n- Giữ nguyên tất cả thẻ img và vị trí của chúng\n- Ví dụ: <p>Nội dung</p><img src=\"...\" alt=\"...\"><h2>Tiêu đề</h2><p>Nội dung tiếp</p>";

        $result = $this->callAI($prompt);
        
        // Loại bỏ markdown code block nếu có
        $result = preg_replace('/```html\s*/i', '', $result);
        $result = preg_replace('/```\s*$/i', '', $result);
        $result = trim($result);
        
        return $result;
    }

    /**
     * Tạo meta keywords từ title và description
     */
    public function generateMetaKeywords(string $title, string $description): string
    {
        $prompt = "Dựa vào tiêu đề và mô tả sau, tạo 5-10 từ khóa SEO phù hợp:\n\nTiêu đề: {$title}\nMô tả: {$description}\n\nYêu cầu:\n- Tạo từ 5-10 từ khóa\n- Chỉ trả về các từ khóa, cách nhau bằng dấu phẩy\n- KHÔNG có số thứ tự, KHÔNG có dấu chấm, KHÔNG có dấu gạch đầu dòng\n- KHÔNG có giải thích, KHÔNG có text khác\n- Chỉ trả về: từ khóa 1, từ khóa 2, từ khóa 3, ...\n- Ví dụ: áo parka nữ, phối đồ parka, parka mùa đông, áo khoác parka, thời trang parka";

        $result = $this->callAI($prompt);
        
        // Xử lý kết quả để chỉ lấy keywords
        return $this->cleanMetaKeywords($result);
    }

    /**
     * Làm sạch meta keywords - chỉ giữ lại keywords cách nhau dấu phẩy
     */
    protected function cleanMetaKeywords(string $keywords): string
    {
        // Loại bỏ markdown, số thứ tự, dấu chấm, dấu gạch đầu dòng
        $keywords = preg_replace('/```[a-z]*\s*/i', '', $keywords);
        $keywords = preg_replace('/^\d+[\.\)]\s*/m', '', $keywords); // Xóa số thứ tự
        $keywords = preg_replace('/^[-•*]\s*/m', '', $keywords); // Xóa dấu gạch đầu dòng
        $keywords = preg_replace('/\.$/', '', $keywords); // Xóa dấu chấm cuối
        
        // Loại bỏ các từ không cần thiết
        $keywords = preg_replace('/\b(từ khóa|keywords?|meta keywords?|seo keywords?):?\s*/i', '', $keywords);
        $keywords = preg_replace('/\b(ví dụ|example|vd):?\s*/i', '', $keywords);
        
        // Chỉ giữ lại các từ khóa, cách nhau dấu phẩy
        $keywords = preg_replace('/[^\p{L}\p{N}\s,]/u', '', $keywords); // Chỉ giữ chữ, số, dấu phẩy, khoảng trắng
        
        // Tách thành mảng và làm sạch từng keyword
        $keywordArray = array_map('trim', explode(',', $keywords));
        $keywordArray = array_filter($keywordArray, function($keyword) {
            // Loại bỏ keyword rỗng hoặc quá ngắn (< 2 ký tự)
            $keyword = trim($keyword);
            return !empty($keyword) && mb_strlen($keyword) >= 2;
        });
        
        // Giới hạn 5-10 keywords
        $keywordArray = array_slice($keywordArray, 0, 10);
        
        // Loại bỏ duplicate
        $keywordArray = array_unique($keywordArray);
        
        // Chuyển về string, cách nhau dấu phẩy
        return implode(', ', $keywordArray);
    }

    /**
     * Gọi Gemini AI API
     */
    protected function callAI(string $prompt): string
    {
        if (empty($this->apiKey)) {
            Log::error('Gemini API key not configured');
            return '';
        }

        try {
            // Tạo full prompt với system instruction
            $fullPrompt = "Bạn là một chuyên gia SEO và copywriter chuyên nghiệp. Bạn viết nội dung tự nhiên, hấp dẫn và tối ưu SEO.\n\n" . $prompt;

            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

            $response = Http::timeout(300)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post($url, [
                    'contents' => [
                        [
                            'parts' => [
                                [
                                    'text' => $fullPrompt,
                                ],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.7,
                        'maxOutputTokens' => 4000,
                        'topP' => 0.95,
                        'topK' => 40,
                    ],
                ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Gemini response format: candidates[0].content.parts[0].text
                if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                    return trim($data['candidates'][0]['content']['parts'][0]['text']);
                }

                Log::error('Gemini API: Unexpected response format', [
                    'response' => $data,
                ]);

                return '';
            }

            Log::error('Gemini API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return '';
        } catch (\Exception $e) {
            Log::error('Gemini API exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return '';
        }
    }
}
