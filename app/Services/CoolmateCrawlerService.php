<?php

namespace App\Services;

use DateTimeImmutable;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CoolmateCrawlerService
{
    private const CSV_CELL_CHARACTER_LIMIT = 30000;

    private const PAGE_BATCH_SIZE = 16;

    private const IMAGE_BATCH_SIZE = 32;

    private const PAGE_TIMEOUT_SECONDS = 25;

    private const IMAGE_TIMEOUT_SECONDS = 15;

    private const PAGE_SELECTOR = 'div.entry-content.single-page';

    private const CRAWLED_URLS_FILE = 'crawled_urls.json';

    /**
     * Crawl bài viết theo batch, lưu ảnh vào thư mục tạm và xuất toàn bộ dữ liệu ra CSV.
     * Không đọc hoặc ghi dữ liệu Post trong database.
     */
    public function crawlPostsToCsv(
        array $postUrls,
        bool $recrawlExisting = false,
        bool $downloadMainImage = true,
        ?string $targetFileName = null
    ): array {
        set_time_limit(3600);
        $this->ensureTempDirectories();

        $results = [
            'total' => count($postUrls),
            'processed' => 0,
            'success' => 0,
            'failed' => 0,
            'skipped' => 0,
            'skipped_duplicate' => 0,
            'skipped_history' => 0,
            'recrawl_existing' => $recrawlExisting,
            'download_main_image' => $downloadMainImage,
            'image_downloaded_count' => 0,
            'main_image_count' => 0,
            'extra_image_count' => 0,
            'posts' => [],
            'warnings' => [],
            'errors' => [],
            'file_name' => null,
            'file_path' => null,
            'main_directory' => $this->mainImageDirectory(),
            'extra_directory' => $this->extraImageDirectory(),
            'page_batch_size' => self::PAGE_BATCH_SIZE,
            'image_batch_size' => self::IMAGE_BATCH_SIZE,
        ];

        $urls = $this->sanitizePostUrls($postUrls, $results, $recrawlExisting);
        if ($urls === []) {
            return $results;
        }

        $fetchErrors = [];
        $htmlPayloads = $this->fetchHtmlPayloads($urls, $fetchErrors);
        $articles = [];

        foreach ($urls as $url) {
            $results['processed']++;

            if (! isset($htmlPayloads[$url])) {
                $results['failed']++;
                $results['errors'][] = "Không thể tải {$url}: ".($fetchErrors[$url] ?? 'Không nhận được HTML hợp lệ.');

                continue;
            }

            try {
                $articles[] = $this->extractArticleData($htmlPayloads[$url], $url);
            } catch (\Throwable $e) {
                $results['failed']++;
                $results['errors'][] = "Không thể phân tích {$url}: {$e->getMessage()}";
                Log::error('Coolmate article parsing failed', [
                    'url' => $url,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($articles === []) {
            if ($targetFileName !== null && trim($targetFileName) !== '') {
                $safeName = basename($targetFileName);
                $results['file_name'] = $safeName;
                $results['file_path'] = $this->tempRootDirectory().DIRECTORY_SEPARATOR.$safeName;
            }

            return $results;
        }

        $imageJobs = $this->buildImageJobs($articles, $downloadMainImage);
        $imageResults = $this->downloadImagesInBatches($imageJobs);
        $csvRows = $this->buildCsvRows($articles, $imageJobs, $imageResults, $results, $downloadMainImage);
        $fileName = $this->writeCsv($csvRows, $targetFileName);

        $results['file_name'] = $fileName;
        $results['file_path'] = $this->tempRootDirectory().DIRECTORY_SEPARATOR.$fileName;
        $this->rememberCrawledArticles($articles);

        return $results;
    }

    /**
     * Trả về đường dẫn an toàn của file CSV trong temp Coolmate.
     */
    public function resolveExportPath(string $fileName): ?string
    {
        $safeFileName = basename($fileName);
        if (
            $safeFileName !== $fileName
            || ! preg_match('/^coolmate_posts_[0-9_-]+\.csv$/', $safeFileName)
        ) {
            return null;
        }

        $path = $this->tempRootDirectory().DIRECTORY_SEPARATOR.$safeFileName;
        if (! is_file($path)) {
            return null;
        }

        $resolvedRoot = realpath($this->tempRootDirectory());
        $resolvedPath = realpath($path);
        if (
            $resolvedRoot === false
            || $resolvedPath === false
            || ! str_starts_with($resolvedPath, $resolvedRoot.DIRECTORY_SEPARATOR)
        ) {
            return null;
        }

        return $resolvedPath;
    }

    private function sanitizePostUrls(
        array $postUrls,
        array &$results,
        bool $recrawlExisting
    ): array
    {
        $urls = [];
        $seen = [];
        $crawledUrls = $this->loadCrawledUrls();

        foreach ($postUrls as $postUrl) {
            $originalUrl = trim((string) $postUrl);
            $normalizedUrl = $this->normalizePostUrl($originalUrl);

            if ($normalizedUrl === null) {
                $results['failed']++;
                $results['errors'][] = "URL Coolmate không hợp lệ: {$originalUrl}";

                continue;
            }

            if (isset($seen[$normalizedUrl])) {
                $results['skipped']++;
                $results['skipped_duplicate']++;
                $results['warnings'][] = "Bỏ qua URL trùng trong danh sách: {$normalizedUrl}";

                continue;
            }

            $seen[$normalizedUrl] = true;

            if (! $recrawlExisting && isset($crawledUrls[$normalizedUrl])) {
                $results['skipped']++;
                $results['skipped_history']++;
                $results['warnings'][] = "Bỏ qua URL đã crawl trước đó: {$normalizedUrl}";

                continue;
            }

            $urls[] = $normalizedUrl;
        }

        return $urls;
    }

    /**
     * @return array<string, array{crawled_at?: string, title?: string, slug?: string}>
     */
    private function loadCrawledUrls(): array
    {
        $path = $this->crawledUrlsPath();
        if (! is_file($path)) {
            return [];
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new \RuntimeException("Không thể đọc lịch sử URL Coolmate: {$path}");
        }

        try {
            if (! flock($handle, LOCK_SH)) {
                throw new \RuntimeException("Không thể khóa lịch sử URL Coolmate: {$path}");
            }

            $contents = stream_get_contents($handle);
            flock($handle, LOCK_UN);
        } finally {
            fclose($handle);
        }

        if ($contents === false || trim($contents) === '') {
            return [];
        }

        $registry = json_decode($contents, true);
        if (! is_array($registry)) {
            throw new \RuntimeException("File lịch sử URL Coolmate không phải JSON hợp lệ: {$path}");
        }

        return is_array($registry['urls'] ?? null) ? $registry['urls'] : [];
    }

    /**
     * Chỉ ghi nhớ các bài đã được xuất CSV thành công.
     *
     * @param  array<int, array<string, mixed>>  $articles
     */
    private function rememberCrawledArticles(array $articles): void
    {
        if ($articles === []) {
            return;
        }

        $path = $this->crawledUrlsPath();
        $handle = fopen($path, 'c+b');
        if ($handle === false) {
            throw new \RuntimeException("Không thể ghi lịch sử URL Coolmate: {$path}");
        }

        try {
            if (! flock($handle, LOCK_EX)) {
                throw new \RuntimeException("Không thể khóa lịch sử URL Coolmate: {$path}");
            }

            rewind($handle);
            $contents = stream_get_contents($handle);
            $registry = trim((string) $contents) === ''
                ? ['version' => 1, 'urls' => []]
                : json_decode((string) $contents, true);

            if (! is_array($registry) || ! is_array($registry['urls'] ?? null)) {
                throw new \RuntimeException("File lịch sử URL Coolmate không phải JSON hợp lệ: {$path}");
            }

            $crawledAt = (new DateTimeImmutable)->format(DATE_ATOM);
            foreach ($articles as $article) {
                $sourceUrl = (string) ($article['source_url'] ?? '');
                if ($sourceUrl === '') {
                    continue;
                }

                $registry['urls'][$sourceUrl] = [
                    'crawled_at' => $crawledAt,
                    'title' => $this->normalizeUtf8((string) ($article['title'] ?? '')),
                    'slug' => $this->normalizeUtf8((string) ($article['slug'] ?? '')),
                ];
            }

            ksort($registry['urls']);
            $json = $this->encodeJson($registry, JSON_PRETTY_PRINT);

            rewind($handle);
            if (! ftruncate($handle, 0) || fwrite($handle, $json.PHP_EOL) === false) {
                throw new \RuntimeException("Không thể cập nhật lịch sử URL Coolmate: {$path}");
            }

            fflush($handle);
            flock($handle, LOCK_UN);
        } finally {
            fclose($handle);
        }
    }

    private function crawledUrlsPath(): string
    {
        return $this->tempRootDirectory().DIRECTORY_SEPARATOR.self::CRAWLED_URLS_FILE;
    }

    /**
     * Tải nhiều trang song song theo từng batch; Playwright chỉ là fallback.
     */
    private function fetchHtmlPayloads(array $urls, array &$errors): array
    {
        $payloads = [];

        foreach (array_chunk($urls, self::PAGE_BATCH_SIZE) as $chunkUrls) {
            try {
                $responses = Http::pool(function (Pool $pool) use ($chunkUrls) {
                    $requests = [];

                    foreach ($chunkUrls as $index => $url) {
                        $key = 'page_'.$index;
                        $requests[$key] = $pool
                            ->as($key)
                            ->withHeaders($this->pageRequestHeaders())
                            ->connectTimeout(8)
                            ->timeout(self::PAGE_TIMEOUT_SECONDS)
                            ->retry(1, 100)
                            ->get($url);
                    }

                    return $requests;
                });
            } catch (\Throwable $e) {
                $responses = [];
                Log::warning('Coolmate page pool failed', [
                    'error' => $e->getMessage(),
                    'urls' => $chunkUrls,
                ]);
            }

            foreach ($chunkUrls as $index => $url) {
                $response = $responses['page_'.$index] ?? null;

                if (
                    $response instanceof Response
                    && $response->successful()
                    && $this->isUsableHtml($response->body(), self::PAGE_SELECTOR)
                ) {
                    $payloads[$url] = $response->body();

                    continue;
                }

                try {
                    $payloads[$url] = $this->fetchHtmlWithPlaywright($url, self::PAGE_SELECTOR);
                } catch (\Throwable $e) {
                    $status = $response instanceof Response ? "HTTP {$response->status()}; " : '';
                    $errors[$url] = $status.$e->getMessage();
                }
            }
        }

        return $payloads;
    }

    private function fetchHtmlWithPlaywright(string $url, string $waitForSelector): string
    {
        $serviceUrl = rtrim(env('PLAYWRIGHT_SERVICE_URL', 'http://localhost:3001'), '/');
        $response = Http::connectTimeout(8)
            ->timeout(120)
            ->post($serviceUrl.'/crawl', [
                'url' => $url,
                'waitForSelector' => $waitForSelector,
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException("Playwright HTTP {$response->status()}");
        }

        $data = $response->json();
        $html = is_array($data) ? (string) ($data['html'] ?? '') : '';

        if (
            ! is_array($data)
            || ($data['success'] ?? false) !== true
            || ! $this->isUsableHtml($html, $waitForSelector)
        ) {
            throw new \RuntimeException(
                'Playwright không trả về HTML bài viết hợp lệ: '
                .(is_array($data) ? ($data['error'] ?? 'unknown error') : 'invalid JSON')
            );
        }

        return $html;
    }

    private function extractArticleData(string $html, string $sourceUrl): array
    {
        $html = $this->normalizeUtf8($html);
        $dom = $this->loadHtml($html);
        $xpath = new DOMXPath($dom);

        $rawTitle = $this->firstNodeText(
            $xpath,
            "//h1[contains(concat(' ', normalize-space(@class), ' '), ' entry-title ')]"
        );
        if ($rawTitle === '') {
            $rawTitle = $this->metaContent($xpath, 'property', 'og:title');
        }
        $rawTitle = $this->normalizeText($rawTitle);

        if ($rawTitle === '') {
            throw new \RuntimeException('Không tìm thấy tiêu đề bài viết.');
        }

        $title = $this->transformTitle($rawTitle);
        $imageFallbackTitle = $this->transformText($rawTitle);

        $slug = Str::slug($title, '-');
        if ($slug === '') {
            throw new \RuntimeException('Không thể tạo slug từ tiêu đề bài viết.');
        }

        $contentNode = $xpath->query(
            "//article//div[contains(concat(' ', normalize-space(@class), ' '), ' entry-content ')"
            ." and contains(concat(' ', normalize-space(@class), ' '), ' single-page ')]"
        )->item(0);

        if (! $contentNode instanceof DOMElement) {
            $contentNode = $xpath->query(
                "//div[contains(concat(' ', normalize-space(@class), ' '), ' entry-content ')]"
            )->item(0);
        }

        if (! $contentNode instanceof DOMElement) {
            throw new \RuntimeException('Không tìm thấy nội dung bài viết.');
        }

        $this->removeUnwantedContentNodes($contentNode);
        $extraImages = $this->collectContentImages(
            $contentNode,
            $sourceUrl,
            $imageFallbackTitle
        );
        $this->transformContentText($contentNode);
        $this->cleanContentMarkup($contentNode);
        $content = $this->innerHtml($contentNode);

        if (trim(strip_tags($content)) === '') {
            throw new \RuntimeException('Nội dung bài viết rỗng sau khi xử lý.');
        }

        $description = $this->transformText(
            $this->metaContent($xpath, 'property', 'og:description')
                ?: $this->metaContent($xpath, 'name', 'description')
        );
        $mainImageUrl = $this->absoluteUrl(
            $this->metaContent($xpath, 'property', 'og:image'),
            $sourceUrl
        );
        $canonical = $this->linkHref($xpath, 'canonical') ?: $sourceUrl;
        $publishedAt = $this->normalizePublishedAt(
            $this->metaContent($xpath, 'property', 'article:published_time')
        );
        $keywords = $this->transformText($this->metaContent($xpath, 'name', 'keywords'));
        $tags = array_values(array_unique(array_filter(array_map(
            fn (string $tag): string => $this->transformText($tag),
            $this->metaContents($xpath, 'property', 'article:tag')
        ))));

        return [
            'source_url' => $sourceUrl,
            'title' => $title,
            'slug' => $slug,
            'content' => $content,
            'excerpt' => $description,
            'meta_title' => $this->transformText(
                $this->metaContent($xpath, 'property', 'og:title')
            ) ?: $title,
            'meta_description' => $description,
            'meta_keywords' => $keywords,
            'meta_canonical' => $canonical,
            'published_at' => $publishedAt,
            'category_slug' => Str::slug(
                $this->transformText(
                    $this->metaContent($xpath, 'property', 'article:section')
                ),
                '-'
            ),
            'tags' => $tags,
            'main_image_url' => $mainImageUrl,
            'main_image_alt' => $this->transformText(
                $this->metaContent($xpath, 'property', 'og:image:alt')
            ) ?: $imageFallbackTitle,
            'extra_images' => $extraImages,
        ];
    }

    private function removeUnwantedContentNodes(DOMElement $contentNode): void
    {
        $xpath = new DOMXPath($contentNode->ownerDocument);
        $nodes = $xpath->query('.//script|.//style|.//noscript|.//iframe|.//form|.//button|.//svg', $contentNode);

        if ($nodes === false) {
            return;
        }

        $toRemove = [];
        foreach ($nodes as $node) {
            $toRemove[] = $node;
        }

        foreach ($toRemove as $node) {
            $node->parentNode?->removeChild($node);
        }
    }

    private function collectContentImages(
        DOMElement $contentNode,
        string $sourceUrl,
        string $fallbackTitle
    ): array {
        $xpath = new DOMXPath($contentNode->ownerDocument);
        $nodes = $xpath->query('.//img', $contentNode);
        $images = [];

        if ($nodes === false) {
            return $images;
        }

        foreach ($nodes as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }

            $imageUrl = $this->imageSource($node, $sourceUrl);
            if ($imageUrl === null) {
                $node->parentNode?->removeChild($node);

                continue;
            }

            $order = count($images) + 1;
            $placeholder = "__COOLMATE_EXTRA_IMAGE_{$order}__";
            $alt = $this->transformText($node->getAttribute('alt')) ?: $fallbackTitle;
            $rawSlug = Str::slug($alt, '-') ?: Str::slug($fallbackTitle, '-');
            $imageSlug = Str::limit($rawSlug, 70, '');
            $imageSlug = rtrim($imageSlug, '-') ?: 'image-'.$order;

            $node->setAttribute('src', $placeholder);
            $node->setAttribute('alt', $alt);
            $node->setAttribute('title', $alt);
            foreach (['srcset', 'data-src', 'data-lazy-src', 'data-original', 'data-srcset', 'loading'] as $attribute) {
                $node->removeAttribute($attribute);
            }

            $images[] = [
                'order' => $order,
                'url' => $imageUrl,
                'alt' => $alt,
                'image_slug' => $imageSlug,
                'placeholder' => $placeholder,
            ];
        }

        return $images;
    }

    private function transformContentText(DOMElement $contentNode): void
    {
        $xpath = new DOMXPath($contentNode->ownerDocument);
        $textNodes = $xpath->query('.//text()', $contentNode);

        if ($textNodes === false) {
            return;
        }

        foreach ($textNodes as $textNode) {
            $textNode->nodeValue = $this->replaceHistoricalYears(
                $this->replaceBrandNames((string) $textNode->nodeValue)
            );
        }
    }

    private function cleanContentMarkup(DOMElement $contentNode): void
    {
        $allowedAttributes = [
            'img' => ['src', 'alt', 'title'],
            'th' => ['colspan', 'rowspan'],
            'td' => ['colspan', 'rowspan'],
        ];
        $allowedTags = [
            'p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
            'ul', 'ol', 'li', 'strong', 'em', 'b', 'i', 'u',
            'img', 'br', 'blockquote', 'table', 'thead',
            'tbody', 'tr', 'th', 'td', 'figure', 'figcaption', 'hr',
        ];

        $xpath = new DOMXPath($contentNode->ownerDocument);
        $nodes = $xpath->query('.//*', $contentNode);
        $elements = [];

        if ($nodes !== false) {
            foreach ($nodes as $node) {
                if ($node instanceof DOMElement) {
                    $elements[] = $node;
                }
            }
        }

        foreach (array_reverse($elements) as $element) {
            $tagName = strtolower($element->tagName);

            if ($tagName === 'a') {
                $this->replaceAnchorWithEm($element);

                continue;
            }

            if (! in_array($tagName, $allowedTags, true)) {
                $this->unwrapElement($element);

                continue;
            }

            $keepAttributes = $allowedAttributes[$tagName] ?? [];
            $removeAttributes = [];
            foreach ($element->attributes as $attribute) {
                if (! in_array(strtolower($attribute->name), $keepAttributes, true)) {
                    $removeAttributes[] = $attribute->name;
                }
            }
            foreach ($removeAttributes as $attributeName) {
                $element->removeAttribute($attributeName);
            }
        }
    }

    private function replaceAnchorWithEm(DOMElement $anchor): void
    {
        $parent = $anchor->parentNode;
        if (! $parent instanceof DOMNode) {
            return;
        }

        $emphasis = $anchor->ownerDocument->createElement('em');
        while ($anchor->firstChild) {
            $emphasis->appendChild($anchor->firstChild);
        }

        $parent->replaceChild($emphasis, $anchor);
    }

    private function unwrapElement(DOMElement $element): void
    {
        $parent = $element->parentNode;
        if (! $parent instanceof DOMNode) {
            return;
        }

        while ($element->firstChild) {
            $parent->insertBefore($element->firstChild, $element);
        }

        $parent->removeChild($element);
    }

    private function buildImageJobs(array $articles, bool $downloadMainImage = true): array
    {
        $jobs = [];
        $clearedSlugs = [];

        foreach ($articles as $articleIndex => $article) {
            $slug = $article['slug'];

            if (! isset($clearedSlugs[$slug])) {
                $this->clearStoredArticleImages($slug);
                $clearedSlugs[$slug] = true;
            }

            if ($downloadMainImage && ! empty($article['main_image_url'])) {
                $jobs[] = [
                    'key' => "article_{$articleIndex}_main",
                    'article_index' => $articleIndex,
                    'type' => 'main',
                    'order' => 0,
                    'slug' => $slug,
                    'file_slug' => $slug,
                    'url' => $article['main_image_url'],
                    'alt' => $article['main_image_alt'],
                    'source_url' => $article['source_url'],
                ];
            }

            foreach ($article['extra_images'] as $image) {
                $jobs[] = [
                    'key' => "article_{$articleIndex}_extra_{$image['order']}",
                    'article_index' => $articleIndex,
                    'type' => 'extra',
                    'order' => $image['order'],
                    'slug' => $slug,
                    'file_slug' => $image['image_slug'],
                    'url' => $image['url'],
                    'alt' => $image['alt'],
                    'source_url' => $article['source_url'],
                ];
            }
        }

        return $jobs;
    }

    /**
     * Tải ảnh song song theo batch và ghi đè đúng tên file quy định.
     */
    private function downloadImagesInBatches(array $jobs): array
    {
        $results = [];

        foreach (array_chunk($jobs, self::IMAGE_BATCH_SIZE) as $chunkJobs) {
            try {
                $responses = Http::pool(function (Pool $pool) use ($chunkJobs) {
                    $requests = [];

                    foreach ($chunkJobs as $index => $job) {
                        $key = 'image_'.$index;
                        $requests[$key] = $pool
                            ->as($key)
                            ->withHeaders([
                                // Không ưu tiên WebP/AVIF để CDN trả đúng định dạng ảnh nguồn.
                                'Accept' => 'image/*,*/*;q=0.8',
                                'Referer' => $job['source_url'],
                                'User-Agent' => $this->pageRequestHeaders()['User-Agent'],
                            ])
                            ->connectTimeout(8)
                            ->timeout(self::IMAGE_TIMEOUT_SECONDS)
                            ->retry(1, 100)
                            ->get($job['url']);
                    }

                    return $requests;
                });
            } catch (\Throwable $e) {
                $responses = [];
                Log::warning('Coolmate image pool failed', [
                    'error' => $e->getMessage(),
                ]);
            }

            foreach ($chunkJobs as $index => $job) {
                $response = $responses['image_'.$index] ?? null;

                if (! $response instanceof Response || ! $this->isValidImageResponse($response, $job['url'])) {
                    $results[$job['key']] = [
                        'status' => 'failed',
                        'error' => $response instanceof Response
                            ? "HTTP {$response->status()} hoặc dữ liệu không phải ảnh"
                            : 'Không nhận được phản hồi ảnh',
                        'relative_path' => null,
                        'absolute_path' => null,
                    ];

                    continue;
                }

                $extension = $this->imageExtension($job['url'], $response);
                $fileSlug = Str::limit($job['file_slug'], 70, '');
                $fileSlug = rtrim($fileSlug, '-') ?: 'image';

                $fileName = $job['type'] === 'main'
                    ? "{$fileSlug}.{$extension}"
                    : "{$fileSlug}-{$job['order']}.{$extension}";
                $directory = $job['type'] === 'main'
                    ? $this->mainImageDirectory()
                    : $this->extraImageDirectory();
                $relativeDirectory = $job['type'] === 'main'
                    ? 'storage/app/tmp/coolmate/main'
                    : 'storage/app/tmp/coolmate/extra';
                $fullPath = $directory.DIRECTORY_SEPARATOR.$fileName;

                try {
                    if (! is_dir($directory)) {
                        @mkdir($directory, 0755, true);
                    }

                    $this->deleteImageVariants($directory, $fileSlug, $job['type'], (int) $job['order']);

                    if (@file_put_contents($fullPath, $response->body()) === false) {
                        $results[$job['key']] = [
                            'status' => 'failed',
                            'error' => 'Không thể ghi file ảnh vào thư mục tạm',
                            'relative_path' => null,
                            'absolute_path' => null,
                        ];

                        continue;
                    }

                    @chmod($fullPath, 0644);
                    $results[$job['key']] = [
                        'status' => 'downloaded',
                        'error' => null,
                        'file_name' => $fileName,
                        'relative_path' => $relativeDirectory.'/'.$fileName,
                        'absolute_path' => $fullPath,
                        'extension' => $extension,
                    ];
                } catch (\Throwable $e) {
                    $results[$job['key']] = [
                        'status' => 'failed',
                        'error' => 'Lỗi lưu file ảnh: '.$e->getMessage(),
                        'relative_path' => null,
                        'absolute_path' => null,
                    ];
                }
            }
        }

        return $results;
    }

    private function buildCsvRows(
        array $articles,
        array $imageJobs,
        array $imageResults,
        array &$results,
        bool $downloadMainImage = true
    ): array {
        $jobsByArticle = [];
        $imageDetailsByArticle = [];

        foreach ($imageJobs as $job) {
            $imageResult = $imageResults[$job['key']] ?? [
                'status' => 'failed',
                'error' => 'Không có kết quả tải ảnh',
                'relative_path' => null,
                'absolute_path' => null,
            ];
            $jobsByArticle[$job['article_index']][$job['key']] = $imageResult;

            if ($imageResult['status'] === 'downloaded') {
                $results['image_downloaded_count']++;
                $results[$job['type'] === 'main' ? 'main_image_count' : 'extra_image_count']++;
            } else {
                $results['warnings'][] = "Không tải được ảnh {$job['url']}: {$imageResult['error']}";
            }

            $imageDetailsByArticle[$job['article_index']][] = [
                'article_url' => $job['source_url'],
                'article_slug' => $job['slug'],
                'image_slug' => $job['file_slug'],
                'type' => $job['type'] === 'main' ? 'main' : 'extra',
                'order' => $job['order'],
                'original_url' => $job['url'],
                'relative_path' => $imageResult['relative_path'] ?? '',
                'absolute_path' => $imageResult['absolute_path'] ?? '',
                'alt' => $job['alt'],
                'status' => $imageResult['status'],
                'error' => $imageResult['error'] ?? '',
            ];
        }

        $rows = [];
        $crawledAt = (new DateTimeImmutable)->format('Y-m-d H:i:s');

        foreach ($articles as $articleIndex => $article) {
            if (! $downloadMainImage && ! empty($article['main_image_url'])) {
                $imageDetailsByArticle[$articleIndex][] = [
                    'article_url' => $article['source_url'],
                    'article_slug' => $article['slug'],
                    'image_slug' => $article['slug'],
                    'type' => 'main',
                    'order' => 0,
                    'original_url' => $article['main_image_url'],
                    'relative_path' => '',
                    'absolute_path' => '',
                    'alt' => $article['main_image_alt'],
                    'status' => 'skipped_by_option',
                    'error' => '',
                ];
            }

            $content = $article['content'];
            $downloadedExtraPaths = [];
            $extraImagePaths = [];
            $mainImagePath = $article['main_image_url'] ?? '';

            $mainKey = "article_{$articleIndex}_main";
            if (($jobsByArticle[$articleIndex][$mainKey]['status'] ?? null) === 'downloaded') {
                $mainImagePath = $jobsByArticle[$articleIndex][$mainKey]['relative_path'];
            }

            foreach ($article['extra_images'] as $image) {
                $extraKey = "article_{$articleIndex}_extra_{$image['order']}";
                $imageResult = $jobsByArticle[$articleIndex][$extraKey] ?? null;
                $replacement = $image['url'];

                if (($imageResult['status'] ?? null) === 'downloaded') {
                    $replacement = $imageResult['relative_path'];
                    $downloadedExtraPaths[] = $replacement;
                }

                $extraImagePaths[] = $replacement;
                $content = str_replace($image['placeholder'], $replacement, $content);
            }

            $rows[] = [
                '',
                $article['title'],
                $article['slug'],
                $article['category_slug'],
                $content,
                $article['excerpt'],
                $mainImagePath,
                implode(', ', $extraImagePaths),
                $article['main_image_alt'],
                'draft',
                0,
                implode(', ', $article['tags']),
                $article['meta_title'],
                $article['meta_description'],
                $article['meta_keywords'],
                $article['meta_canonical'],
                '',
                $article['published_at'],
                $article['source_url'],
                $article['main_image_url'] ?? '',
                $this->encodeJson($downloadedExtraPaths),
                count($article['extra_images']),
                $this->encodeJson($imageDetailsByArticle[$articleIndex] ?? []),
                $crawledAt,
            ];

            $results['success']++;
            $results['posts'][] = [
                'title' => $article['title'],
                'slug' => $article['slug'],
                'source_url' => $article['source_url'],
                'main_image' => $mainImagePath,
                'extra_image_count' => count($article['extra_images']),
                'content_length' => mb_strlen($content, 'UTF-8'),
            ];
        }

        return $rows;
    }

    private function createCsv(array $rows, ?string $targetFileName = null): string
    {
        return $this->writeCsv($rows, $targetFileName);
    }

    private function writeCsv(array $rows, ?string $targetFileName = null): string
    {
        $defaultHeaders = [
            'ID',
            'Tiêu đề',
            'Slug',
            'Danh mục (Slug)',
            'Nội dung',
            'Tóm tắt',
            'Thumbnail URL',
            'Ảnh phụ',
            'Alt ảnh',
            'Trạng thái',
            'Nổi bật',
            'Tags (phẩy)',
            'Meta Title',
            'Meta Description',
            'Meta Keywords',
            'Meta Canonical',
            'Tác giả (Email)',
            'Ngày xuất bản',
            'URL nguồn',
            'Ảnh chính gốc',
            'Ảnh phụ đã lưu (JSON)',
            'Số ảnh phụ',
            'Chi tiết ảnh (JSON)',
            'Ngày crawl',
        ];

        if ($targetFileName === null || trim($targetFileName) === '') {
            $timestamp = (new DateTimeImmutable)->format('Y-m-d_H-i-s-u');
            $fileName = "coolmate_posts_{$timestamp}.csv";
        } else {
            $fileName = basename($targetFileName);
        }

        $path = $this->tempRootDirectory().DIRECTORY_SEPARATOR.$fileName;

        if (! is_file($path)) {
            $handle = fopen($path, 'wb');
            if ($handle === false) {
                throw new \RuntimeException("Không thể tạo file CSV: {$path}");
            }

            [$headers, $splitRows] = $this->splitLongContentColumns($defaultHeaders, $rows);

            try {
                if (fwrite($handle, "\xEF\xBB\xBF") === false) {
                    throw new \RuntimeException("Không thể ghi BOM UTF-8 vào file CSV: {$path}");
                }

                $this->writeCsvRow($handle, $headers, $path);
                foreach ($splitRows as $row) {
                    $this->writeCsvRow($handle, $row, $path);
                }
            } finally {
                fclose($handle);
            }

            $this->validateCsvFile($path, count($headers), count($splitRows));

            return $fileName;
        }

        $readHandle = fopen($path, 'rb');
        if ($readHandle === false) {
            throw new \RuntimeException("Không thể đọc header CSV: {$path}");
        }

        $headers = fgetcsv($readHandle, null, ',', '"', '');
        fclose($readHandle);

        if (! is_array($headers) || empty($headers)) {
            throw new \RuntimeException("File CSV tồn tại nhưng không có header hợp lệ: {$path}");
        }

        $headers[0] = (string) preg_replace('/^\xEF\xBB\xBF/', '', (string) $headers[0]);
        $formattedRows = $this->formatRowsForExistingHeaders($headers, $rows);

        $appendHandle = fopen($path, 'ab');
        if ($appendHandle === false) {
            throw new \RuntimeException("Không thể mở file CSV để ghi nối tiếp: {$path}");
        }

        try {
            foreach ($formattedRows as $row) {
                $this->writeCsvRow($appendHandle, $row, $path);
            }
        } finally {
            fclose($appendHandle);
        }

        return $fileName;
    }

    private function formatRowsForExistingHeaders(array $headers, array $rows): array
    {
        $contentColumnIndex = array_search('Nội dung', $headers, true);
        if ($contentColumnIndex === false) {
            $contentColumnIndex = 4;
        }

        $extraContentIndexes = [];
        for ($i = 2; ; $i++) {
            $idx = array_search("Nội dung {$i}", $headers, true);
            if ($idx === false) {
                break;
            }
            $extraContentIndexes[] = $idx;
        }

        $expectedColumnCount = count($headers);
        $formattedRows = [];

        foreach ($rows as $row) {
            $content = (string) ($row[$contentColumnIndex] ?? '');
            $chunks = [];
            $contentLength = mb_strlen($content, 'UTF-8');

            for ($offset = 0; $offset < $contentLength; $offset += self::CSV_CELL_CHARACTER_LIMIT) {
                $chunks[] = mb_substr(
                    $content,
                    $offset,
                    self::CSV_CELL_CHARACTER_LIMIT,
                    'UTF-8'
                );
            }

            if ($chunks === []) {
                $chunks = [''];
            }

            $row[$contentColumnIndex] = $chunks[0];
            $formattedRow = array_pad($row, $expectedColumnCount, '');

            foreach ($extraContentIndexes as $chunkOffset => $colIdx) {
                $formattedRow[$colIdx] = $chunks[$chunkOffset + 1] ?? '';
            }

            if (count($chunks) > count($extraContentIndexes) + 1) {
                $lastColIdx = empty($extraContentIndexes) ? $contentColumnIndex : end($extraContentIndexes);
                $remainingContent = implode('', array_slice($chunks, count($extraContentIndexes) + 1));
                $formattedRow[$lastColIdx] .= $remainingContent;
            }

            $formattedRows[] = array_slice($formattedRow, 0, $expectedColumnCount);
        }

        return $formattedRows;
    }

    private function splitLongContentColumns(array $headers, array $rows): array
    {
        $contentColumnIndex = array_search('Nội dung', $headers, true);
        if ($contentColumnIndex === false) {
            throw new \RuntimeException('Không tìm thấy cột Nội dung trong CSV.');
        }

        $chunkedRows = [];
        $maximumChunkCount = 1;

        foreach ($rows as $row) {
            $content = (string) ($row[$contentColumnIndex] ?? '');
            $chunks = [];
            $contentLength = mb_strlen($content, 'UTF-8');

            for ($offset = 0; $offset < $contentLength; $offset += self::CSV_CELL_CHARACTER_LIMIT) {
                $chunks[] = mb_substr(
                    $content,
                    $offset,
                    self::CSV_CELL_CHARACTER_LIMIT,
                    'UTF-8'
                );
            }

            if ($chunks === []) {
                $chunks = [''];
            }

            $row[$contentColumnIndex] = $chunks[0];
            $row['_content_chunks'] = array_slice($chunks, 1);
            $maximumChunkCount = max($maximumChunkCount, count($chunks));
            $chunkedRows[] = $row;
        }

        for ($chunkNumber = 2; $chunkNumber <= $maximumChunkCount; $chunkNumber++) {
            $headers[] = "Nội dung {$chunkNumber}";
        }

        foreach ($chunkedRows as &$row) {
            $extraChunks = $row['_content_chunks'];
            unset($row['_content_chunks']);

            for ($chunkNumber = 2; $chunkNumber <= $maximumChunkCount; $chunkNumber++) {
                $row[] = $extraChunks[$chunkNumber - 2] ?? '';
            }
        }
        unset($row);

        return [$headers, $chunkedRows];
    }

    /**
     * @param  resource  $handle
     */
    private function writeCsvRow($handle, array $row, string $path): void
    {
        $values = array_map(
            fn (mixed $value): string => $this->sanitizeCsvText((string) $value),
            $row
        );

        if (fputcsv($handle, $values, ',', '"', '', "\r\n") === false) {
            throw new \RuntimeException("Không thể ghi dữ liệu vào file CSV: {$path}");
        }
    }

    private function validateCsvFile(
        string $path,
        int $expectedColumnCount,
        int $expectedDataRowCount
    ): void {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new \RuntimeException("Không thể kiểm tra file CSV: {$path}");
        }

        $rowNumber = 0;

        try {
            while (($row = fgetcsv($handle, null, ',', '"', '')) !== false) {
                $rowNumber++;

                if (count($row) !== $expectedColumnCount) {
                    throw new \RuntimeException(
                        "File CSV lỗi cấu trúc tại dòng dữ liệu {$rowNumber}: "
                        .count($row)." cột, cần {$expectedColumnCount} cột."
                    );
                }

                foreach ($row as $columnIndex => $value) {
                    if (str_contains($value, "\r") || str_contains($value, "\n")) {
                        throw new \RuntimeException(
                            "File CSV còn ký tự xuống dòng tại bản ghi {$rowNumber}, "
                            ."cột ".($columnIndex + 1).'.'
                        );
                    }
                }
            }
        } catch (\Throwable $e) {
            @unlink($path);

            throw $e;
        } finally {
            fclose($handle);
        }

        $actualDataRowCount = max(0, $rowNumber - 1);
        if ($actualDataRowCount !== $expectedDataRowCount) {
            @unlink($path);

            throw new \RuntimeException(
                "File CSV lỗi số bản ghi: {$actualDataRowCount}, cần {$expectedDataRowCount}."
            );
        }
    }

    private function clearStoredArticleImages(string $slug): void
    {
        foreach ([$this->mainImageDirectory(), $this->extraImageDirectory()] as $directory) {
            foreach (glob($directory.DIRECTORY_SEPARATOR.$slug.'*') ?: [] as $path) {
                $fileName = basename($path);
                $pattern = $directory === $this->mainImageDirectory()
                    ? '/^'.preg_quote($slug, '/').'\.[a-z0-9]+$/i'
                    : '/^'.preg_quote($slug, '/').'-[0-9]+\.[a-z0-9]+$/i';

                if (is_file($path) && preg_match($pattern, $fileName)) {
                    @unlink($path);
                }
            }
        }
    }

    private function deleteImageVariants(
        string $directory,
        string $slug,
        string $type,
        int $order
    ): void {
        $prefix = $type === 'main' ? $slug : "{$slug}-{$order}";

        foreach (glob($directory.DIRECTORY_SEPARATOR.$prefix.'.*') ?: [] as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }

    private function ensureTempDirectories(): void
    {
        foreach ([
            $this->tempRootDirectory(),
            $this->mainImageDirectory(),
            $this->extraImageDirectory(),
        ] as $directory) {
            if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
                throw new \RuntimeException("Không thể tạo thư mục {$directory}");
            }
        }
    }

    protected function tempRootDirectory(): string
    {
        return storage_path('app/tmp/coolmate');
    }

    protected function mainImageDirectory(): string
    {
        return $this->tempRootDirectory().DIRECTORY_SEPARATOR.'main';
    }

    protected function extraImageDirectory(): string
    {
        return $this->tempRootDirectory().DIRECTORY_SEPARATOR.'extra';
    }

    private function normalizePostUrl(string $url): ?string
    {
        if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        $parts = parse_url($url);
        if (! is_array($parts)) {
            return null;
        }

        $scheme = strtolower($parts['scheme'] ?? '');
        $host = strtolower(rtrim($parts['host'] ?? '', '.'));

        if (
            ! in_array($scheme, ['http', 'https'], true)
            || ! in_array($host, ['coolmate.me', 'www.coolmate.me'], true)
            || isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['port'])
        ) {
            return null;
        }

        $path = $parts['path'] ?? '/';
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        return 'https://www.coolmate.me'.($path !== '' ? $path : '/');
    }

    private function pageRequestHeaders(): array
    {
        return [
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language' => 'vi-VN,vi;q=0.9,en;q=0.8',
            'Cache-Control' => 'no-cache',
            'Referer' => 'https://www.coolmate.me/blog',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
        ];
    }

    private function isUsableHtml(string $html, ?string $selector = null): bool
    {
        if (strlen(trim($html)) < 200) {
            return false;
        }

        foreach (['Just a moment', 'cf-browser-verification', 'cf-chl-'] as $marker) {
            if (stripos($html, $marker) !== false) {
                return false;
            }
        }

        if ($selector !== self::PAGE_SELECTOR) {
            return true;
        }

        $dom = $this->loadHtml($html);
        $xpath = new DOMXPath($dom);

        return $xpath->query(
            "//div[contains(concat(' ', normalize-space(@class), ' '), ' entry-content ')"
            ." and contains(concat(' ', normalize-space(@class), ' '), ' single-page ')]"
        )->length > 0;
    }

    private function isValidImageResponse(Response $response, string $url): bool
    {
        if (! $response->successful() || $response->body() === '') {
            return false;
        }

        $contentType = strtolower((string) $response->header('Content-Type'));
        if (str_starts_with($contentType, 'image/')) {
            return true;
        }

        $extension = strtolower(pathinfo((string) parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));

        return in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif'], true)
            && ! str_contains($contentType, 'text/html');
    }

    private function imageExtension(string $url, Response $response): string
    {
        $urlExtension = strtolower(pathinfo((string) parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
        $mime = strtolower(trim(explode(';', (string) $response->header('Content-Type'))[0]));
        $mimeExtension = match ($mime) {
            'image/jpeg' => in_array($urlExtension, ['jpg', 'jpeg'], true)
                ? $urlExtension
                : 'jpg',
            'image/png' => 'png',
            'image/apng' => 'apng',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            'image/avif' => 'avif',
            'image/svg+xml' => 'svg',
            'image/bmp' => 'bmp',
            'image/tiff' => in_array($urlExtension, ['tif', 'tiff'], true)
                ? $urlExtension
                : 'tiff',
            'image/x-icon', 'image/vnd.microsoft.icon' => 'ico',
            default => null,
        };

        if ($mimeExtension !== null) {
            return $mimeExtension;
        }

        if (
            in_array(
                $urlExtension,
                ['jpg', 'jpeg', 'png', 'apng', 'webp', 'gif', 'avif', 'svg', 'bmp', 'tif', 'tiff', 'ico'],
                true
            )
        ) {
            return $urlExtension;
        }

        throw new \RuntimeException("Không xác định được định dạng ảnh gốc: {$url}");
    }

    private function imageSource(DOMElement $image, string $baseUrl): ?string
    {
        foreach (['data-src', 'data-lazy-src', 'data-original', 'src'] as $attribute) {
            $value = trim($image->getAttribute($attribute));
            if ($value !== '' && ! str_starts_with(strtolower($value), 'data:')) {
                return $this->absoluteUrl($value, $baseUrl);
            }
        }

        foreach (['data-srcset', 'srcset'] as $attribute) {
            $srcset = trim($image->getAttribute($attribute));
            if ($srcset === '') {
                continue;
            }

            $firstCandidate = trim(explode(',', $srcset)[0]);
            $url = trim(preg_split('/\s+/', $firstCandidate)[0] ?? '');
            if ($url !== '') {
                return $this->absoluteUrl($url, $baseUrl);
            }
        }

        return null;
    }

    private function absoluteUrl(string $url, string $baseUrl): ?string
    {
        $url = trim($url);
        if ($url === '' || str_starts_with(strtolower($url), 'data:')) {
            return null;
        }

        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }

        $base = parse_url($baseUrl);
        if (! is_array($base) || empty($base['host'])) {
            return null;
        }

        $scheme = $base['scheme'] ?? 'https';
        if (str_starts_with($url, '//')) {
            return $scheme.':'.$url;
        }

        if (str_starts_with($url, '/')) {
            return $scheme.'://'.$base['host'].$url;
        }

        $basePath = dirname($base['path'] ?? '/');

        return $scheme.'://'.$base['host'].rtrim($basePath, '/').'/'.ltrim($url, '/');
    }

    private function loadHtml(string $html): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        @$dom->loadHTML(
            '<?xml encoding="UTF-8">'.$html,
            LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NONET | LIBXML_COMPACT
        );

        return $dom;
    }

    private function metaContent(DOMXPath $xpath, string $attribute, string $value): string
    {
        $node = $xpath->query(
            "//meta[translate(@{$attribute}, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz')='"
            .strtolower($value)."']/@content"
        )->item(0);

        return $node ? trim((string) $node->nodeValue) : '';
    }

    private function metaContents(DOMXPath $xpath, string $attribute, string $value): array
    {
        $nodes = $xpath->query(
            "//meta[translate(@{$attribute}, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz')='"
            .strtolower($value)."']/@content"
        );
        $values = [];

        if ($nodes !== false) {
            foreach ($nodes as $node) {
                $content = $this->normalizeText((string) $node->nodeValue);
                if ($content !== '') {
                    $values[] = $content;
                }
            }
        }

        return array_values(array_unique($values));
    }

    private function linkHref(DOMXPath $xpath, string $relation): string
    {
        $node = $xpath->query(
            "//link[contains(concat(' ', normalize-space(@rel), ' '), ' {$relation} ')]/@href"
        )->item(0);

        return $node ? trim((string) $node->nodeValue) : '';
    }

    private function firstNodeText(DOMXPath $xpath, string $query): string
    {
        $node = $xpath->query($query)->item(0);

        return $node ? trim((string) $node->textContent) : '';
    }

    private function normalizePublishedAt(string $value): string
    {
        if ($value === '') {
            return '';
        }

        try {
            $date = new DateTimeImmutable($value);
            $year = (int) $date->format('Y');

            if ($year >= 1990 && $year <= 2025) {
                $date = $date->setDate(
                    2026,
                    (int) $date->format('m'),
                    (int) $date->format('d')
                );
            }

            return $date->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return $this->replaceHistoricalYears($value);
        }
    }

    private function transformText(string $value): string
    {
        return $this->normalizeText(
            $this->replaceHistoricalYears(
                $this->replaceBrandNames($value)
            )
        );
    }

    private function transformTitle(string $value): string
    {
        $value = $this->replaceBrandNames($this->normalizeText($value));
        $value = (string) preg_replace(
            '/(?<!\d)(?:199[0-9]|20[01][0-9]|202[0-6])(?!\d)/u',
            '',
            $value
        );
        $value = (string) preg_replace('/\s+/u', ' ', $value);

        return trim($value, " \t\n\r\0\x0B-–—|:,");
    }

    private function replaceBrandNames(string $value): string
    {
        return (string) preg_replace(
            '/(?:coolmate(?:\.me)?|cool(?:[\s\x{00A0}]+)?blog)/iu',
            'Khánh Beauty',
            $value
        );
    }

    private function replaceHistoricalYears(string $value): string
    {
        return (string) preg_replace(
            '/(?<!\d)(?:199[0-9]|20[01][0-9]|202[0-5])(?!\d)/u',
            '2026',
            $value
        );
    }

    private function normalizeText(string $value): string
    {
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim((string) preg_replace('/\s+/u', ' ', $value));
    }

    private function innerHtml(DOMElement $element): string
    {
        $html = '';
        foreach ($element->childNodes as $childNode) {
            $html .= $element->ownerDocument->saveHTML($childNode);
        }

        return trim($html);
    }

    private function sanitizeCsvText(string $value): string
    {
        $value = (string) preg_replace(
            '/[^\P{C}\t\r\n]/u',
            '',
            $this->normalizeUtf8($value)
        );

        return (string) preg_replace('/\R+/u', ' ', $value);
    }

    private function normalizeUtf8(string $value): string
    {
        return mb_check_encoding($value, 'UTF-8')
            ? $value
            : mb_convert_encoding($value, 'UTF-8', 'UTF-8');
    }

    private function encodeJson(mixed $value, int $flags = 0): string
    {
        return json_encode(
            $value,
            $flags
                | JSON_UNESCAPED_SLASHES
                | JSON_UNESCAPED_UNICODE
                | JSON_INVALID_UTF8_SUBSTITUTE
                | JSON_THROW_ON_ERROR
        );
    }
}
