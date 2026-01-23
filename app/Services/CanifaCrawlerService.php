<?php

namespace App\Services;

use App\Models\Post;
use App\Models\Account;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use DOMDocument;
use DOMXPath;

class CanifaCrawlerService
{
    protected AIService $aiService;
    protected int $userId;

    public function __construct(AIService $aiService)
    {
        $this->aiService = $aiService;
        $this->userId = auth('web')->id() ?? 1;
    }

    /**
     * Crawl danh sách bài viết từ các danh mục
     */
    public function crawlCategories(array $categoryUrls): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
            'posts' => [],
        ];

        foreach ($categoryUrls as $categoryUrl) {
            $categoryUrl = trim($categoryUrl);
            if (empty($categoryUrl)) {
                continue;
            }

            try {
                $postUrls = $this->extractPostUrlsFromCategory($categoryUrl);
                
                foreach ($postUrls as $postUrl) {
                    try {
                        $post = $this->crawlPost($postUrl);
                        if ($post) {
                            $results['posts'][] = $post;
                            $results['success']++;
                        } else {
                            $results['failed']++;
                            $results['errors'][] = "Không thể crawl: {$postUrl}";
                        }
                    } catch (\Exception $e) {
                        $results['failed']++;
                        $results['errors'][] = "Lỗi crawl {$postUrl}: " . $e->getMessage();
                        Log::error('CanifaCrawler: Error crawling post', [
                            'url' => $postUrl,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            } catch (\Exception $e) {
                $results['errors'][] = "Lỗi crawl category {$categoryUrl}: " . $e->getMessage();
                Log::error('CanifaCrawler: Error crawling category', [
                    'url' => $categoryUrl,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Lấy danh sách URL bài viết từ trang danh mục
     */
    protected function extractPostUrlsFromCategory(string $categoryUrl): array
    {
        $html = $this->fetchHtml($categoryUrl);
        if (empty($html)) {
            return [];
        }

        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        $xpath = new DOMXPath($dom);

        // Tìm div có class chứa elementor-element-211db47a
        $containerQuery = "//div[contains(@class, 'elementor-element-211db47a')]";
        $containerNodes = $xpath->query($containerQuery);

        $postUrls = [];

        foreach ($containerNodes as $containerNode) {
            // Tìm tất cả div có class chứa type-post
            $postNodes = $xpath->query(".//div[contains(@class, 'type-post')]", $containerNode);

            foreach ($postNodes as $postNode) {
                // Tìm div có class elementor-widget-container đầu tiên
                $widgetContainer = $xpath->query(".//div[contains(@class, 'elementor-widget-container')]", $postNode)->item(0);
                
                if ($widgetContainer) {
                    // Tìm thẻ a đầu tiên trong widget-container
                    $linkNode = $xpath->query(".//a[@href]", $widgetContainer)->item(0);
                    
                    if ($linkNode && $linkNode->hasAttribute('href')) {
                        $url = $linkNode->getAttribute('href');
                        // Nếu là relative URL, chuyển thành absolute
                        if (!filter_var($url, FILTER_VALIDATE_URL)) {
                            $url = $this->makeAbsoluteUrl($categoryUrl, $url);
                        }
                        $postUrls[] = $url;
                    }
                }
            }
        }

        return array_unique($postUrls);
    }

    /**
     * Crawl một bài viết cụ thể
     */
    public function crawlPost(string $postUrl): ?Post
    {
        $html = $this->fetchHtml($postUrl);
        if (empty($html)) {
            return null;
        }

        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        $xpath = new DOMXPath($dom);

        // 1. Lấy title từ h1
        $titleNode = $xpath->query("//h1[contains(@class, 'elementor-heading-title')]")->item(0);
        $originalTitle = $titleNode ? trim($titleNode->textContent) : '';

        if (empty($originalTitle)) {
            return null;
        }

        // Dùng AI viết lại title
        $seoTitle = $this->aiService->rewriteTitle($originalTitle);
        if (empty($seoTitle)) {
            $seoTitle = $originalTitle;
        }

        // 2. Lấy meta description
        $metaDescNode = $xpath->query("//meta[@property='og:description']")->item(0);
        $originalDescription = $metaDescNode ? trim($metaDescNode->getAttribute('content')) : '';

        // Dùng AI viết lại description
        $seoDescription = '';
        if (!empty($originalDescription)) {
            $seoDescription = $this->aiService->rewriteDescription($originalDescription);
        }
        if (empty($seoDescription)) {
            $seoDescription = Str::limit(strip_tags($seoTitle), 200);
        }

        // 3. Tạo slug (KHÔNG chứa năm)
        $titleForSlug = str_replace('[NOBI]currentyear[NOBI]', '', $seoTitle);
        $titleForSlug = preg_replace('/\b(201[0-9]|202[0-9]|2030)\b/u', '', $titleForSlug);
        $titleForSlug = preg_replace('/\s+/', ' ', $titleForSlug);
        $titleForSlug = trim($titleForSlug);
        if (empty($titleForSlug)) {
            $titleForSlug = $originalTitle;
        }
        $slug = Str::slug($titleForSlug);
        $slug = $this->ensureUniqueSlug($slug);

        // 4. Tạo meta keywords bằng AI
        $metaKeywords = $this->aiService->generateMetaKeywords($seoTitle, $seoDescription);
        if (empty($metaKeywords)) {
            // Fallback: tạo keywords đơn giản từ title
            $metaKeywords = $this->generateFallbackKeywords($seoTitle);
        }

        // 5. Lấy content
        $contentHtml = $this->extractContent($xpath);
        if (empty($contentHtml)) {
            return null;
        }

        // Xử lý HTML: xóa class, thay thẻ a thành strong, xóa img, thay CANIFA
        $processedHtml = $this->processHtml($contentHtml);

        // Dùng AI viết lại content
        $aiContent = $this->aiService->rewriteContent($processedHtml);
        if (empty($aiContent)) {
            $aiContent = $processedHtml;
        }

        // Xử lý lại: chuyển div thành p, xóa class
        $finalContent = $this->finalizeContent($aiContent);

        // Tạo post
        $post = Post::create([
            'title' => $seoTitle,
            'slug' => $slug,
            'meta_title' => $seoTitle,
            'meta_description' => $seoDescription,
            'meta_keywords' => $metaKeywords,
            'meta_canonical' => 'https://nobifashion.vn/' . $slug,
            'content' => $finalContent,
            'status' => 'published',
            'published_at' => now(),
            'category_id' => 2,
            'created_by' => $this->userId,
            'account_id' => $this->userId,
        ]);

        return $post;
    }

    /**
     * Lấy nội dung từ div elementor-widget-theme-post-content
     */
    protected function extractContent(DOMXPath $xpath): string
    {
        $contentNode = $xpath->query("//div[contains(@class, 'elementor-widget-theme-post-content')]//div[contains(@class, 'elementor-widget-container')]")->item(0);
        
        if (!$contentNode) {
            return '';
        }

        $dom = $contentNode->ownerDocument;
        
        // Lấy toàn bộ HTML bên trong container
        $html = '';
        if ($contentNode->hasChildNodes()) {
            foreach ($contentNode->childNodes as $child) {
                $html .= $dom->saveHTML($child);
            }
        } else {
            // Nếu không có child nodes, lấy innerHTML
            $html = $dom->saveHTML($contentNode);
            // Loại bỏ thẻ div container bên ngoài
            $html = preg_replace('/^<div[^>]*>/', '', $html);
            $html = preg_replace('/<\/div>$/', '', $html);
        }

        return $html;
    }

    /**
     * Xử lý HTML: xóa class, thay thẻ a thành strong, xóa img, thay CANIFA
     */
    protected function processHtml(string $html): string
    {
        $dom = new DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        // Xóa tất cả class và các thuộc tính không cần thiết
        $xpath = new DOMXPath($dom);
        $allNodes = $xpath->query('//*');

        foreach ($allNodes as $node) {
            // Xóa class
            if ($node->hasAttribute('class')) {
                $node->removeAttribute('class');
            }
            
            // Xóa các thuộc tính khác không cần thiết
            $attrsToRemove = [];
            foreach ($node->attributes as $attribute) {
                $attrName = $attribute->name;
                // Giữ lại href, src, alt cho các thẻ cần thiết
                if (in_array($attrName, ['href', 'src', 'alt', 'title'])) {
                    continue;
                }
                // Xóa các thuộc tính không cần thiết
                if (in_array($attrName, ['id', 'style', 'onclick', 'onerror']) || 
                    strpos($attrName, 'data-') === 0) {
                    $attrsToRemove[] = $attrName;
                }
            }
            foreach ($attrsToRemove as $attrName) {
                $node->removeAttribute($attrName);
            }
        }

        // Thay thẻ a thành strong
        $linkNodes = $xpath->query('//a');
        $linksToReplace = [];
        foreach ($linkNodes as $linkNode) {
            $linksToReplace[] = $linkNode;
        }
        
        foreach ($linksToReplace as $linkNode) {
            $strongNode = $dom->createElement('strong');
            foreach ($linkNode->childNodes as $child) {
                $strongNode->appendChild($child->cloneNode(true));
            }
            if ($linkNode->parentNode) {
                $linkNode->parentNode->replaceChild($strongNode, $linkNode);
            }
        }

        // Xóa TẤT CẢ thẻ img
        $imgNodes = $xpath->query('//img');
        $imgsToRemove = [];
        foreach ($imgNodes as $imgNode) {
            $imgsToRemove[] = $imgNode;
        }
        foreach ($imgsToRemove as $imgNode) {
            if ($imgNode->parentNode) {
                $imgNode->parentNode->removeChild($imgNode);
            }
        }

        // Lấy HTML đã xử lý (chỉ lấy body content, không có DOCTYPE)
        $html = '';
        $bodyNodes = $xpath->query('//body');
        if ($bodyNodes->length > 0) {
            foreach ($bodyNodes->item(0)->childNodes as $child) {
                $html .= $dom->saveHTML($child);
            }
        } else {
            // Nếu không có body, lấy tất cả nodes
            $html = $dom->saveHTML();
            // Loại bỏ html và body tags
            $html = preg_replace('/<\/?html[^>]*>/i', '', $html);
            $html = preg_replace('/<\/?body[^>]*>/i', '', $html);
        }
        
        // Loại bỏ DOCTYPE và XML declaration
        $html = preg_replace('/<!DOCTYPE[^>]*>/i', '', $html);
        $html = preg_replace('/<\?xml[^>]*\?>/i', '', $html);
        
        // Thay CANIFA thành Nobi Fashion Việt Nam với link
        $html = preg_replace('/\b(CANIFA|canifa|canifa\.com)\b/i', '<a href="https://nobifashion.vn">Nobi Fashion Việt Nam</a>', $html);

        return trim($html);
    }

    /**
     * Xử lý cuối cùng: chuyển div thành p, xóa class, loại bỏ DOCTYPE và XML
     */
    protected function finalizeContent(string $html): string
    {
        // Xử lý nếu AI trả về markdown code block
        $html = $this->extractHtmlFromMarkdown($html);

        // Loại bỏ DOCTYPE và XML declaration nếu có
        $html = preg_replace('/<!DOCTYPE[^>]*>/i', '', $html);
        $html = preg_replace('/<\?xml[^>]*\?>/i', '', $html);
        
        // Loại bỏ markdown code block nếu còn sót lại
        $html = preg_replace('/```html\s*/i', '', $html);
        $html = preg_replace('/```\s*$/i', '', $html);
        $html = preg_replace('/^```\s*/i', '', $html);
        
        // Loại bỏ thẻ <p> chứa markdown code block
        $html = preg_replace('/<p>\s*```html\s*/i', '', $html);
        $html = preg_replace('/```\s*<\/p>/i', '', $html);
        
        $html = trim($html);

        // Nếu HTML rỗng hoặc không hợp lệ, trả về rỗng
        if (empty($html) || strlen($html) < 10) {
            return '';
        }

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        // Loại bỏ thẻ <article> nếu có - chỉ lấy nội dung bên trong
        $articleNodes = $xpath->query('//article');
        foreach ($articleNodes as $articleNode) {
            $fragment = $dom->createDocumentFragment();
            while ($articleNode->firstChild) {
                $fragment->appendChild($articleNode->firstChild);
            }
            if ($articleNode->parentNode) {
                $articleNode->parentNode->replaceChild($fragment, $articleNode);
            }
        }

        // Xóa TẤT CẢ thẻ img
        $imgNodes = $xpath->query('//img');
        $imgsToRemove = [];
        foreach ($imgNodes as $imgNode) {
            $imgsToRemove[] = $imgNode;
        }
        foreach ($imgsToRemove as $imgNode) {
            if ($imgNode->parentNode) {
                $imgNode->parentNode->removeChild($imgNode);
            }
        }

        // Xóa thẻ <figure> (không cần img nữa)
        $figureNodes = $xpath->query('//figure');
        $figuresToRemove = [];
        foreach ($figureNodes as $figureNode) {
            $figuresToRemove[] = $figureNode;
        }
        foreach ($figuresToRemove as $figureNode) {
            if ($figureNode->parentNode) {
                $figureNode->parentNode->removeChild($figureNode);
            }
        }

        // Xóa thẻ <picture> và <source>
        $pictureNodes = $xpath->query('//picture');
        $picturesToRemove = [];
        foreach ($pictureNodes as $pictureNode) {
            $picturesToRemove[] = $pictureNode;
        }
        foreach ($picturesToRemove as $pictureNode) {
            if ($pictureNode->parentNode) {
                $pictureNode->parentNode->removeChild($pictureNode);
            }
        }

        // Xóa thẻ <source>
        $sourceNodes = $xpath->query('//source');
        $sourcesToRemove = [];
        foreach ($sourceNodes as $sourceNode) {
            $sourcesToRemove[] = $sourceNode;
        }
        foreach ($sourcesToRemove as $sourceNode) {
            if ($sourceNode->parentNode) {
                $sourceNode->parentNode->removeChild($sourceNode);
            }
        }

        // Chuyển div thành p (nếu div không chứa block elements khác)
        $divNodes = $xpath->query('//div');
        $divsToReplace = [];
        foreach ($divNodes as $divNode) {
            $divsToReplace[] = $divNode;
        }

        foreach ($divsToReplace as $divNode) {
            $hasBlockChildren = false;
            foreach ($divNode->childNodes as $child) {
                if ($child->nodeType === XML_ELEMENT_NODE) {
                    $blockTags = ['div', 'p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'ul', 'ol', 'li', 'table', 'blockquote', 'img'];
                    if (in_array(strtolower($child->nodeName), $blockTags)) {
                        $hasBlockChildren = true;
                        break;
                    }
                }
            }

            if (!$hasBlockChildren && $divNode->parentNode) {
                $pNode = $dom->createElement('p');
                foreach ($divNode->childNodes as $child) {
                    $pNode->appendChild($child->cloneNode(true));
                }
                $divNode->parentNode->replaceChild($pNode, $divNode);
            }
        }

        // Xóa class và các thuộc tính không cần thiết
        $allowedTags = ['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'ul', 'ol', 'li', 'strong', 'em', 'b', 'i', 'u', 'a', 'br', 'blockquote'];
        $allNodes = $xpath->query('//*');
        $nodesToRemove = [];
        
        foreach ($allNodes as $node) {
            $tagName = strtolower($node->nodeName);
            
            // Xóa các thẻ không được phép
            if (!in_array($tagName, $allowedTags)) {
                $nodesToRemove[] = $node;
                continue;
            }
            
            // Xóa class
            if ($node->hasAttribute('class')) {
                $node->removeAttribute('class');
            }
            
            // Xóa các thuộc tính không cần thiết
            $attrsToRemove = [];
            foreach ($node->attributes as $attribute) {
                $attrName = $attribute->name;
                
                // Giữ lại các thuộc tính cần thiết
                if ($tagName === 'img' && in_array($attrName, ['src', 'alt'])) {
                    continue;
                }
                if ($tagName === 'a' && $attrName === 'href') {
                    continue;
                }
                
                // Xóa tất cả thuộc tính khác
                $attrsToRemove[] = $attrName;
            }
            
            foreach ($attrsToRemove as $attrName) {
                $node->removeAttribute($attrName);
            }
        }

        // Xóa các thẻ không được phép
        foreach ($nodesToRemove as $node) {
            if ($node->parentNode) {
                // Di chuyển children lên parent trước khi xóa
                $fragment = $dom->createDocumentFragment();
                while ($node->firstChild) {
                    $fragment->appendChild($node->firstChild);
                }
                $node->parentNode->replaceChild($fragment, $node);
            }
        }

        // Lấy HTML content
        $bodyContent = $dom->saveHTML();
        
        // Loại bỏ html, body, article tags nếu có
        $bodyContent = preg_replace('/<\/?html[^>]*>/i', '', $bodyContent);
        $bodyContent = preg_replace('/<\/?body[^>]*>/i', '', $bodyContent);
        $bodyContent = preg_replace('/<\/?article[^>]*>/i', '', $bodyContent);
        
        // Loại bỏ DOCTYPE và XML declaration một lần nữa
        $bodyContent = preg_replace('/<!DOCTYPE[^>]*>/i', '', $bodyContent);
        $bodyContent = preg_replace('/<\?xml[^>]*\?>/i', '', $bodyContent);
        
        // Decode HTML entities về UTF-8
        $bodyContent = html_entity_decode($bodyContent, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        $bodyContent = trim($bodyContent);

        return $bodyContent;
    }

    /**
     * Trích xuất HTML từ markdown code block nếu AI trả về markdown
     */
    protected function extractHtmlFromMarkdown(string $content): string
    {
        // Loại bỏ markdown code block wrapper
        $content = preg_replace('/^<p>\s*```html\s*/i', '', $content);
        $content = preg_replace('/```\s*<\/p>$/i', '', $content);
        $content = preg_replace('/^```html\s*/i', '', $content);
        $content = preg_replace('/```\s*$/i', '', $content);
        $content = preg_replace('/^```\s*/i', '', $content);
        $content = preg_replace('/```\s*$/i', '', $content);
        
        // Nếu có markdown code block, trích xuất HTML bên trong
        if (preg_match('/```html\s*(.*?)\s*```/is', $content, $matches)) {
            return trim($matches[1]);
        }
        
        if (preg_match('/```\s*(.*?)\s*```/is', $content, $matches)) {
            // Kiểm tra xem có phải HTML không
            $potentialHtml = trim($matches[1]);
            if (preg_match('/<[a-z][\s\S]*>/i', $potentialHtml)) {
                return $potentialHtml;
            }
        }

        return trim($content);
    }

    /**
     * Fetch HTML từ URL
     */
    protected function fetchHtml(string $url): string
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                ])
                ->get($url);

            if ($response->successful()) {
                return $response->body();
            }

            return '';
        } catch (\Exception $e) {
            Log::error('CanifaCrawler: Error fetching HTML', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return '';
        }
    }

    /**
     * Chuyển relative URL thành absolute URL
     */
    protected function makeAbsoluteUrl(string $baseUrl, string $relativeUrl): string
    {
        if (filter_var($relativeUrl, FILTER_VALIDATE_URL)) {
            return $relativeUrl;
        }

        $parsedBase = parse_url($baseUrl);
        $base = $parsedBase['scheme'] . '://' . $parsedBase['host'];
        
        if (strpos($relativeUrl, '/') === 0) {
            return $base . $relativeUrl;
        }

        $basePath = dirname($parsedBase['path'] ?? '/');
        return $base . $basePath . '/' . $relativeUrl;
    }

    /**
     * Đảm bảo slug là unique
     */
    protected function ensureUniqueSlug(string $slug): string
    {
        $originalSlug = $slug;
        $counter = 1;

        while (Post::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Tạo keywords fallback từ title nếu AI không trả về
     */
    protected function generateFallbackKeywords(string $title): string
    {
        // Loại bỏ các từ không cần thiết
        $stopWords = ['là', 'gì', 'của', 'và', 'với', 'cho', 'từ', 'đến', 'trong', 'về', 'theo', 'để', 'được', 'có', 'không', 'mà', 'này', 'đó', 'nào', 'sẽ', 'đã', 'đang'];
        
        // Tách title thành các từ
        $words = preg_split('/[\s,\.\-]+/u', mb_strtolower($title));
        
        // Loại bỏ stop words và từ quá ngắn
        $keywords = array_filter($words, function($word) use ($stopWords) {
            $word = trim($word);
            return !empty($word) 
                && mb_strlen($word) >= 2 
                && !in_array($word, $stopWords)
                && !is_numeric($word);
        });
        
        // Giới hạn 5-10 keywords
        $keywords = array_slice(array_unique($keywords), 0, 10);
        
        return implode(', ', $keywords);
    }
}
