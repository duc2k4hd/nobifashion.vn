<?php

namespace App\Services;

use App\Models\Post;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CoolmateCrawlerService
{
    protected AIService $aiService;
    protected int $userId;

    public function __construct(AIService $aiService)
    {
        $this->aiService = $aiService;
        $this->userId = auth('web')->id() ?? 1;
    }

    /**
     * Crawl danh sách category URLs (JSON endpoint hoặc HTML)
     */
    public function crawlCategories(array $categoryUrls): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'posts' => [],
            'errors' => [],
        ];

        Log::info('=== COOLMATE CRAWLER START ===');
        Log::info('Total category URLs: ' . count($categoryUrls));

        foreach ($categoryUrls as $categoryUrl) {
            try {
                Log::info("Processing Coolmate category URL: {$categoryUrl}");
                $postUrls = $this->extractPostUrlsFromCategory($categoryUrl);
                Log::info("Found " . count($postUrls) . " post URLs from Coolmate category");

                if (empty($postUrls)) {
                    $results['errors'][] = "Không tìm thấy bài viết nào trong danh mục: {$categoryUrl}";
                    Log::warning("No posts found in Coolmate category: {$categoryUrl}");
                }

                foreach ($postUrls as $postUrl) {
                    try {
                        Log::info("Crawling Coolmate post URL: {$postUrl}");
                        $post = $this->crawlPost($postUrl);
                        if ($post) {
                            $results['success']++;
                            $results['posts'][] = [
                                'id' => $post->id,
                                'title' => $post->title,
                                'slug' => $post->slug,
                            ];
                            Log::info("Successfully crawled Coolmate post: {$post->title} (ID: {$post->id})");
                        } else {
                            $results['failed']++;
                            $results['errors'][] = "Không thể crawl bài viết: {$postUrl}";
                            Log::warning("Failed to crawl Coolmate post: {$postUrl}");
                        }
                    } catch (\Throwable $e) {
                        $results['failed']++;
                        $results['errors'][] = "Lỗi khi crawl {$postUrl}: " . $e->getMessage();
                        Log::error("Error crawling Coolmate post: {$postUrl}", [
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                    }
                }
            } catch (\Throwable $e) {
                $results['errors'][] = "Lỗi khi crawl danh mục {$categoryUrl}: " . $e->getMessage();
                Log::error("Error crawling Coolmate category: {$categoryUrl}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        Log::info("=== COOLMATE CRAWLER END === Success: {$results['success']}, Failed: {$results['failed']}");
        return $results;
    }

    /**
     * Từ 1 URL danh mục (JSON endpoint hoặc HTML), lấy list link bài viết
     *
     * - Nếu Content-Type: application/json → decode giống coolmate.json, lấy field "data"
     * - Ngược lại: parse trực tiếp HTML (giống debug.html)
     *
     * Cả 2 case đều dùng cùng 1 cấu trúc HTML: h5.post-title a.plain
     */
    protected function extractPostUrlsFromCategory(string $categoryUrl): array
    {
        Log::info("=== COOLMATE: EXTRACT POST URLS FROM CATEGORY ===");
        Log::info("Category URL: {$categoryUrl}");

        $response = Http::timeout(90)->get($categoryUrl);

        if (!$response->successful()) {
            Log::error('Coolmate category HTTP error', [
                'status' => $response->status(),
                'body' => Str::limit($response->body(), 500),
            ]);
            throw new \RuntimeException("Coolmate category HTTP error: " . $response->status());
        }

        $contentType = $response->header('Content-Type', '');
        $rawBody = $response->body();
        $html = '';

        if (str_contains($contentType, 'application/json')) {
            Log::info('Coolmate category responded with JSON, decoding...');
            $data = $response->json();
            if (!isset($data['data']) || !is_string($data['data'])) {
                Log::error('Coolmate JSON missing "data" field', ['body' => $data]);
                throw new \RuntimeException('Coolmate JSON missing "data" field');
            }
            // JSON decode đã chuyển \u003C thành <, nên chỉ cần dùng trực tiếp
            $html = $data['data'];
        } else {
            Log::info('Coolmate category responded with HTML');
            $html = $rawBody;
        }

        Log::info("Coolmate category HTML fragment length: " . strlen($html));

        if (empty($html)) {
            Log::warning("Empty HTML returned from Coolmate category URL");
            return [];
        }

        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        $xpath = new DOMXPath($dom);

        $postUrls = [];

        // Chuẩn theo debug.html: h5.post-title a[href]
        $linkNodes = $xpath->query("//h5[contains(@class,'post-title')]//a[@href]");
        Log::info("Coolmate: found {$linkNodes->length} post link nodes");

        foreach ($linkNodes as $linkNode) {
            /** @var \DOMElement $linkNode */
            $href = trim($linkNode->getAttribute('href'));
            if (empty($href)) {
                continue;
            }
            $absolute = $this->makeAbsoluteUrl($href, $categoryUrl);
            $postUrls[] = $absolute;
            Log::info("Coolmate: added post URL: {$absolute}");
        }

        $postUrls = array_values(array_unique($postUrls));
        Log::info("Coolmate: total unique post URLs: " . count($postUrls));

        return $postUrls;
    }

    /**
     * Crawl 1 bài viết Coolmate
     */
    public function crawlPost(string $postUrl): ?Post
    {
        ini_set('max_execution_time', 600);
        set_time_limit(600);

        Log::info("=== COOLMATE: CRAWL POST ===");
        Log::info("Post URL: {$postUrl}");

        // Dùng Playwright service để đảm bảo JS render đủ (đợi selector content xuất hiện)
        // Chỉ truyền selector chính, fallback sẽ xử lý trong PHP
        $html = $this->fetchHtml($postUrl, 'div.entry-content.single-page');
        Log::info("Coolmate post HTML length: " . strlen($html));

        if (empty($html)) {
            Log::warning("Empty HTML returned from Coolmate post URL");
            return null;
        }

        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        $xpath = new DOMXPath($dom);

        // 1. Title: <h1 class="entry-title">
        $titleNode = $xpath->query("//h1[contains(@class,'entry-title')]")->item(0);
        Log::info("Coolmate title node found: " . ($titleNode ? 'yes' : 'no'));

        $originalTitle = $titleNode ? trim($titleNode->textContent) : '';
        Log::info("Coolmate original title: " . ($originalTitle ?: 'EMPTY'));

        if (empty($originalTitle)) {
            Log::warning("Coolmate: empty title, skip post");
            return null;
        }

        // Viết lại tiêu đề chuẩn SEO bằng AI
        $seoTitle = $this->aiService->rewriteTitle($originalTitle);
        if (empty($seoTitle)) {
            $seoTitle = $originalTitle;
        }
        // Lưu bản raw để tạo slug (không chứa placeholder)
        $rawSeoTitleForSlug = $seoTitle;
        // Thay các năm (2010-2026) thành [NOBI]currentyear[NOBI] trong title (KHÔNG ảnh hưởng slug)
        if (function_exists('replaceYearsWithPlaceholder')) {
            $seoTitle = replaceYearsWithPlaceholder($seoTitle);
        }

        // 2. Meta description: <meta property="og:description">
        $metaDescNode = $xpath->query("//meta[@property='og:description']")->item(0);
        Log::info("Coolmate meta description node found: " . ($metaDescNode ? 'yes' : 'no'));

        $originalDescription = $metaDescNode && $metaDescNode->hasAttribute('content')
            ? trim($metaDescNode->getAttribute('content'))
            : '';
        Log::info("Coolmate original description: " . ($originalDescription ? Str::limit($originalDescription, 120) : 'EMPTY'));

        $seoDescription = '';
        if (!empty($originalDescription)) {
            $seoDescription = $this->aiService->rewriteDescription($originalDescription);
        }
        if (empty($seoDescription)) {
            $seoDescription = Str::limit(strip_tags($seoTitle), 200);
        }
        // Thay năm trong meta description (chỉ text, không đụng tới URL/ảnh)
        if (function_exists('replaceYearsWithPlaceholder')) {
            $seoDescription = replaceYearsWithPlaceholder($seoDescription);
        }

        // 3. Tạo slug (KHÔNG chứa năm, KHÔNG chứa Coolmate)
        //    Dùng bản raw trước khi chèn placeholder để slug không chứa [NOBI]...
        $titleForSlug = $rawSeoTitleForSlug;
        // Bỏ các năm 2000-2035
        $titleForSlug = preg_replace('/\b(20[0-3][0-9])\b/u', '', $titleForSlug);
        // Bỏ thương hiệu Coolmate
        $titleForSlug = preg_replace('/\b[Cc]oolmate(\.me)?\b/u', '', $titleForSlug);
        $titleForSlug = preg_replace('/\s+/', ' ', $titleForSlug);
        $titleForSlug = trim($titleForSlug);
        if (empty($titleForSlug)) {
            $titleForSlug = $originalTitle;
        }

        $slug = Str::slug($titleForSlug);
        $slug = preg_replace('/[^a-z0-9\-]/', '', $slug);
        $slug = preg_replace('/\-+/', '-', $slug);
        $slug = trim($slug, '-');
        if (empty($slug)) {
            $slug = Str::slug($originalTitle);
        }
        $slug = $this->ensureUniqueSlug($slug);
        $metaCanonical = 'https://nobifashion.vn/' . $slug;

        // 4. Thumbnail từ meta og:image
        $thumbnail = $this->extractThumbnail($xpath, $postUrl, $slug, $seoTitle);

        // 5. Content: theo yêu cầu mới -> tìm <article> trước, rồi lấy div.entry-content.single-page đầu tiên bên trong
        $articleNode = $xpath->query("//article")->item(0);
        Log::info("Coolmate article node found: " . ($articleNode ? 'yes' : 'no'));

        $contentDiv = null;

        if ($articleNode) {
            $contentDiv = $xpath->query(".//div[contains(@class,'entry-content') and contains(@class,'single-page')]", $articleNode)->item(0);
            Log::info("Coolmate: entry-content single-page inside article found: " . ($contentDiv ? 'yes' : 'no'));
        }

        // Fallback: nếu trong article không có thì tìm toàn trang
        if (!$contentDiv) {
            $contentDiv = $xpath->query("//div[contains(@class,'entry-content') and contains(@class,'single-page')]")->item(0);
            Log::info("Coolmate: entry-content single-page global found: " . ($contentDiv ? 'yes' : 'no'));
        }

        // Fallback 1: chỉ có entry-content
        if (!$contentDiv) {
            $contentDiv = $xpath->query("//div[contains(@class,'entry-content')]")->item(0);
            Log::info("Coolmate: trying fallback selector (entry-content only)");
        }

        Log::info("Coolmate entry-content node found: " . ($contentDiv ? 'yes' : 'no'));

        if (!$contentDiv) {
            Log::warning("Coolmate: entry-content not found with all fallbacks, skip post");
            return null;
        }

        // Lấy innerHTML của contentDiv (giống OnoffCrawlerService)
        $rawContentHtml = '';
        $dom = $contentDiv->ownerDocument;
        foreach ($contentDiv->childNodes as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE || $child->nodeType === XML_TEXT_NODE) {
                $rawContentHtml .= $dom->saveHTML($child);
            }
        }
        Log::info("Coolmate raw content HTML length: " . strlen($rawContentHtml));
        Log::info("Coolmate raw content HTML preview (first 500 chars): " . substr($rawContentHtml, 0, 500));

        if (empty($rawContentHtml) || trim(strip_tags($rawContentHtml)) === '') {
            Log::warning("Coolmate: empty content HTML after extraction, skip post");
            // Thử fallback: lấy toàn bộ innerHTML bằng cách khác
            $rawContentHtml = '';
            foreach ($contentDiv->childNodes as $child) {
                $rawContentHtml .= $dom->saveHTML($child);
            }
            if (empty($rawContentHtml) || trim(strip_tags($rawContentHtml)) === '') {
                Log::warning("Coolmate: fallback extraction also empty, skip post");
                return null;
            }
            Log::info("Coolmate: fallback extraction succeeded, length=" . strlen($rawContentHtml));
        }

        // Xử lý HTML: xóa class/attr, xử lý thẻ a/img, thay Coolmate → Nobi Fashion Việt Nam
        $processedHtml = $this->processHtml($rawContentHtml, $slug, $postUrl);
        Log::info("Coolmate processed HTML length: " . strlen($processedHtml));

        // Gọi AI viết lại content (giữ nguyên vị trí img)
        $maxContentLength = 30000;
        $contentForAI = $processedHtml;
        if (strlen($contentForAI) > $maxContentLength) {
            $contentForAI = substr($contentForAI, 0, $maxContentLength);
            $lastTagPos = strrpos($contentForAI, '>');
            if ($lastTagPos !== false) {
                $contentForAI = substr($contentForAI, 0, $lastTagPos + 1);
            }
        }

        Log::info("Coolmate: calling AI to rewrite content, length=" . strlen($contentForAI));
        $aiContent = $this->aiService->rewriteContentForRoutine($contentForAI);
        if (empty($aiContent)) {
            Log::warning("Coolmate: AI returned empty content, fallback to processed HTML");
            $aiContent = $processedHtml;
        } else {
            if (strlen($processedHtml) > $maxContentLength) {
                $remaining = substr($processedHtml, $maxContentLength);
                $firstTagPos = strpos($remaining, '>');
                if ($firstTagPos !== false) {
                    $remaining = substr($remaining, $firstTagPos + 1);
                    $aiContent .= "\n" . $remaining;
                }
            }
        }

        // Finalize content: div → p, xóa class/attr lần nữa, giữ HTML sạch
        $finalContent = $this->finalizeContent($aiContent);
        Log::info("Coolmate final content length: " . strlen($finalContent));

        // Guard: nếu finalizeContent làm rỗng thì fallback về processedHtml (đã sạch) để không mất content
        if (empty($finalContent) || trim(strip_tags($finalContent)) === '') {
            Log::warning("Coolmate: finalContent empty after finalizeContent, fallback to processedHtml");
            $finalContent = $this->finalizeContent($processedHtml);
            Log::info("Coolmate final content length (fallback): " . strlen($finalContent));
        }

        // 6. Meta keywords
        $metaKeywords = $this->aiService->generateMetaKeywords($seoTitle, $seoDescription);
        if (empty($metaKeywords)) {
            $metaKeywords = $this->generateFallbackKeywords($seoTitle);
        }

        // Tạo post
        $post = Post::create([
            'title' => $seoTitle,
            'slug' => $slug,
            'meta_title' => $seoTitle,
            'meta_description' => $seoDescription,
            'meta_keywords' => $metaKeywords,
            'meta_canonical' => $metaCanonical,
            'content' => $finalContent,
            'thumbnail' => $thumbnail['path'] ?? null,
            'thumbnail_alt_text' => $thumbnail['alt'] ?? $seoTitle,
            'status' => 'published',
            'published_at' => now(),
            'category_id' => 2,
            'created_by' => $this->userId,
            'account_id' => $this->userId,
        ]);

        return $post;
    }

    protected function extractThumbnail(DOMXPath $xpath, string $postUrl, string $slug, string $title): array
    {
        Log::info("=== COOLMATE: EXTRACT THUMBNAIL ===");
        $result = ['path' => null, 'alt' => $title];

        $metaImageNode = $xpath->query("//meta[@property='og:image']")->item(0);
        Log::info("Coolmate og:image node found: " . ($metaImageNode ? 'yes' : 'no'));

        if ($metaImageNode && $metaImageNode->hasAttribute('content')) {
            $imgUrl = trim($metaImageNode->getAttribute('content'));
            Log::info("Coolmate og:image URL: {$imgUrl}");

            if (!empty($imgUrl)) {
                $absoluteImgUrl = $this->makeAbsoluteUrl($imgUrl, $postUrl);
                $extension = $this->getImageExtension($absoluteImgUrl);
                $filename = $slug . '.' . $extension;
                $savePath = public_path('clients/assets/img/posts/' . $filename);

                $dir = dirname($savePath);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }

                if ($this->downloadImage($absoluteImgUrl, $savePath)) {
                    $result['path'] = 'clients/assets/img/posts/' . $filename;
                    $result['alt'] = $title;
                    Log::info("Coolmate thumbnail saved: {$result['path']}");
                }
            }
        }

        return $result;
    }

    /**
     * Xử lý HTML: xóa class/thuộc tính, thay Coolmate → Nobi Fashion Việt Nam, xử lý thẻ a/img
     */
    protected function processHtml(string $html, string $slug, string $postUrl = ''): string
    {
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        $xpath = new DOMXPath($dom);

        $allowedTags = ['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'ul', 'ol', 'li', 'strong', 'em', 'b', 'i', 'u', 'a', 'img', 'br', 'blockquote'];

        $allNodes = $xpath->query('//*');
        foreach ($allNodes as $node) {
            /** @var \DOMElement $node */
            if (!in_array(strtolower($node->nodeName), $allowedTags, true)) {
                continue;
            }

            $attributesToRemove = [];
            foreach ($node->attributes as $attr) {
                $attrName = $attr->nodeName;
                if (!in_array($attrName, ['href', 'src', 'alt', 'title'], true)) {
                    $attributesToRemove[] = $attrName;
                }
            }
            foreach ($attributesToRemove as $attrName) {
                $node->removeAttribute($attrName);
            }
        }

        // Xử lý thẻ a:
        // - Nếu text chứa Coolmate/coolmate/coolmate.me → href = nobifashion.vn, text = Nobi Fashion Việt Nam
        // - Ngược lại: href = random meta_canonical
        $linkNodes = $xpath->query('//a');
        foreach ($linkNodes as $linkNode) {
            /** @var \DOMElement $linkNode */
            $linkText = trim($linkNode->textContent);
            if (preg_match('/coolmate(\.me)?/i', $linkText)) {
                $linkNode->setAttribute('href', 'https://nobifashion.vn');
                $linkNode->nodeValue = 'Nobi Fashion Việt Nam';
            } else {
                $randomPost = Post::whereNotNull('meta_canonical')
                    ->inRandomOrder()
                    ->first();
                if ($randomPost && $randomPost->meta_canonical) {
                    $linkNode->setAttribute('href', $randomPost->meta_canonical);
                } else {
                    $linkNode->setAttribute('href', 'https://nobifashion.vn');
                }
            }
        }

        // Thay Coolmate/coolmate/coolmate.me trong text (không nằm trong <a>) → Nobi Fashion Việt Nam
        $textNodes = $xpath->query('//text()[not(ancestor::a)]');
        foreach ($textNodes as $textNode) {
            $text = $textNode->nodeValue;
            if (preg_match('/coolmate(\.me)?/i', $text)) {
                $newText = preg_replace('/coolmate(\.me)?/i', 'Nobi Fashion Việt Nam', $text);
                $textNode->nodeValue = $newText;
            }
        }

        // Xử lý thẻ img: tải ảnh về và thay src
        $imgNodes = $xpath->query('//img');
        foreach ($imgNodes as $imgNode) {
            /** @var \DOMElement $imgNode */
            $imgUrl = '';
            if ($imgNode->hasAttribute('src')) {
                $imgUrl = $imgNode->getAttribute('src');
            } elseif ($imgNode->hasAttribute('data-src')) {
                $imgUrl = $imgNode->getAttribute('data-src');
            }

            if (!empty($imgUrl) && !str_starts_with($imgUrl, 'data:')) {
                $absoluteImgUrl = $imgUrl;
                if (!filter_var($imgUrl, FILTER_VALIDATE_URL) && !empty($postUrl)) {
                    $absoluteImgUrl = $this->makeAbsoluteUrl($imgUrl, $postUrl);
                }

                if (filter_var($absoluteImgUrl, FILTER_VALIDATE_URL)) {
                    $extension = $this->getImageExtension($absoluteImgUrl);
                    $filename = $slug . '-' . uniqid() . '.' . $extension;
                    $savePath = public_path('clients/assets/img/posts/' . $filename);

                    $dir = dirname($savePath);
                    if (!is_dir($dir)) {
                        mkdir($dir, 0755, true);
                    }

                    if ($this->downloadImage($absoluteImgUrl, $savePath)) {
                        $imgNode->setAttribute('src', 'https://nobifashion.vn/clients/assets/img/posts/' . $filename);
                        if ($imgNode->hasAttribute('data-src')) {
                            $imgNode->removeAttribute('data-src');
                        }
                    }
                }
            }
        }

        $html = $dom->saveHTML();
        return $html;
    }

    /**
     * Finalize content: chuyển div thành p, xóa class/id/style/data-*, chỉ giữ HTML cơ bản sạch
     *
     * (Dùng same logic với Onoff, đã tối ưu để tránh rác & encoding lỗi)
     */
    protected function finalizeContent(string $html): string
    {
        $originalInput = $html;

        // 1) Remove wrappers / declarations
        $html = preg_replace('/<!DOCTYPE[^>]*>/i', '', $html);
        $html = preg_replace('/<\?xml[^>]*\?>/i', '', $html);
        $html = preg_replace('/<\/?html[^>]*>/i', '', $html);
        $html = preg_replace('/<\/?body[^>]*>/i', '', $html);

        // 2) Decode entities (đảm bảo tiếng Việt chuẩn)
        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // 3) Convert div -> p (đơn giản, vì content đã được AI trả HTML bài viết)
        $html = preg_replace('/<div\b[^>]*>/i', '<p>', $html);
        $html = preg_replace('/<\/div>/i', '</p>', $html);

        // 4) Unwrap span (xóa span nhưng giữ text)
        $html = preg_replace('/<span\b[^>]*>/i', '', $html);
        $html = preg_replace('/<\/span>/i', '', $html);

        // 5) Chuẩn hóa <a>: chỉ giữ href/title; nếu thiếu href thì set https://nobifashion.vn
        $html = preg_replace_callback('/<a\b([^>]*)>/i', function ($m) {
            $attrs = $m[1] ?? '';
            $href = '';
            $title = '';
            if (preg_match('/\bhref\s*=\s*("([^"]*)"|\'([^\']*)\')/i', $attrs, $mm)) {
                $href = $mm[2] !== '' ? $mm[2] : ($mm[3] ?? '');
            }
            if (preg_match('/\btitle\s*=\s*("([^"]*)"|\'([^\']*)\')/i', $attrs, $mm)) {
                $title = $mm[2] !== '' ? $mm[2] : ($mm[3] ?? '');
            }
            if (trim($href) === '') {
                $href = 'https://nobifashion.vn';
            }
            $out = '<a href="' . htmlspecialchars($href, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '"';
            if (trim($title) !== '') {
                $out .= ' title="' . htmlspecialchars($title, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '"';
            }
            $out .= '>';
            return $out;
        }, $html);

        // 6) Chuẩn hóa <img>: chỉ giữ src/alt/title
        // - Nếu src rỗng hoặc là data:* (base64 placeholder) thì XÓA hẳn img
        $html = preg_replace_callback('/<img\b([^>]*?)(\/?)>/i', function ($m) {
            $attrs = $m[1] ?? '';
            $src = '';
            $alt = '';
            $title = '';
            if (preg_match('/\bsrc\s*=\s*("([^"]*)"|\'([^\']*)\')/i', $attrs, $mm)) {
                $src = $mm[2] !== '' ? $mm[2] : ($mm[3] ?? '');
            }
            if (preg_match('/\balt\s*=\s*("([^"]*)"|\'([^\']*)\')/i', $attrs, $mm)) {
                $alt = $mm[2] !== '' ? $mm[2] : ($mm[3] ?? '');
            }
            if (preg_match('/\btitle\s*=\s*("([^"]*)"|\'([^\']*)\')/i', $attrs, $mm)) {
                $title = $mm[2] !== '' ? $mm[2] : ($mm[3] ?? '');
            }
            $src = trim($src);
            if ($src === '' || str_starts_with(strtolower($src), 'data:')) {
                return '';
            }
            $out = '<img src="' . htmlspecialchars($src, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '"';
            if (trim($alt) !== '') {
                $out .= ' alt="' . htmlspecialchars($alt, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '"';
            }
            if (trim($title) !== '') {
                $out .= ' title="' . htmlspecialchars($title, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '"';
            }
            $out .= '>';
            return $out;
        }, $html);

        // 7) Xóa attributes còn lại trên các tag khác (class/id/style/data-*...)
        $html = preg_replace('/<(?!a\b|img\b)([a-z0-9]+)\s+[^>]*?>/i', '<$1>', $html);

        // 8) Chỉ giữ các tag thông dụng
        $allowed = '(p|h1|h2|h3|h4|h5|h6|ul|ol|li|strong|em|b|i|u|a|img|br|blockquote)';
        $html = preg_replace('/<(\/?)(?!' . $allowed . '\b)[a-z0-9]+\b[^>]*>/i', '', $html);

        // 9) Cleanup whitespace
        $html = preg_replace('/\s+/', ' ', $html);
        $html = str_replace('> <', '><', $html);
        $html = trim($html);

        // Guard cuối: nếu vẫn rỗng thì trả về processed html gốc (decode tối thiểu) thay vì rỗng
        if ($html === '' && trim(strip_tags($originalInput)) !== '') {
            $fallback = html_entity_decode($originalInput, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $fallback = preg_replace('/<!DOCTYPE[^>]*>/i', '', $fallback);
            $fallback = preg_replace('/<\?xml[^>]*\?>/i', '', $fallback);
            $fallback = preg_replace('/<\/?html[^>]*>/i', '', $fallback);
            $fallback = preg_replace('/<\/?body[^>]*>/i', '', $fallback);
            $fallback = preg_replace('/\s+/', ' ', $fallback);
            $fallback = str_replace('> <', '><', $fallback);
            return trim($fallback);
        }

        return $html;
    }

    protected function makeAbsoluteUrl(string $relativeUrl, string $baseUrl): string
    {
        if (filter_var($relativeUrl, FILTER_VALIDATE_URL)) {
            return $relativeUrl;
        }
        $parsedBase = parse_url($baseUrl);
        $scheme = $parsedBase['scheme'] ?? 'https';
        $host = $parsedBase['host'] ?? '';

        if (str_starts_with($relativeUrl, '//')) {
            return $scheme . ':' . $relativeUrl;
        }
        if (str_starts_with($relativeUrl, '/')) {
            return $scheme . '://' . $host . $relativeUrl;
        }
        $basePath = dirname($parsedBase['path'] ?? '/');
        return $scheme . '://' . $host . $basePath . '/' . ltrim($relativeUrl, '/');
    }

    protected function getImageExtension(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        return $extension ? strtolower($extension) : 'jpg';
    }

    protected function downloadImage(string $url, string $savePath): bool
    {
        try {
            $response = Http::timeout(60)->get($url);
            if ($response->successful()) {
                file_put_contents($savePath, $response->body());
                Log::info("Coolmate image downloaded: {$savePath}");
                return true;
            }
            Log::warning("Coolmate image download failed: {$url}, status=" . $response->status());
        } catch (\Throwable $e) {
            Log::error("Coolmate image download exception: {$url}", ['error' => $e->getMessage()]);
        }
        return false;
    }

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

    protected function generateFallbackKeywords(string $title): string
    {
        $words = preg_split('/\s+/', $title);
        $keywords = array_slice($words, 0, 10);
        return implode(', ', $keywords);
    }

    /**
     * Fetch HTML using Playwright service (đảm bảo JS render đủ)
     */
    protected function fetchHtml(string $url, ?string $waitForSelector = null): string
    {
        Log::info("=== COOLMATE: FETCH HTML WITH PLAYWRIGHT SERVICE ===");
        Log::info("URL: {$url}");
        if ($waitForSelector) {
            Log::info("Wait for selector: {$waitForSelector}");
        }

        try {
            $playwrightServiceUrl = env('PLAYWRIGHT_SERVICE_URL', 'http://localhost:3001');

            Log::info("Calling Playwright service: {$playwrightServiceUrl}/crawl");

            $payload = ['url' => $url];
            if ($waitForSelector) {
                $payload['waitForSelector'] = $waitForSelector;
            }

            $response = Http::timeout(120)
                ->post("{$playwrightServiceUrl}/crawl", $payload);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['success']) && $data['success'] === true && isset($data['html'])) {
                    $html = $data['html'];
                    Log::info("Coolmate Playwright response body length: " . strlen($html));
                    Log::info("Coolmate Playwright response body preview (first 500 chars): " . substr($html, 0, 500));

                    if (strpos($html, 'Just a moment') !== false || strpos($html, 'cf-browser-verification') !== false) {
                        Log::warning("Coolmate: Cloudflare challenge still present in response");
                    }
                    return $html;
                } else {
                    Log::error("Coolmate Playwright service returned an error or invalid data", [
                        'response_body' => $response->body(),
                    ]);
                    throw new \Exception("Coolmate Playwright service returned an error: " . ($data['error'] ?? 'Unknown error'));
                }
            } else {
                Log::error("Coolmate Playwright service HTTP error", [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \Exception("Coolmate Playwright service HTTP error: " . $response->status());
            }
        } catch (\Exception $e) {
            Log::critical('COOLMATE PLAYWRIGHT SERVICE FAILED', [
                'url' => $url,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new \Exception("Failed to fetch Coolmate URL with Playwright service: " . $e->getMessage());
        }
    }
}

