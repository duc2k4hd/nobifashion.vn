<?php

namespace App\Services;

use App\Models\Post;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use DOMDocument;
use DOMXPath;

class OnoffCrawlerService
{
    protected AIService $aiService;
    protected int $userId;

    public function __construct(AIService $aiService)
    {
        $this->aiService = $aiService;
        $this->userId = auth('web')->id() ?? 1;
    }

    /**
     * Crawl danh sách category URLs
     */
    public function crawlCategories(array $categoryUrls): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'posts' => [],
            'errors' => [],
        ];

        Log::info('=== ONOFF CRAWLER START ===');
        Log::info('Total category URLs: ' . count($categoryUrls));

        foreach ($categoryUrls as $categoryUrl) {
            try {
                Log::info("Processing category URL: {$categoryUrl}");
                $postUrls = $this->extractPostUrlsFromCategory($categoryUrl);
                Log::info("Found " . count($postUrls) . " post URLs from category");
                
                if (empty($postUrls)) {
                    $results['errors'][] = "Không tìm thấy bài viết nào trong danh mục: {$categoryUrl}";
                    Log::warning("No posts found in category: {$categoryUrl}");
                }
                
                foreach ($postUrls as $postUrl) {
                    try {
                        Log::info("Crawling post URL: {$postUrl}");
                        $post = $this->crawlPost($postUrl);
                        if ($post) {
                            $results['success']++;
                            $results['posts'][] = [
                                'id' => $post->id,
                                'title' => $post->title,
                                'slug' => $post->slug,
                            ];
                            Log::info("Successfully crawled post: {$post->title} (ID: {$post->id})");
                        } else {
                            $results['failed']++;
                            $results['errors'][] = "Không thể crawl bài viết: {$postUrl}";
                            Log::warning("Failed to crawl post: {$postUrl}");
                        }
                    } catch (\Exception $e) {
                        $results['failed']++;
                        $results['errors'][] = "Lỗi khi crawl {$postUrl}: " . $e->getMessage();
                        Log::error("Error crawling post: {$postUrl}", [
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                    }
                }
            } catch (\Exception $e) {
                $results['errors'][] = "Lỗi khi crawl danh mục {$categoryUrl}: " . $e->getMessage();
                Log::error("Error crawling category: {$categoryUrl}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        Log::info("=== ONOFF CRAWLER END === Success: {$results['success']}, Failed: {$results['failed']}");
        return $results;
    }

    /**
     * Extract post URLs from category page
     */
    protected function extractPostUrlsFromCategory(string $categoryUrl): array
    {
        Log::info("=== EXTRACT POST URLS FROM CATEGORY ===");
        Log::info("Category URL: {$categoryUrl}");
        
        // Đợi selector bài list để tránh HTML chưa render xong
        $html = $this->fetchHtml($categoryUrl, 'article.post.type-post, div.elementor-element-91e2450');
        Log::info("HTML length: " . strlen($html));
        
        if (empty($html)) {
            Log::warning("Empty HTML returned from category URL");
            return [];
        }

        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        $xpath = new DOMXPath($dom);

        $postUrls = [];

        // Tìm div có class chứa "elementor-element elementor-element-91e2450 e-flex e-con-boxed e-con e-parent e-lazyloaded"
        $containerNodes = $xpath->query("//div[contains(@class, 'elementor-element') and contains(@class, 'elementor-element-91e2450') and contains(@class, 'e-flex') and contains(@class, 'e-con-boxed') and contains(@class, 'e-con') and contains(@class, 'e-parent') and contains(@class, 'e-lazyloaded')]");
        Log::info("Found " . $containerNodes->length . " container nodes");
        
        if ($containerNodes->length > 0) {
            foreach ($containerNodes as $containerIndex => $container) {
                Log::info("Processing container #{$containerIndex}");
                
                // Tìm tất cả article có class chứa "post type-post"
                $articleNodes = $xpath->query(".//article[contains(@class, 'post') and contains(@class, 'type-post')]", $container);
                Log::info("Found " . $articleNodes->length . " article nodes (post type-post) in container #{$containerIndex}");
                
                foreach ($articleNodes as $articleIndex => $article) {
                    Log::info("Processing article #{$articleIndex}");
                    
                    // Tìm div có class chứa "type-post"
                    $typePostDiv = $xpath->query(".//div[contains(@class, 'type-post')]", $article)->item(0);
                    
                    if ($typePostDiv) {
                        // Tìm thẻ a đầu tiên trong div type-post
                        $linkNodes = $xpath->query(".//a", $typePostDiv);
                        Log::info("Found " . $linkNodes->length . " link nodes in type-post div");
                        
                        if ($linkNodes->length > 0) {
                            $linkNode = $linkNodes->item(0);
                            
                            if ($linkNode && $linkNode->hasAttribute('href')) {
                                $href = $linkNode->getAttribute('href');
                                Log::info("Found href: {$href}");
                                
                                if (!empty($href)) {
                                    $absoluteUrl = $this->makeAbsoluteUrl($href, $categoryUrl);
                                    $postUrls[] = $absoluteUrl;
                                    Log::info("Added post URL: {$absoluteUrl}");
                                }
                            }
                        }
                    } else {
                        // Thử tìm link trực tiếp trong article
                        $directLinks = $xpath->query(".//a", $article);
                        Log::info("Trying direct links in article: " . $directLinks->length);
                        
                        if ($directLinks->length > 0) {
                            foreach ($directLinks as $linkNode) {
                                if ($linkNode->hasAttribute('href')) {
                                    $href = $linkNode->getAttribute('href');
                                    // Chỉ lấy link có chứa /blog/ để đảm bảo là link bài viết
                                    if (strpos($href, '/blog/') !== false) {
                                        $absoluteUrl = $this->makeAbsoluteUrl($href, $categoryUrl);
                                        $postUrls[] = $absoluteUrl;
                                        Log::info("Added post URL (direct link): {$absoluteUrl}");
                                        break; // Chỉ lấy link đầu tiên
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } else {
            Log::warning("No container nodes found");
            
            // Fallback: Tìm tất cả article có class chứa "post type-post"
            $allArticles = $xpath->query("//article[contains(@class, 'post') and contains(@class, 'type-post')]");
            Log::info("Found " . $allArticles->length . " articles (fallback)");
            
            foreach ($allArticles as $article) {
                $linkNodes = $xpath->query(".//a[contains(@href, '/blog/')]", $article);
                if ($linkNodes->length > 0) {
                    $linkNode = $linkNodes->item(0);
                    if ($linkNode->hasAttribute('href')) {
                        $href = $linkNode->getAttribute('href');
                        $absoluteUrl = $this->makeAbsoluteUrl($href, $categoryUrl);
                        $postUrls[] = $absoluteUrl;
                        Log::info("Added post URL (fallback): {$absoluteUrl}");
                    }
                }
            }
        }

        // Loại bỏ duplicate
        $postUrls = array_unique($postUrls);
        Log::info("Total unique post URLs found: " . count($postUrls));

        return array_values($postUrls);
    }

    /**
     * Crawl một bài viết
     */
    public function crawlPost(string $postUrl): ?Post
    {
        // Tăng thời gian thực thi để xử lý content dài
        ini_set('max_execution_time', 600); // 10 phút
        set_time_limit(600); // 10 phút
        
        Log::info("=== CRAWL POST ===");
        Log::info("Post URL: {$postUrl}");
        
        // Đợi đúng widget content để tránh trường hợp Playwright trả HTML trước khi Elementor render
        $html = $this->fetchHtml($postUrl, 'div.elementor-widget-neuron-post-content, div.elementor-widget-theme-post-content');
        Log::info("HTML length: " . strlen($html));
        
        if (empty($html)) {
            Log::warning("Empty HTML returned from post URL");
            return null;
        }

        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        $xpath = new DOMXPath($dom);

        // 1. Lấy title từ h1 class="elementor-heading-title elementor-size-default"
        $titleNode = $xpath->query("//h1[contains(@class, 'elementor-heading-title') and contains(@class, 'elementor-size-default')]")->item(0);
        Log::info("Title node found: " . ($titleNode ? 'yes' : 'no'));
        
        $originalTitle = $titleNode ? trim($titleNode->textContent) : '';
        Log::info("Original title: " . ($originalTitle ?: 'EMPTY'));

        if (empty($originalTitle)) {
            Log::warning("Empty title, skipping post");
            return null;
        }

        // Bỏ qua bài viết nếu tiêu đề chứa "ONOFF", "onoff" hoặc "onoff.vn"
        if (preg_match('/\b(ONOFF|onoff|onoff\.vn)\b/i', $originalTitle)) {
            Log::info("Title contains ONOFF/onoff/onoff.vn, skipping post: {$originalTitle}");
            return null;
        }

        // Dùng AI viết lại title
        $seoTitle = $this->aiService->rewriteTitle($originalTitle);
        if (empty($seoTitle)) {
            $seoTitle = $originalTitle;
        }
        // Thay thế các năm 2010-2026 thành [NOBI]currentyear[NOBI]
        $seoTitle = replaceYearsWithPlaceholder($seoTitle);

        // 2. Lấy meta description
        $metaDescNode = $xpath->query("//meta[@property='og:description']")->item(0);
        Log::info("Meta description node found: " . ($metaDescNode ? 'yes' : 'no'));
        
        $originalDescription = $metaDescNode ? trim($metaDescNode->getAttribute('content')) : '';
        Log::info("Original description: " . ($originalDescription ? Str::limit($originalDescription, 100) : 'EMPTY'));

        // Dùng AI viết lại description
        $seoDescription = '';
        if (!empty($originalDescription)) {
            $seoDescription = $this->aiService->rewriteDescription($originalDescription);
        }
        if (empty($seoDescription)) {
            $seoDescription = Str::limit(strip_tags($seoTitle), 200);
        }
        // Thay thế các năm 2010-2026 thành [NOBI]currentyear[NOBI]
        $seoDescription = replaceYearsWithPlaceholder($seoDescription);
        Log::info("SEO description: " . Str::limit($seoDescription, 100));

        // 3. Tạo slug trước (cần cho thumbnail và content)
        // Loại bỏ "Nobi Fashion Việt Nam" và các biến thể khỏi title trước khi tạo slug
        $titleForSlug = $seoTitle;
        // Slug KHÔNG được chứa năm: bỏ placeholder + bỏ mọi năm 4 chữ số (2010-2030)
        $titleForSlug = str_replace('[NOBI]currentyear[NOBI]', '', $titleForSlug);
        $titleForSlug = preg_replace('/\b(201[0-9]|202[0-9]|2030)\b/u', '', $titleForSlug);
        $titleForSlug = preg_replace('/\bNobi\s+Fashion\s+Vi[ệe]t\s+Nam\b/ui', '', $titleForSlug);
        $titleForSlug = preg_replace('/\bNobi\s+Fashion\b/ui', '', $titleForSlug);
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

        // 4. Lấy hình ảnh từ meta property="og:image"
        $thumbnail = $this->extractThumbnail($xpath, $postUrl, $slug, $seoTitle);

        // 5. Lấy content
        $contentHtml = $this->extractContent($xpath);
        Log::info("Content HTML length: " . strlen($contentHtml));
        
        if (empty($contentHtml)) {
            Log::warning("Empty content, skipping post");
            return null;
        }

        // Xử lý HTML: xóa class, thay thẻ a, xử lý img, thay ONOFF
        Log::info("Processing HTML...");
        $startTime = microtime(true);
        $processedHtml = $this->processHtml($contentHtml, $slug, $postUrl);
        Log::info("HTML processed in " . round(microtime(true) - $startTime, 2) . " seconds");

        // Dùng AI viết lại content
        // Giới hạn độ dài content để tránh timeout (30000 ký tự)
        $maxContentLength = 30000;
        $contentForAI = $processedHtml;
        if (strlen($contentForAI) > $maxContentLength) {
            Log::warning("Content too long (" . strlen($contentForAI) . " chars), truncating to {$maxContentLength} chars for AI");
            $contentForAI = substr($contentForAI, 0, $maxContentLength);
            // Cố gắng cắt ở thẻ đóng gần nhất để không làm hỏng HTML
            $lastTagPos = strrpos($contentForAI, '>');
            if ($lastTagPos !== false) {
                $contentForAI = substr($contentForAI, 0, $lastTagPos + 1);
            }
        }
        
        Log::info("Calling AI to rewrite content (length: " . strlen($contentForAI) . " chars)...");
        $startTime = microtime(true);
        $aiContent = $this->aiService->rewriteContentForRoutine($contentForAI);
        Log::info("AI rewrite completed in " . round(microtime(true) - $startTime, 2) . " seconds");
        if (empty($aiContent)) {
            Log::warning("AI returned empty content, using processed HTML");
            $aiContent = $processedHtml;
        } else {
            // Nếu content bị cắt, thêm phần còn lại vào
            if (strlen($processedHtml) > $maxContentLength) {
                $remainingContent = substr($processedHtml, $maxContentLength);
                // Tìm thẻ đóng đầu tiên để bắt đầu từ đó
                $firstTagPos = strpos($remainingContent, '>');
                if ($firstTagPos !== false) {
                    $remainingContent = substr($remainingContent, $firstTagPos + 1);
                    // Thêm phần còn lại vào cuối
                    $aiContent .= "\n" . $remainingContent;
                }
            }
        }

        // Xử lý lại: chuyển div thành p, xóa class
        $finalContent = $this->finalizeContent($aiContent);
        
        // Thay thế các năm 2010-2026 thành [NOBI]currentyear[NOBI] trong content
        $finalContent = replaceYearsWithPlaceholder($finalContent);

        // Tạo meta keywords
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

    /**
     * Extract thumbnail từ meta og:image
     */
    protected function extractThumbnail(DOMXPath $xpath, string $postUrl, string $slug, string $title): array
    {
        Log::info("=== EXTRACT THUMBNAIL ===");
        $result = ['path' => null, 'alt' => $title];

        // Tìm meta property="og:image"
        $metaImageNode = $xpath->query("//meta[@property='og:image']")->item(0);
        Log::info("Meta og:image node found: " . ($metaImageNode ? 'yes' : 'no'));
        
        if ($metaImageNode && $metaImageNode->hasAttribute('content')) {
            $imgUrl = trim($metaImageNode->getAttribute('content'));
            Log::info("Found og:image URL: {$imgUrl}");
            
            if (!empty($imgUrl)) {
                $absoluteImgUrl = $this->makeAbsoluteUrl($imgUrl, $postUrl);
                Log::info("Absolute img URL: {$absoluteImgUrl}");
                
                // Tải ảnh về
                $extension = $this->getImageExtension($absoluteImgUrl);
                $filename = $slug . '.' . $extension;
                $savePath = public_path('clients/assets/img/posts/' . $filename);
                
                // Tạo thư mục nếu chưa có
                $dir = dirname($savePath);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                
                if ($this->downloadImage($absoluteImgUrl, $savePath)) {
                    $result['path'] = 'clients/assets/img/posts/' . $filename;
                    $result['alt'] = $title;
                    Log::info("Thumbnail saved: {$result['path']}");
                } else {
                    Log::warning("Failed to download thumbnail: {$absoluteImgUrl}");
                }
            }
        } else {
            Log::warning("No og:image meta tag found");
        }

        return $result;
    }

    /**
     * Extract content từ div class="elementor-widget-neuron-post-content"
     */
    protected function extractContent(DOMXPath $xpath): string
    {
        Log::info("=== EXTRACT CONTENT ===");
        
        // Tìm div có class chứa "elementor-widget-neuron-post-content"
        $contentDiv = $xpath->query("//div[contains(@class, 'elementor-widget-neuron-post-content')]")->item(0);
        Log::info("Content div found: " . ($contentDiv ? 'yes' : 'no'));
        
        if (!$contentDiv) {
            // Fallback: một số site Elementor dùng widget theme-post-content
            $contentDiv = $xpath->query("//div[contains(@class, 'elementor-widget-theme-post-content')]")->item(0);
            Log::info("Fallback theme-post-content div found: " . ($contentDiv ? 'yes' : 'no'));
        }

        if (!$contentDiv) {
            // Fallback cuối: cố gắng lấy content trong article (giữ HTML thô để xử lý sau)
            $article = $xpath->query("//article")->item(0);
            Log::info("Fallback article found: " . ($article ? 'yes' : 'no'));
            if (!$article) {
                Log::warning("Content container not found (neuron/theme/article)");
                return '';
            }
            $dom = $article->ownerDocument;
            $html = '';
            foreach ($article->childNodes as $node) {
                $html .= $dom->saveHTML($node);
            }
            return $html;
        }

        // Lấy innerHTML của div
        $html = '';
        foreach ($contentDiv->childNodes as $node) {
            $dom = $contentDiv->ownerDocument;
            $html .= $dom->saveHTML($node);
        }

        return $html;
    }

    /**
     * Process HTML: xóa class, thay thẻ a, xử lý img, thay ONOFF
     */
    protected function processHtml(string $html, string $slug, string $postUrl = ''): string
    {
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        $xpath = new DOMXPath($dom);

        // Xóa class và các thuộc tính không cần thiết
        $allowedTags = ['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'ul', 'ol', 'li', 'strong', 'em', 'b', 'i', 'u', 'a', 'img', 'br', 'blockquote'];
        
        $allNodes = $xpath->query('//*');
        foreach ($allNodes as $node) {
            if (!in_array(strtolower($node->nodeName), $allowedTags)) {
                continue;
            }

            // Xóa class và các thuộc tính không cần thiết
            $attributesToRemove = [];
            foreach ($node->attributes as $attr) {
                $attrName = $attr->nodeName;
                if (!in_array($attrName, ['href', 'src', 'alt', 'title'])) {
                    $attributesToRemove[] = $attrName;
                }
            }
            foreach ($attributesToRemove as $attrName) {
                $node->removeAttribute($attrName);
            }
        }

        // Xử lý thẻ a: nếu không chứa ONOFF/onoff/onoff.vn thì thay href = random meta_canonical
        $linkNodes = $xpath->query('//a');
        foreach ($linkNodes as $linkNode) {
            $linkText = $linkNode->textContent;
            if (!preg_match('/\b(ONOFF|onoff|onoff\.vn)\b/i', $linkText)) {
                // Lấy random meta_canonical từ posts
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

        // Xử lý thẻ img: tải về và thay src
        $imgNodes = $xpath->query('//img');
        foreach ($imgNodes as $imgNode) {
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

        // Xử lý ONOFF/onoff/onoff.vn: nếu trong thẻ a thì thay href, nếu không thì chỉ thay text
        $linkNodes = $xpath->query('//a');
        foreach ($linkNodes as $linkNode) {
            $linkText = $linkNode->textContent;
            if (preg_match('/\b(ONOFF|onoff|onoff\.vn)\b/i', $linkText)) {
                // Nếu trong thẻ a: thay href và text
                $linkNode->setAttribute('href', 'https://nobifashion.vn');
                // Thay text trong thẻ a
                $linkNode->nodeValue = 'Nobi Fashion Việt Nam';
            }
        }
        
        // Thay ONOFF/onoff/onoff.vn thành Nobi Fashion Việt Nam (chỉ text, không trong thẻ a)
        // Tìm tất cả text nodes không nằm trong thẻ a
        $textNodes = $xpath->query('//text()[not(ancestor::a)]');
        foreach ($textNodes as $textNode) {
            $text = $textNode->nodeValue;
            if (preg_match('/\b(ONOFF|onoff|onoff\.vn)\b/i', $text)) {
                $newText = preg_replace('/\b(ONOFF|onoff|onoff\.vn)\b/i', 'Nobi Fashion Việt Nam', $text);
                $textNode->nodeValue = $newText;
            }
        }
        
        $html = $dom->saveHTML();
        return $html;
    }

    /**
     * Finalize content: chuyển div thành p, xóa class
     */
    protected function finalizeContent(string $html): string
    {
        $originalInput = $html;
        // Xóa XML declaration nếu có trong input (xử lý triệt để)
        $html = preg_replace('/<\?xml[^>]*\?>/i', '', $html);
        $html = preg_replace('/<\?xml\s+encoding[^>]*\?>/i', '', $html);
        $html = preg_replace('/<\?xml\s+encoding\s*=\s*["\']UTF-8["\'][^>]*\?>/i', '', $html);
        $html = preg_replace('/<\?xml\s+encoding\s*=\s*["\']utf-8["\'][^>]*\?>/i', '', $html);
        $html = preg_replace('/<\?xml[^>]*>/i', '', $html);
        $html = trim($html);
        
        $dom = new DOMDocument();
        // Ép charset UTF-8 để tránh lỗi mojibake (MÃ¹a Ä...)
        $utf8Meta = '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
        @$dom->loadHTML($utf8Meta . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $xpath = new DOMXPath($dom);

        // Xóa các "anchor" linh tinh của Elementor (menu anchor / toc anchor)
        $anchorNodes = $xpath->query("//span[contains(concat(' ', normalize-space(@class), ' '), ' elementor-menu-anchor ') or starts-with(@id, 'm-neuron-toc__heading-anchor')]");
        $anchorsToRemove = [];
        foreach ($anchorNodes as $n) {
            $anchorsToRemove[] = $n;
        }
        foreach ($anchorsToRemove as $n) {
            if ($n->parentNode) {
                $n->parentNode->removeChild($n);
            }
        }

        // Chuyển TẤT CẢ div thành p
        $divNodes = $xpath->query('//div');
        $divsToProcess = [];
        foreach ($divNodes as $divNode) {
            $divsToProcess[] = $divNode;
        }
        
        foreach ($divsToProcess as $divNode) {
            // Kiểm tra xem div có chứa block-level elements (h1-h6, ul, ol) không
            $hasBlockChildren = false;
            foreach ($divNode->childNodes as $child) {
                if ($child->nodeType === XML_ELEMENT_NODE) {
                    $tagName = strtolower($child->nodeName);
                    if (in_array($tagName, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'ul', 'ol'])) {
                        $hasBlockChildren = true;
                        break;
                    }
                }
            }

            if (!$hasBlockChildren) {
                // Nếu không có block children, chuyển div thành p
                $pNode = $dom->createElement('p');
                foreach ($divNode->childNodes as $child) {
                    $pNode->appendChild($child->cloneNode(true));
                }
                if ($divNode->parentNode) {
                    $divNode->parentNode->replaceChild($pNode, $divNode);
                }
            } else {
                // Nếu có block children, unwrap div (giữ nguyên children, xóa div)
                $parent = $divNode->parentNode;
                if ($parent) {
                    while ($divNode->firstChild) {
                        $parent->insertBefore($divNode->firstChild, $divNode);
                    }
                    $parent->removeChild($divNode);
                }
            }
        }
        
        // Xóa các thẻ không cơ bản, chỉ giữ: p, h1-h6, ul, ol, li, strong, em, b, i, u, a, img, br, blockquote, span
        $allowedTags = ['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'ul', 'ol', 'li', 'strong', 'em', 'b', 'i', 'u', 'a', 'img', 'br', 'blockquote', 'span'];
        $allNodes = $xpath->query('//*');
        $nodesToRemove = [];
        foreach ($allNodes as $node) {
            if (!in_array(strtolower($node->nodeName), $allowedTags)) {
                $nodesToRemove[] = $node;
            }
        }
        
        foreach ($nodesToRemove as $node) {
            // Unwrap node: chuyển children vào parent và xóa node
            $parent = $node->parentNode;
            if ($parent) {
                while ($node->firstChild) {
                    $parent->insertBefore($node->firstChild, $node);
                }
                $parent->removeChild($node);
            }
        }

        // Xóa tất cả attributes không cần thiết (CHẶT): chỉ giữ href/title cho <a>, src/alt/title cho <img>
        $allNodes = $xpath->query('//*');
        foreach ($allNodes as $node) {
            $tag = strtolower($node->nodeName);
            $keep = [];
            if ($tag === 'a') {
                $keep = ['href', 'title'];
            } elseif ($tag === 'img') {
                $keep = ['src', 'alt', 'title'];
            }

            $toRemove = [];
            if ($node->hasAttributes()) {
                foreach ($node->attributes as $attr) {
                    $attrName = strtolower($attr->nodeName);
                    if (!in_array($attrName, $keep, true)) {
                        $toRemove[] = $attr->nodeName;
                    }
                }
            }
            foreach ($toRemove as $attrName) {
                $node->removeAttribute($attrName);
            }
        }
        
        // Xóa tất cả thẻ span rỗng
        $spanNodes = $xpath->query('//span');
        $spansToRemove = [];
        foreach ($spanNodes as $spanNode) {
            $textContent = trim($spanNode->textContent);
            if (empty($textContent)) {
                $spansToRemove[] = $spanNode;
            }
        }
        foreach ($spansToRemove as $spanNode) {
            $parent = $spanNode->parentNode;
            if ($parent) {
                $parent->removeChild($spanNode);
            }
        }
        
        // Unwrap các thẻ span còn lại (chuyển children vào parent)
        $spanNodes = $xpath->query('//span');
        $spansToUnwrap = [];
        foreach ($spanNodes as $spanNode) {
            $spansToUnwrap[] = $spanNode;
        }
        foreach ($spansToUnwrap as $spanNode) {
            $parent = $spanNode->parentNode;
            if ($parent) {
                while ($spanNode->firstChild) {
                    $parent->insertBefore($spanNode->firstChild, $spanNode);
                }
                $parent->removeChild($spanNode);
            }
        }
        
        // Sửa lỗi <p> lồng trong <p>: unwrap p bên trong
        $pNodes = $xpath->query('//p//p');
        $pToUnwrap = [];
        foreach ($pNodes as $pNode) {
            $pToUnwrap[] = $pNode;
        }
        foreach ($pToUnwrap as $pNode) {
            $parent = $pNode->parentNode;
            if ($parent && strtolower($parent->nodeName) === 'p') {
                while ($pNode->firstChild) {
                    $parent->insertBefore($pNode->firstChild, $pNode);
                }
                $parent->removeChild($pNode);
            }
        }

        // Lấy innerHTML của body
        $body = $dom->getElementsByTagName('body')->item(0);
        if ($body) {
            $html = '';
            foreach ($body->childNodes as $node) {
                $html .= $dom->saveHTML($node);
            }
        } else {
            $html = $dom->saveHTML();
        }
        
        // Loại bỏ DOCTYPE và XML declaration (xử lý triệt để)
        $html = preg_replace('/<!DOCTYPE[^>]*>/i', '', $html);
        $html = preg_replace('/<\?xml[^>]*\?>/i', '', $html);
        $html = preg_replace('/<\?xml\s+encoding[^>]*\?>/i', '', $html);
        $html = preg_replace('/<\?xml\s+encoding\s*=\s*["\']UTF-8["\'][^>]*\?>/i', '', $html);
        $html = preg_replace('/<\?xml\s+encoding\s*=\s*["\']utf-8["\'][^>]*\?>/i', '', $html);
        $html = preg_replace('/<\?xml[^>]*>/i', '', $html);
        $html = preg_replace('/<\/?html[^>]*>/i', '', $html);
        $html = preg_replace('/<\/?body[^>]*>/i', '', $html);

        // Chắc chắn không còn style/class/id/data-* (phòng trường hợp HTML bị "lọt" qua DOM)
        $html = preg_replace('/\s+(class|id|style|data-[a-z0-9\-\_:]+)\s*=\s*"[^"]*"/i', '', $html);
        $html = preg_replace("/\s+(class|id|style|data-[a-z0-9\-\_:]+)\s*=\s*'[^']*'/i", '', $html);
        
        // Xóa các thẻ span rỗng còn sót lại (sau khi saveHTML)
        $html = preg_replace('/<span[^>]*>\s*<\/span>/i', '', $html);
        $html = preg_replace('/<span[^>]*><\/span>/i', '', $html);
        
        // Sửa lỗi HTML bị lỗi (ví dụ: <span style=" color:>)
        $html = preg_replace('/<span[^>]*style\s*=\s*["\'][^"\']*["\']\s*>/i', '<span>', $html);
        $html = preg_replace('/<span[^>]*style\s*=\s*[^>]*>/i', '<span>', $html);
        
        // Decode HTML entities về UTF-8
        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Fix mojibake: UTF-8 bị đọc như ISO-8859-1/Windows-1252 (dạng "MÃ¹a Ä...")
        if (preg_match('/[ÃÂ][^\s]{1,2}/u', $html)) {
            // thử ISO-8859-1 -> UTF-8
            $fixedIso = @mb_convert_encoding($html, 'UTF-8', 'ISO-8859-1');
            // thử Windows-1252 -> UTF-8
            $fixedWin = @mb_convert_encoding($html, 'UTF-8', 'Windows-1252');

            // chọn bản có ít ký tự mojibake hơn
            $countBad = fn(string $s) => preg_match_all('/[ÃÂ][^\s]{1,2}/u', $s, $m);
            $best = $html;
            if (is_string($fixedIso) && $countBad($fixedIso) < $countBad($best)) {
                $best = $fixedIso;
            }
            if (is_string($fixedWin) && $countBad($fixedWin) < $countBad($best)) {
                $best = $fixedWin;
            }
            $html = $best;
        }
        
        // Xử lý phần "Xem thêm" bị trùng lặp: nếu có text "Xem thêm:" + text + link với cùng text, chỉ giữ link
        // Pattern: "Xem thêm:" + text + <a>text</a> (cùng text) -> chỉ giữ <a>Xem thêm: text</a>
        $html = preg_replace_callback(
            '/(<strong>)?Xem\s+thêm\s*:?\s*(<\/strong>)?\s*([^<]+?)\s*(<strong>)?\s*<a[^>]+href=["\']([^"\']+)["\'][^>]*>([^<]+?)<\/a>/is',
            function($matches) {
                $textBeforeLink = trim(strip_tags($matches[3] ?? ''));
                $href = $matches[5] ?? '#';
                $linkText = trim(strip_tags($matches[6] ?? ''));
                
                // Nếu text trước link giống với link text (hoặc chứa link text), chỉ giữ link
                if (!empty($textBeforeLink) && !empty($linkText)) {
                    // So sánh text (bỏ qua khoảng trắng và case)
                    $textBeforeLinkNormalized = preg_replace('/\s+/', ' ', strtolower($textBeforeLink));
                    $linkTextNormalized = preg_replace('/\s+/', ' ', strtolower($linkText));
                    
                    // Nếu text trước link chứa link text hoặc ngược lại
                    if (stripos($textBeforeLinkNormalized, $linkTextNormalized) !== false || 
                        stripos($linkTextNormalized, $textBeforeLinkNormalized) !== false ||
                        $textBeforeLinkNormalized === $linkTextNormalized) {
                        // Chỉ giữ link với "Xem thêm:"
                        return '<a href="' . $href . '">Xem thêm: ' . $linkText . '</a>';
                    }
                }
                
                // Nếu không trùng, giữ nguyên nhưng đảm bảo chỉ có một "Xem thêm:"
                if (preg_match('/Xem\s+thêm\s*:?\s*/i', $textBeforeLink)) {
                    // Xóa "Xem thêm:" khỏi text trước link
                    $textBeforeLink = preg_replace('/Xem\s+thêm\s*:?\s*/i', '', $textBeforeLink);
                    return ($textBeforeLink ? $textBeforeLink . ' ' : '') . '<a href="' . $href . '">Xem thêm: ' . $linkText . '</a>';
                }
                
                return $matches[0];
            },
            $html
        );
        
        // Xóa các chuỗi CSS selector rác (Tailwind, attribute selectors) còn sót lại
        // Pattern: [&_...], _*]:..., standard-markdown, progressive-markdown, etc.
        $html = preg_replace('/\[&[^\]]*\]/i', '', $html); // [&_pre>div]:bg-bg-000/50
        $html = preg_replace('/_\*?\]:[^\s">]+/i', '', $html); // _*]:min-w-0
        $html = preg_replace('/\b(standard-markdown|progressive-markdown|grid-cols|gap|relative|leading|font-claude-response|group|pb-|pl-|pr-|bg-|border-|ignore-pre-bg)\b[^\s">]*/i', '', $html);
        $html = preg_replace('/\b(data-test-render-count|data-is-streaming|class|id|style)\s*=\s*["\'][^"\']*["\']/i', '', $html);
        
        // Xóa các chuỗi text rác còn sót lại (kiểu "> _*]:min-w-0 standard-markdown">)
        $html = preg_replace('/>\s*[\[\&_*\]\s:]+[a-z0-9\-]+\s*">/i', '>', $html);
        $html = preg_replace('/"\s*[\[\&_*\]\s:]+[a-z0-9\-]+\s*>/i', '"', $html);
        
        // Xóa các khoảng trắng thừa
        $html = preg_replace('/\s+/', ' ', $html);
        $html = str_replace('> <', '><', $html);
        
        // Xóa các thẻ span rỗng một lần nữa sau khi decode
        $html = preg_replace('/<span>\s*<\/span>/i', '', $html);
        $html = preg_replace('/<span><\/span>/i', '', $html);
        
        // Xóa XML declaration một lần nữa ở cuối cùng (đảm bảo triệt để)
        $html = preg_replace('/<\?xml[^>]*\?>/i', '', $html);
        $html = preg_replace('/<\?xml\s+encoding[^>]*\?>/i', '', $html);
        $html = preg_replace('/<\?xml\s+encoding\s*=\s*["\']UTF-8["\'][^>]*\?>/i', '', $html);
        $html = preg_replace('/<\?xml\s+encoding\s*=\s*["\']utf-8["\'][^>]*\?>/i', '', $html);
        $html = preg_replace('/<\?xml[^>]*>/i', '', $html);
        
        // Xóa các khoảng trắng ở đầu và cuối
        $html = trim($html);
        
        // Đảm bảo không bắt đầu bằng XML declaration
        if (preg_match('/^<\?xml/i', $html)) {
            $html = preg_replace('/^<\?xml[^>]*\?>\s*/i', '', $html);
            $html = preg_replace('/^<\?xml[^>]*>\s*/i', '', $html);
        }

        $html = trim($html);

        // Guard: nếu cleaning làm rỗng (hoặc DOM fail) thì fallback về bản decode tối thiểu để không mất content
        if ($html === '' && trim(strip_tags($originalInput)) !== '') {
            $fallback = preg_replace('/<\?xml[^>]*\?>/i', '', $originalInput);
            $fallback = html_entity_decode($fallback, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $fallback = $this->sanitizeHtmlBasic($fallback);
            return $fallback;
        }

        return $html;
    }

    /**
     * Fallback sanitizer (khi DOMDocument fail): ép output chỉ còn HTML cơ bản + text thuần,
     * xóa sạch class/id/style/data-* và bóc hết wrapper div/span.
     */
    protected function sanitizeHtmlBasic(string $html): string
    {
        $html = preg_replace('/<!DOCTYPE[^>]*>/i', '', $html);
        $html = preg_replace('/<\?xml[^>]*\?>/i', '', $html);
        $html = preg_replace('/<\/?html[^>]*>/i', '', $html);
        $html = preg_replace('/<\/?body[^>]*>/i', '', $html);

        // Bóc hết div/span wrapper
        $html = preg_replace('/<\/?(div|span)[^>]*>/i', '', $html);

        // Chuẩn hóa <a>: chỉ giữ href/title (ĐẢM BẢO luôn có href, fallback về nobifashion.vn nếu không có)
        $html = preg_replace_callback(
            '/<a\b([^>]*)>/i',
            function ($m) {
                $attrs = $m[1] ?? '';
                $href = null;
                $title = null;
                if (preg_match('/\bhref\s*=\s*("([^"]*)"|\'([^\']*)\')/i', $attrs, $mm)) {
                    $href = $mm[2] !== '' ? $mm[2] : ($mm[3] ?? '');
                }
                if (preg_match('/\btitle\s*=\s*("([^"]*)"|\'([^\']*)\')/i', $attrs, $mm)) {
                    $title = $mm[2] !== '' ? $mm[2] : ($mm[3] ?? '');
                }
                // Đảm bảo luôn có href (fallback về nobifashion.vn nếu không có)
                if (empty($href)) {
                    $href = 'https://nobifashion.vn';
                }
                $out = '<a href="' . htmlspecialchars($href, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '"';
                if (!empty($title)) {
                    $out .= ' title="' . htmlspecialchars($title, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '"';
                }
                $out .= '>';
                return $out;
            },
            $html
        );

        // Chuẩn hóa <img>: chỉ giữ src/alt/title (hỗ trợ <img ...> và <img .../>)
        $html = preg_replace_callback(
            '/<img\b([^>]*?)(\/?)>/i',
            function ($m) {
                $attrs = $m[1] ?? '';
                $src = null;
                $alt = null;
                $title = null;
                if (preg_match('/\bsrc\s*=\s*("([^"]*)"|\'([^\']*)\')/i', $attrs, $mm)) {
                    $src = $mm[2] !== '' ? $mm[2] : ($mm[3] ?? '');
                }
                if (preg_match('/\balt\s*=\s*("([^"]*)"|\'([^\']*)\')/i', $attrs, $mm)) {
                    $alt = $mm[2] !== '' ? $mm[2] : ($mm[3] ?? '');
                }
                if (preg_match('/\btitle\s*=\s*("([^"]*)"|\'([^\']*)\')/i', $attrs, $mm)) {
                    $title = $mm[2] !== '' ? $mm[2] : ($mm[3] ?? '');
                }
                $out = '<img';
                if (!empty($src)) {
                    $out .= ' src="' . htmlspecialchars($src, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '"';
                }
                if (!empty($alt)) {
                    $out .= ' alt="' . htmlspecialchars($alt, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '"';
                }
                if (!empty($title)) {
                    $out .= ' title="' . htmlspecialchars($title, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '"';
                }
                $out .= '>';
                return $out;
            },
            $html
        );

        // Xóa toàn bộ attributes còn lại trên mọi tag (TRỪ <a> và <img> vì đã được chuẩn hóa rồi)
        $html = preg_replace('/<(?!a\b|img\b)([a-z0-9]+)\s+[^>]*?>/i', '<$1>', $html);

        // Xóa các chuỗi CSS selector rác (Tailwind, attribute selectors) còn sót lại sau khi xóa div/span
        // Pattern: [&_...], _*]:..., standard-markdown, progressive-markdown, etc.
        $html = preg_replace('/\[&[^\]]*\]/i', '', $html); // [&_pre>div]:bg-bg-000/50
        $html = preg_replace('/_\*?\]:[^\s">]+/i', '', $html); // _*]:min-w-0
        $html = preg_replace('/\b(standard-markdown|progressive-markdown|grid-cols|gap|relative|leading|font-claude-response|group|pb-|pl-|pr-|bg-|border-|ignore-pre-bg)\b[^\s">]*/i', '', $html);
        $html = preg_replace('/\b(data-test-render-count|data-is-streaming|class|id|style)\s*=\s*["\'][^"\']*["\']/i', '', $html);
        
        // Xóa các chuỗi text rác còn sót lại (kiểu "> _*]:min-w-0 standard-markdown">)
        $html = preg_replace('/>\s*[\[\&_*\]\s:]+[a-z0-9\-]+\s*">/i', '>', $html);
        $html = preg_replace('/"\s*[\[\&_*\]\s:]+[a-z0-9\-]+\s*>/i', '"', $html);
        
        // Xóa thẻ rỗng
        $html = preg_replace('/<a>\s*<\/a>/i', '', $html);

        // Chỉ giữ tag cơ bản
        $allowed = '(p|h1|h2|h3|h4|h5|h6|ul|ol|li|strong|em|b|i|u|a|img|br|blockquote)';
        $html = preg_replace('/<(\/?)(?!' . $allowed . '\b)[a-z0-9]+\b[^>]*>/i', '', $html);

        // Cleanup whitespace
        $html = preg_replace('/\s+/', ' ', $html);
        $html = str_replace('> <', '><', $html);
        return trim($html);
    }

    /**
     * Generate fallback keywords từ title
     */
    protected function generateFallbackKeywords(string $title): string
    {
        $words = explode(' ', $title);
        $keywords = array_slice($words, 0, 10);
        return implode(', ', $keywords);
    }

    /**
     * Fetch HTML using Playwright service
     */
    protected function fetchHtml(string $url, ?string $waitForSelector = null): string
    {
        Log::info("=== FETCH HTML WITH PLAYWRIGHT SERVICE ===");
        Log::info("URL: {$url}");
        if (!empty($waitForSelector)) {
            Log::info("waitForSelector: {$waitForSelector}");
        }

        try {
            $playwrightServiceUrl = env('PLAYWRIGHT_SERVICE_URL', 'http://localhost:3001');

            Log::info("Calling Playwright service: {$playwrightServiceUrl}/crawl");

            $response = Http::timeout(120)
                ->post("{$playwrightServiceUrl}/crawl", [
                    'url' => $url,
                    'waitForSelector' => $waitForSelector,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['success']) && $data['success'] === true && isset($data['html'])) {
                    $html = $data['html'];
                    Log::info("Response body length: " . strlen($html));
                    Log::info("Response body preview (first 500 chars): " . substr($html, 0, 500));

                    if (strpos($html, 'Just a moment') !== false || strpos($html, 'cf-browser-verification') !== false) {
                        Log::warning("Cloudflare challenge still present in response");
                    }
                    return $html;
                } else {
                    Log::error("Playwright service returned an error or invalid data", [
                        'response_body' => $response->body(),
                    ]);
                    throw new \Exception("Playwright service returned an error: " . ($data['error'] ?? 'Unknown error'));
                }
            } else {
                Log::error("Playwright service HTTP error", [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \Exception("Playwright service HTTP error: " . $response->status());
            }
        } catch (\Exception $e) {
            Log::critical('PLAYWRIGHT SERVICE FAILED', [
                'url' => $url,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new \Exception("Failed to fetch URL with Playwright service: " . $e->getMessage());
        }
    }

    /**
     * Make absolute URL
     */
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

    /**
     * Get image extension from URL
     */
    protected function getImageExtension(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        
        if (empty($extension)) {
            return 'jpg';
        }
        
        return strtolower($extension);
    }

    /**
     * Download image
     */
    protected function downloadImage(string $url, string $savePath): bool
    {
        try {
            $response = Http::timeout(60)->get($url);
            
            if ($response->successful()) {
                file_put_contents($savePath, $response->body());
                Log::info("Image downloaded: {$savePath}");
                return true;
            } else {
                Log::warning("Failed to download image: {$url} (Status: {$response->status()})");
                return false;
            }
        } catch (\Exception $e) {
            Log::error("Error downloading image: {$url}", [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Ensure unique slug
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
}
