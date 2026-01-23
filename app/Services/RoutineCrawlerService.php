<?php

namespace App\Services;

use App\Models\Post;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use DOMDocument;
use DOMXPath;

class RoutineCrawlerService
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

        Log::info('=== ROUTINE CRAWLER START ===');
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

        Log::info("=== ROUTINE CRAWLER END === Success: {$results['success']}, Failed: {$results['failed']}");
        return $results;
    }

    /**
     * Extract post URLs from category page
     */
    protected function extractPostUrlsFromCategory(string $categoryUrl): array
    {
        Log::info("=== EXTRACT POST URLS FROM CATEGORY ===");
        Log::info("Category URL: {$categoryUrl}");
        
        $html = $this->fetchHtml($categoryUrl);
        Log::info("HTML length: " . strlen($html));
        
        if (empty($html)) {
            Log::warning("Empty HTML returned from category URL");
            return [];
        }

        // Debug: Lưu một phần HTML để kiểm tra structure
        $htmlSample = substr($html, 0, 5000);
        Log::info("HTML sample (first 5000 chars): " . $htmlSample);
        
        // Tìm tất cả div có class chứa các từ khóa để debug
        preg_match_all('/<div[^>]*class="[^"]*(?:mx-auto|container|px-4|w-full|relative|block|type-post)[^"]*"[^>]*>/i', $html, $matches);
        Log::info("Found " . count($matches[0]) . " divs with relevant classes");
        if (count($matches[0]) > 0) {
            Log::info("Sample divs: " . implode("\n", array_slice($matches[0], 0, 5)));
        }
        
        // Tìm tất cả link có chứa /tin-thoi-trang/
        preg_match_all('/<a[^>]*href="([^"]*\/tin-thoi-trang\/[^"]*)"[^>]*>/i', $html, $linkMatches);
        Log::info("Found " . count($linkMatches[1]) . " links containing '/tin-thoi-trang/'");
        if (count($linkMatches[1]) > 0) {
            Log::info("Sample links: " . implode("\n", array_slice($linkMatches[1], 0, 5)));
        }

        $dom = new DOMDocument();
        // Ép charset UTF-8 để tránh lỗi mojibake (MÃ¹a Ä...)
        $utf8Meta = '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
        @$dom->loadHTML($utf8Meta . $html);
        $xpath = new DOMXPath($dom);

        $postUrls = [];

        // Tìm div có class chứa "mx-auto container px-4"
        $containerNodes = $xpath->query("//div[contains(@class, 'mx-auto') and contains(@class, 'container') and contains(@class, 'px-4')]");
        Log::info("Found " . $containerNodes->length . " container nodes (mx-auto container px-4)");
        
        if ($containerNodes->length > 0) {
            // Thử tất cả container nodes, không chỉ node đầu tiên
            foreach ($containerNodes as $containerIndex => $container) {
                Log::info("Processing container #{$containerIndex}");
                
                // Tìm tất cả div có class chứa "w-full relative block"
                $postDivs = $xpath->query(".//div[contains(@class, 'w-full') and contains(@class, 'relative') and contains(@class, 'block')]", $container);
                Log::info("Found " . $postDivs->length . " post divs (w-full relative block) in container #{$containerIndex}");
                
                if ($postDivs->length > 0) {
                    foreach ($postDivs as $index => $postDiv) {
                        Log::info("Processing post div #{$index} in container #{$containerIndex}");
                        
                        // Tìm div có class chứa "type-post"
                        $typePostDiv = $xpath->query(".//div[contains(@class, 'type-post')]", $postDiv)->item(0);
                        
                        if ($typePostDiv) {
                            Log::info("Found type-post div in post div #{$index}");
                            
                            // Tìm thẻ a đầu tiên trong div type-post
                            $linkNodes = $xpath->query(".//a", $typePostDiv);
                            Log::info("Found " . $linkNodes->length . " link nodes in type-post div");
                            
                            if ($linkNodes->length > 0) {
                                $linkNode = $linkNodes->item(0);
                                
                                if ($linkNode) {
                                    // Lấy href từ thẻ a
                                    $href = '';
                                    if ($linkNode->hasAttribute('href')) {
                                        $href = $linkNode->getAttribute('href');
                                        Log::info("Found href: {$href}");
                                    } else {
                                        Log::warning("Link node has no href attribute");
                                        continue;
                                    }
                                    
                                    if (!empty($href)) {
                                        $absoluteUrl = $this->makeAbsoluteUrl($href, $categoryUrl);
                                        $postUrls[] = $absoluteUrl;
                                        Log::info("Added post URL: {$absoluteUrl}");
                                    } else {
                                        Log::warning("Empty href found");
                                    }
                                } else {
                                    Log::warning("Link node is null");
                                }
                            } else {
                                Log::warning("No link nodes found in type-post div");
                            }
                        } else {
                            Log::warning("No type-post div found in post div #{$index}");
                            
                            // Thử tìm link trực tiếp trong postDiv nếu không có type-post
                            $directLinks = $xpath->query(".//a", $postDiv);
                            Log::info("Trying direct links in post div: " . $directLinks->length);
                            
                            if ($directLinks->length > 0) {
                                foreach ($directLinks as $linkIndex => $linkNode) {
                                    if ($linkNode->hasAttribute('href')) {
                                        $href = $linkNode->getAttribute('href');
                                        // Chỉ lấy link có chứa /tin-thoi-trang/ để đảm bảo là link bài viết
                                        if (strpos($href, '/tin-thoi-trang/') !== false) {
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
                } else {
                    Log::warning("No post divs found in container #{$containerIndex}");
                    
                    // Thử tìm link trực tiếp trong container
                    $allLinks = $xpath->query(".//a[contains(@href, '/tin-thoi-trang/')]", $container);
                    Log::info("Trying direct links in container: " . $allLinks->length);
                    
                    foreach ($allLinks as $linkNode) {
                        if ($linkNode->hasAttribute('href')) {
                            $href = $linkNode->getAttribute('href');
                            $absoluteUrl = $this->makeAbsoluteUrl($href, $categoryUrl);
                            $postUrls[] = $absoluteUrl;
                            Log::info("Added post URL (container direct link): {$absoluteUrl}");
                        }
                    }
                }
            }
        } else {
            Log::warning("No container nodes found (mx-auto container px-4)");
            
            // Thử tìm với query khác để debug
            $allDivs = $xpath->query("//div[contains(@class, 'mx-auto')]");
            Log::info("Found " . $allDivs->length . " divs with class containing 'mx-auto'");
            
            $allContainerDivs = $xpath->query("//div[contains(@class, 'container')]");
            Log::info("Found " . $allContainerDivs->length . " divs with class containing 'container'");
            
            // Thử tìm link trực tiếp
            $allPostLinks = $xpath->query("//a[contains(@href, '/tin-thoi-trang/')]");
            Log::info("Found " . $allPostLinks->length . " links containing '/tin-thoi-trang/'");
            
            foreach ($allPostLinks as $linkNode) {
                if ($linkNode->hasAttribute('href')) {
                    $href = $linkNode->getAttribute('href');
                    $absoluteUrl = $this->makeAbsoluteUrl($href, $categoryUrl);
                    $postUrls[] = $absoluteUrl;
                    Log::info("Added post URL (fallback): {$absoluteUrl}");
                }
            }
        }

        Log::info("Total unique post URLs found: " . count(array_unique($postUrls)));
        return array_unique($postUrls);
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
        
        $html = $this->fetchHtml($postUrl);
        Log::info("HTML length: " . strlen($html));
        
        if (empty($html)) {
            Log::warning("Empty HTML returned from post URL");
            return null;
        }

        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        $xpath = new DOMXPath($dom);

        // 1. Lấy title từ h1#page-title
        $titleNode = $xpath->query("//h1[@id='page-title']")->item(0);
        Log::info("Title node found: " . ($titleNode ? 'yes' : 'no'));
        
        $originalTitle = $titleNode ? trim($titleNode->textContent) : '';
        Log::info("Original title: " . ($originalTitle ?: 'EMPTY'));

        if (empty($originalTitle)) {
            Log::warning("Empty title, skipping post");
            return null;
        }

        // Bỏ qua bài viết nếu tiêu đề chứa "Routine", "routine" hoặc "routine.vn"
        if (preg_match('/\b(Routine|routine|routine\.vn)\b/i', $originalTitle)) {
            Log::info("Title contains Routine/routine/routine.vn, skipping post: {$originalTitle}");
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
        $titleForSlug = preg_replace('/\s+/', ' ', $titleForSlug); // Xóa khoảng trắng thừa
        $titleForSlug = trim($titleForSlug);
        
        // Nếu title rỗng sau khi loại bỏ, dùng title gốc
        if (empty($titleForSlug)) {
            $titleForSlug = $originalTitle;
        }
        
        $slug = Str::slug($titleForSlug);
        // Đảm bảo slug không rỗng và chỉ chứa chữ cái, số, dấu gạch ngang
        $slug = preg_replace('/[^a-z0-9\-]/', '', $slug);
        $slug = preg_replace('/\-+/', '-', $slug); // Xóa dấu gạch ngang liên tiếp
        $slug = trim($slug, '-'); // Xóa dấu gạch ngang ở đầu và cuối
        
        // Nếu slug vẫn rỗng, dùng title gốc
        if (empty($slug)) {
            $slug = Str::slug($originalTitle);
        }
        
        $slug = $this->ensureUniqueSlug($slug);
        $metaCanonical = 'https://nobifashion.vn/' . $slug;

        // 4. Lấy hình ảnh (cần slug để đặt tên file)
        $thumbnail = $this->extractThumbnail($xpath, $postUrl, $slug, $seoTitle);

        // 5. Lấy content
        $contentHtml = $this->extractContent($xpath);
        Log::info("Content HTML length: " . strlen($contentHtml));
        
        if (empty($contentHtml)) {
            Log::warning("Empty content HTML, skipping post");
            return null;
        }

        // Xử lý HTML: xóa class, thay thẻ a, xử lý img, thay Routine
        Log::info("Processing HTML...");
        $startTime = microtime(true);
        $processedHtml = $this->processHtml($contentHtml, $slug, $postUrl);
        Log::info("HTML processed in " . round(microtime(true) - $startTime, 2) . " seconds");

        // Dùng AI viết lại content với prompt đặc biệt cho Routine
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
     * Extract thumbnail image
     */
    protected function extractThumbnail(DOMXPath $xpath, string $postUrl, string $slug, string $title): array
    {
        Log::info("=== EXTRACT THUMBNAIL ===");
        $result = ['path' => null, 'alt' => $title];

        // Tìm div#post-toc-detail
        $tocDiv = $xpath->query("//div[@id='post-toc-detail']")->item(0);
        Log::info("TOC div found: " . ($tocDiv ? 'yes' : 'no'));
        
        if ($tocDiv) {
            // Tìm div có class chứa "w-full h-full object-cover unselectable overflow-hidden"
            $imgContainer = $xpath->query(".//div[contains(@class, 'w-full') and contains(@class, 'h-full') and contains(@class, 'object-cover') and contains(@class, 'unselectable') and contains(@class, 'overflow-hidden')]", $tocDiv)->item(0);
            Log::info("Image container found: " . ($imgContainer ? 'yes' : 'no'));
            
            if ($imgContainer) {
                // Tìm picture đầu tiên
                $pictureNode = $xpath->query(".//picture", $imgContainer)->item(0);
                Log::info("Picture node found: " . ($pictureNode ? 'yes' : 'no'));
                
                if ($pictureNode) {
                    // Tìm img đầu tiên
                    $imgNode = $xpath->query(".//img", $pictureNode)->item(0);
                    Log::info("Img node found: " . ($imgNode ? 'yes' : 'no'));
                    
                    if ($imgNode) {
                        // Lấy src từ img hoặc từ source trong picture
                        $imgUrl = '';
                        if ($imgNode->hasAttribute('src')) {
                            $imgUrl = $imgNode->getAttribute('src');
                            Log::info("Found img src: {$imgUrl}");
                        } else {
                            // Thử lấy từ source trong picture
                            $sourceNode = $xpath->query(".//source", $pictureNode)->item(0);
                            if ($sourceNode && $sourceNode->hasAttribute('srcset')) {
                                $srcset = $sourceNode->getAttribute('srcset');
                                Log::info("Found srcset: {$srcset}");
                                // Lấy URL đầu tiên từ srcset
                                if (preg_match('/^([^\s]+)/', $srcset, $matches)) {
                                    $imgUrl = $matches[1];
                                    Log::info("Extracted img URL from srcset: {$imgUrl}");
                                }
                            }
                        }
                        
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
                                $result['alt'] = ($imgNode->hasAttribute('alt') ? $imgNode->getAttribute('alt') : '') ?: $title;
                                Log::info("Thumbnail saved: {$result['path']}");
                            } else {
                                Log::warning("Failed to download thumbnail: {$absoluteImgUrl}");
                            }
                        } else {
                            Log::warning("Empty img URL");
                        }
                    }
                }
            }
        } else {
            Log::warning("TOC div not found, trying alternative selectors");
            // Thử tìm ảnh bằng cách khác
            $allImages = $xpath->query("//img");
            Log::info("Total images found on page: " . $allImages->length);
        }

        return $result;
    }

    /**
     * Extract content from div.text-editor.rich-text
     */
    protected function extractContent(DOMXPath $xpath): string
    {
        Log::info("=== EXTRACT CONTENT ===");
        
        // Thử nhiều cách tìm content div
        $contentNode = $xpath->query("//div[contains(@class, 'text-editor') and contains(@class, 'rich-text')]")->item(0);
        Log::info("Content node found (text-editor rich-text): " . ($contentNode ? 'yes' : 'no'));
        
        if (!$contentNode) {
            // Thử tìm với class khác
            $contentNode = $xpath->query("//div[contains(@class, 'text-editor')]")->item(0);
            Log::info("Content node found (text-editor only): " . ($contentNode ? 'yes' : 'no'));
        }
        
        if (!$contentNode) {
            // Thử tìm với rich-text
            $contentNode = $xpath->query("//div[contains(@class, 'rich-text')]")->item(0);
            Log::info("Content node found (rich-text only): " . ($contentNode ? 'yes' : 'no'));
        }
        
        if (!$contentNode) {
            Log::warning("No content node found");
            return '';
        }

        $html = '';
        foreach ($contentNode->childNodes as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE || $child->nodeType === XML_TEXT_NODE) {
                $html .= $contentNode->ownerDocument->saveHTML($child);
            }
        }
        
        Log::info("Extracted content HTML length: " . strlen($html));
        return $html;
    }

    /**
     * Process HTML: xóa class, xử lý thẻ a, img, thay Routine
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

        // Xử lý thẻ a: nếu không chứa Routine/routine/routine.vn thì thay href = random meta_canonical
        $linkNodes = $xpath->query('//a');
        foreach ($linkNodes as $linkNode) {
            $linkText = $linkNode->textContent;
            if (!preg_match('/routine|routine\.vn/i', $linkText)) {
                // Lấy random meta_canonical từ posts
                $randomPost = Post::where('meta_canonical', '!=', null)
                    ->where('meta_canonical', '<>', '')
                    ->inRandomOrder()
                    ->first();
                
                if ($randomPost) {
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
                // Tạo absolute URL nếu cần
                $absoluteImgUrl = $imgUrl;
                if (!filter_var($imgUrl, FILTER_VALIDATE_URL) && !empty($postUrl)) {
                    $absoluteImgUrl = $this->makeAbsoluteUrl($imgUrl, $postUrl);
                }
                
                if (filter_var($absoluteImgUrl, FILTER_VALIDATE_URL)) {
                    $extension = $this->getImageExtension($absoluteImgUrl);
                    $filename = $slug . '-' . uniqid() . '.' . $extension;
                    $savePath = public_path('clients/assets/img/posts/' . $filename);
                    
                    // Tạo thư mục nếu chưa có
                    $dir = dirname($savePath);
                    if (!is_dir($dir)) {
                        mkdir($dir, 0755, true);
                    }
                    
                    if ($this->downloadImage($absoluteImgUrl, $savePath)) {
                        $imgNode->setAttribute('src', 'https://nobifashion.vn/clients/assets/img/posts/' . $filename);
                        // Xóa data-src nếu có
                        if ($imgNode->hasAttribute('data-src')) {
                            $imgNode->removeAttribute('data-src');
                        }
                    }
                }
            }
        }

        // Thay Routine/routine/routine.vn thành Nobi Fashion Việt Nam với link
        $html = $dom->saveHTML();
        $html = preg_replace('/\b(Routine|routine|routine\.vn)\b/i', '<a href="https://nobifashion.vn">Nobi Fashion Việt Nam</a>', $html);

        return $html;
    }

    /**
     * Finalize content: chuyển div thành p, xóa class
     */
    protected function finalizeContent(string $html): string
    {
        $originalInput = $html;
        // Xóa XML declaration nếu có trong input
        $html = preg_replace('/<\?xml[^>]*\?>/i', '', $html);
        $html = preg_replace('/<\?xml\s+encoding[^>]*\?>/i', '', $html);

        $dom = new DOMDocument();
        // Ép charset UTF-8 để tránh mojibake + load dạng fragment để tránh lòi </body></html>
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

        // Chuyển div thành p nếu div không chứa block-level children
        $divNodes = $xpath->query('//div');
        foreach ($divNodes as $divNode) {
            $hasBlockChildren = false;
            foreach ($divNode->childNodes as $child) {
                if ($child->nodeType === XML_ELEMENT_NODE) {
                    $tagName = strtolower($child->nodeName);
                    if (in_array($tagName, ['div', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'ul', 'ol', 'p'])) {
                        $hasBlockChildren = true;
                        break;
                    }
                }
            }

            if (!$hasBlockChildren) {
                $pNode = $dom->createElement('p');
                foreach ($divNode->childNodes as $child) {
                    $pNode->appendChild($child->cloneNode(true));
                }
                $divNode->parentNode->replaceChild($pNode, $divNode);
            }
        }

        // Giữ tag cơ bản + strip attributes CHẶT (chỉ giữ href/title cho <a>, src/alt/title cho <img>)
        $allowedTags = ['p','h1','h2','h3','h4','h5','h6','ul','ol','li','strong','em','b','i','u','a','img','br','blockquote'];
        $allNodes = $xpath->query('//*');
        $nodesToRemove = [];
        foreach ($allNodes as $node) {
            if (!in_array(strtolower($node->nodeName), $allowedTags, true)) {
                $nodesToRemove[] = $node;
            }
        }
        foreach ($nodesToRemove as $node) {
            $parent = $node->parentNode;
            if ($parent) {
                while ($node->firstChild) {
                    $parent->insertBefore($node->firstChild, $node);
                }
                $parent->removeChild($node);
            }
        }

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

        // Lấy innerHTML của body thay vì toàn bộ document để tránh XML declaration
        $body = $dom->getElementsByTagName('body')->item(0);
        if ($body) {
            $html = '';
            foreach ($body->childNodes as $node) {
                $html .= $dom->saveHTML($node);
            }
        } else {
            $html = $dom->saveHTML();
        }
        
        // Loại bỏ DOCTYPE và XML declaration (nếu còn)
        $html = preg_replace('/<!DOCTYPE[^>]*>/i', '', $html);
        $html = preg_replace('/<\?xml[^>]*\?>/i', '', $html);
        $html = preg_replace('/<\?xml\s+encoding[^>]*\?>/i', '', $html);
        $html = preg_replace('/<\/?html[^>]*>/i', '', $html);
        $html = preg_replace('/<\/?body[^>]*>/i', '', $html);

        // Chắc chắn không còn style/class/id/data-* (phòng trường hợp HTML bị "lọt" qua DOM)
        $html = preg_replace('/\s+(class|id|style|data-[a-z0-9\-\_:]+)\s*=\s*"[^"]*"/i', '', $html);
        $html = preg_replace("/\s+(class|id|style|data-[a-z0-9\-\_:]+)\s*=\s*'[^']*'/i", '', $html);
        
        // Decode HTML entities về UTF-8
        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Fix mojibake: UTF-8 bị đọc như ISO-8859-1/Windows-1252 (dạng "MÃ¹a Ä...")
        if (preg_match('/[ÃÂ][^\s]{1,2}/u', $html)) {
            $fixedIso = @mb_convert_encoding($html, 'UTF-8', 'ISO-8859-1');
            $fixedWin = @mb_convert_encoding($html, 'UTF-8', 'Windows-1252');
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

        $html = trim($html);

        // Guard: nếu cleaning làm rỗng mà input vẫn có text thì fallback tối thiểu
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
     * xóa sạch class/id/style/data-* và bóc hết div/span.
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

        // Chuẩn hóa <img>: chỉ giữ src/alt/title
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

        // Chỉ giữ tag cơ bản
        $allowed = '(p|h1|h2|h3|h4|h5|h6|ul|ol|li|strong|em|b|i|u|a|img|br|blockquote)';
        $html = preg_replace('/<(\/?)(?!' . $allowed . '\b)[a-z0-9]+\b[^>]*>/i', '', $html);

        $html = preg_replace('/\s+/', ' ', $html);
        $html = str_replace('> <', '><', $html);
        return trim($html);
    }

    /**
     * Fetch HTML from URL using Playwright Service (Node.js)
     */
    protected function fetchHtml(string $url): string
    {
        Log::info("=== FETCH HTML WITH PLAYWRIGHT SERVICE ===");
        Log::info("URL: {$url}");
        
        try {
            // Gọi Playwright service
            $playwrightServiceUrl = env('PLAYWRIGHT_SERVICE_URL', 'http://localhost:3001');
            
            Log::info("Calling Playwright service: {$playwrightServiceUrl}/crawl");
            
            $response = Http::timeout(90)
                ->post("{$playwrightServiceUrl}/crawl", [
                    'url' => $url,
                ]);
            
            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['success']) && $data['success'] === true && isset($data['html'])) {
                    $html = $data['html'];
                    Log::info("Response body length: " . strlen($html));
                    Log::info("Response body preview (first 500 chars): " . substr($html, 0, 500));
                    
                    // Kiểm tra xem có bị Cloudflare challenge không
                    if (strpos($html, 'Just a moment') !== false || strpos($html, 'cf-browser-verification') !== false) {
                        Log::warning("Cloudflare challenge still present in response");
                    }
                    
                    return $html;
                } else {
                    $error = $data['error'] ?? 'Unknown error';
                    Log::error("Playwright service returned error", ['error' => $error]);
                    throw new \RuntimeException("Playwright service error: {$error}");
                }
            } else {
                Log::error("Playwright service HTTP error", [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \RuntimeException("Playwright service HTTP error: " . $response->status());
            }
            
        } catch (\Throwable $e) {
            Log::critical('PLAYWRIGHT SERVICE FAILED', [
                'url' => $url,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw new \RuntimeException("Failed to fetch URL with Playwright service: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Make absolute URL from relative URL
     */
    protected function makeAbsoluteUrl(string $relativeUrl, string $baseUrl): string
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
     * Download image
     */
    protected function downloadImage(string $url, string $savePath): bool
    {
        try {
            // Tạo thư mục nếu chưa có
            $dir = dirname($savePath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $response = Http::timeout(30)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                ])
                ->get($url);

            if ($response->successful()) {
                file_put_contents($savePath, $response->body());
                return true;
            }
        } catch (\Exception $e) {
            Log::error('Error downloading image', ['url' => $url, 'error' => $e->getMessage()]);
        }

        return false;
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

    /**
     * Generate fallback keywords
     */
    protected function generateFallbackKeywords(string $title): string
    {
        $stopWords = ['là', 'gì', 'của', 'và', 'với', 'cho', 'từ', 'đến', 'trong', 'về', 'theo', 'để', 'được', 'có', 'không', 'mà', 'này', 'đó', 'nào', 'sẽ', 'đã', 'đang'];
        
        $words = preg_split('/[\s,\.\-]+/u', mb_strtolower($title));
        
        $keywords = array_filter($words, function($word) use ($stopWords) {
            $word = trim($word);
            return !empty($word) 
                && mb_strlen($word) >= 2 
                && !in_array($word, $stopWords)
                && !is_numeric($word);
        });
        
        $keywords = array_slice(array_unique($keywords), 0, 10);
        
        return implode(', ', $keywords);
    }
}
