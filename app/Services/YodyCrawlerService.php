<?php

namespace App\Services;

use App\Models\Category;
use App\Models\YodyTempProduct;
use Illuminate\Http\Client\Pool;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class YodyCrawlerService
{
    private const PRODUCT_FETCH_CONCURRENCY = 8;
    private const IMAGE_FETCH_CONCURRENCY = 12;
    private const PRODUCT_FETCH_TIMEOUT = 25;
    private const IMAGE_FETCH_TIMEOUT = 20;

    private array $usedSkus = [];
    private ?string $currentCrawlBatch = null;

    public function crawlProductsToImportFile(array $productUrls, array $options = []): array
    {
        $this->usedSkus = [];
        $this->syncLegacyManifestToDatabase();
        $this->currentCrawlBatch = $this->generateCrawlBatch();

        $results = [
            'processed' => 0,
            'success' => 0,
            'failed' => 0,
            'skipped' => 0,
            'products' => [],
            'errors' => [],
            'warnings' => [],
            'image_downloaded_count' => 0,
            'variant_count' => 0,
            'file_name' => null,
            'download_url' => null,
            'temp_directory' => $this->getYodyTempPath(),
            'image_directory' => $this->getYodyCrawlsPath(),
        ];

        $productRows = [];
        $imageRows = [];
        $variantRows = [];
        $faqRows = [];
        $howToRows = [];

        $urls = $this->sanitizeUrls($productUrls, $results['warnings']);
        if (empty($urls)) {
            throw new \InvalidArgumentException('Không có URL sản phẩm Yody hợp lệ để xử lý.');
        }
        $resolvedOptions = $this->resolveOptions($options, $results['warnings']);

        $htmlPayloads = $this->fetchHtmlPayloads($urls);

        foreach ($urls as $url) {
            $results['processed']++;

            try {
                if (!isset($htmlPayloads[$url])) {
                    throw new \RuntimeException("Không thể tải HTML từ Yody cho URL {$url}.");
                }

                $html = $htmlPayloads[$url];
                $productData = $this->extractProductDataFromHtml($html);
                $variants = array_values(array_filter($productData['variants'] ?? [], function ($variant) {
                    return is_array($variant) && !empty($variant['sku']);
                }));
                $productSku = $this->normalizeSku($this->deriveProductSku($variants, $productData));

                if (in_array($productSku, $this->usedSkus, true)) {
                    $results['skipped']++;
                    $results['warnings'][] = "Bỏ qua URL {$url} vì SKU {$productSku} đã xuất hiện trong danh sách crawl hiện tại.";
                    continue;
                }

                if ($this->tempIndexHasSku($productSku)) {
                    $results['skipped']++;
                    $results['warnings'][] = "Bỏ qua URL {$url} vì SKU {$productSku} đã tồn tại trong thư mục tạm Yody.";
                    $this->usedSkus[] = $productSku;
                    continue;
                }

                $this->usedSkus[] = $productSku;

                $mapped = $this->mapProductToImportRows($productData, $url, $resolvedOptions, $results['warnings'], $productSku);

                $productRows[] = $mapped['product'];
                $imageRows = array_merge($imageRows, $mapped['images']);
                $variantRows = array_merge($variantRows, $mapped['variants']);
                $faqRows = array_merge($faqRows, $mapped['faqs']);
                $howToRows = array_merge($howToRows, $mapped['how_tos']);
                $this->storeTempProductIndex($mapped['summary'], $mapped['temp_images'] ?? $mapped['images']);

                $results['success']++;
                $results['variant_count'] += count($mapped['variants']);
                $results['image_downloaded_count'] += count($mapped['images']);
                $results['products'][] = $mapped['summary'];
            } catch (\Throwable $e) {
                $results['failed']++;
                $results['errors'][] = "[$url] " . $e->getMessage();

                Log::error('Yody crawler failed', [
                    'url' => $url,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        if (!empty($productRows)) {
            $fileName = $this->buildImportWorkbook(
                $productRows,
                $imageRows,
                $variantRows,
                $faqRows,
                $howToRows
            );

            $results['file_name'] = $fileName;
            $results['download_url'] = route('admin.yody-crawler.download', ['filename' => $fileName]);
        }

        return $results;
    }

    private function sanitizeUrls(array $productUrls, array &$warnings): array
    {
        $urls = [];

        foreach ($productUrls as $url) {
            $url = trim((string) $url);
            if ($url === '') {
                continue;
            }

            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                $warnings[] = "Bỏ qua URL không hợp lệ: {$url}";
                continue;
            }

            $host = parse_url($url, PHP_URL_HOST) ?: '';
            $path = parse_url($url, PHP_URL_PATH) ?: '';

            if (!str_contains($host, 'yody.vn')) {
                $warnings[] = "Bỏ qua URL không thuộc yody.vn: {$url}";
                continue;
            }

            if (!str_starts_with($path, '/product/')) {
                $warnings[] = "Bỏ qua URL không phải trang sản phẩm Yody: {$url}";
                continue;
            }

            $urls[] = $url;
        }

        return array_values(array_unique($urls));
    }

    private function resolveOptions(array $options, array &$warnings): array
    {
        $primaryCategorySlug = trim((string) ($options['primary_category_slug'] ?? ''));
        $categorySlugs = $this->parseCsvList($options['category_slugs'] ?? '');
        $tagNames = $this->parseCsvList($options['tag_names'] ?? '');

        $lookupSlugs = array_values(array_unique(array_filter(array_merge(
            $primaryCategorySlug !== '' ? [$primaryCategorySlug] : [],
            $categorySlugs
        ))));

        $existingCategorySlugs = Category::query()
            ->whereIn('slug', $lookupSlugs)
            ->pluck('slug')
            ->all();

        $existingMap = array_fill_keys($existingCategorySlugs, true);

        if ($primaryCategorySlug !== '' && !isset($existingMap[$primaryCategorySlug])) {
            $warnings[] = "Bỏ qua primary_category_slug '{$primaryCategorySlug}' vì chưa tồn tại trong hệ thống.";
            $primaryCategorySlug = '';
        }

        $validCategorySlugs = [];
        foreach ($categorySlugs as $slug) {
            if (!isset($existingMap[$slug])) {
                $warnings[] = "Bỏ qua category_slug '{$slug}' vì chưa tồn tại trong hệ thống.";
                continue;
            }

            $validCategorySlugs[] = $slug;
        }

        return [
            'primary_category_slug' => $primaryCategorySlug,
            'category_slugs' => array_values(array_unique($validCategorySlugs)),
            'tag_names' => array_values(array_unique($tagNames)),
            'is_featured' => (bool) ($options['is_featured'] ?? false),
            'is_active' => array_key_exists('is_active', $options) ? (bool) $options['is_active'] : true,
            'use_yody_category_as_tag' => array_key_exists('use_yody_category_as_tag', $options)
                ? (bool) $options['use_yody_category_as_tag']
                : true,
        ];
    }

    private function fetchHtml(string $url): string
    {
        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36',
            'Accept-Language' => 'vi-VN,vi;q=0.9,en-US;q=0.8,en;q=0.7',
            'Referer' => 'https://yody.vn/',
        ])->timeout(self::PRODUCT_FETCH_TIMEOUT)->retry(1, 300)->get($url);

        if (!$response->successful()) {
            throw new \RuntimeException("Không thể tải HTML từ Yody. HTTP {$response->status()}");
        }

        return $response->body();
    }

    private function fetchHtmlPayloads(array $urls): array
    {
        $htmlPayloads = [];

        foreach (array_chunk(array_values($urls), self::PRODUCT_FETCH_CONCURRENCY) as $chunkUrls) {
            $responses = Http::pool(function (Pool $pool) use ($chunkUrls) {
                $requests = [];

                foreach ($chunkUrls as $index => $url) {
                    $key = 'html_' . $index;
                    $requests[$key] = $pool
                        ->as($key)
                        ->withHeaders([
                            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36',
                            'Accept-Language' => 'vi-VN,vi;q=0.9,en-US;q=0.8,en;q=0.7',
                            'Referer' => 'https://yody.vn/',
                        ])
                        ->timeout(self::PRODUCT_FETCH_TIMEOUT)
                        ->retry(1, 300)
                        ->get($url);
                }

                return $requests;
            });

            foreach ($chunkUrls as $index => $url) {
                $key = 'html_' . $index;
                $response = $responses[$key] ?? null;

                if ($response && $response->successful()) {
                    $htmlPayloads[$url] = $response->body();
                }
            }
        }

        return $htmlPayloads;
    }

    private function extractProductDataFromHtml(string $html): array
    {
        $anchor = strpos($html, 'self.PDPData =');
        if ($anchor === false) {
            throw new \RuntimeException('Không tìm thấy self.PDPData trong HTML Yody.');
        }

        $quoteStart = strpos($html, '"', $anchor);
        if ($quoteStart === false) {
            throw new \RuntimeException('Không xác định được vị trí bắt đầu của self.PDPData.');
        }

        $endMarkers = [
            'self.currentVariant =',
            'self.categories =',
            '</script>',
        ];

        $quoteEnd = null;
        foreach ($endMarkers as $marker) {
            $markerPos = strpos($html, $marker, $quoteStart);
            if ($markerPos === false) {
                continue;
            }

            $candidate = $markerPos;
            if ($quoteEnd === null || $candidate < $quoteEnd) {
                $quoteEnd = $candidate;
            }
        }

        if ($quoteEnd === null) {
            throw new \RuntimeException('Không xác định được điểm kết thúc của self.PDPData.');
        }

        $quotedPayload = trim(substr($html, $quoteStart, $quoteEnd - $quoteStart));
        $quotedPayload = rtrim($quotedPayload, ";\r\n\t ");

        $jsonString = json_decode($quotedPayload, true, 512, JSON_THROW_ON_ERROR);
        $data = json_decode($jsonString, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($data) || empty($data)) {
            throw new \RuntimeException('Dữ liệu PDPData không hợp lệ.');
        }

        return $this->normalizeMixed($data);
    }

    private function normalizeMixed(mixed $value): mixed
    {
        if (is_array($value)) {
            $normalized = [];
            foreach ($value as $key => $item) {
                $normalized[$key] = $this->normalizeMixed($item);
            }

            return $normalized;
        }

        if (is_string($value)) {
            return $this->normalizeText($value);
        }

        return $value;
    }

    private function normalizeText(string $value): string
    {
        $value = str_replace(["\r\n", "\r"], "\n", $value);
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = $this->repairMojibake($value);

        return trim($value);
    }

    private function repairMojibake(string $value): string
    {
        $best = $value;

        for ($i = 0; $i < 2; $i++) {
            if ($this->countBadMarkers($best) === 0) {
                break;
            }

            $fixedIso = @mb_convert_encoding($best, 'UTF-8', 'ISO-8859-1');
            $fixedWin = @mb_convert_encoding($best, 'UTF-8', 'Windows-1252');

            $candidate = $best;

            if (is_string($fixedIso) && $this->countBadMarkers($fixedIso) < $this->countBadMarkers($candidate)) {
                $candidate = $fixedIso;
            }

            if (is_string($fixedWin) && $this->countBadMarkers($fixedWin) < $this->countBadMarkers($candidate)) {
                $candidate = $fixedWin;
            }

            if ($candidate === $best) {
                break;
            }

            $best = $candidate;
        }

        return $best;
    }

    private function countBadMarkers(string $value): int
    {
        return (int) preg_match_all('/(?:Ã.|Â.|Ä.|Ð.|Ñ.|áº.|á».|â€.)/u', $value, $matches);
    }

    private function mapProductToImportRows(array $data, string $url, array $options, array &$warnings, string $productSku): array
    {
        $variants = array_values(array_filter($data['variants'] ?? [], function ($variant) {
            return is_array($variant) && !empty($variant['sku']);
        }));

        $productName = $data['name'] ?? 'Sản phẩm Yody';
        $productSlug = $data['url_handle'] ?? Str::slug($productName);
        [$imageRows, $tempImageRows, $imageUrlToKey] = $this->buildImageRows($productSku, $productName, $variants, $warnings);
        [$variantRows, $productStock, $productPrice, $productSalePrice] = $this->buildVariantRows($productSku, $variants, $imageUrlToKey);

        $description = $this->sanitizeDescriptionHtml($data['description'] ?? '');
        if ($description === '') {
            $plainSeoDescription = trim((string) ($data['seo_description'] ?? ''));
            $description = $plainSeoDescription !== '' ? '<p>' . e($plainSeoDescription) . '</p>' : '';
        }

        $shortDescription = $this->makeShortDescription($description);
        $categoryName = trim((string) ($data['category']['name'] ?? ''));
        $yodyCategorySlug = trim((string) ($data['category']['slug'] ?? ''));
        $materialName = trim((string) ($data['material']['name'] ?? ''));

        $primaryCategorySlug = $options['primary_category_slug'];
        if ($primaryCategorySlug === '' && $yodyCategorySlug !== '' && Category::query()->where('slug', $yodyCategorySlug)->exists()) {
            $primaryCategorySlug = $yodyCategorySlug;
        }

        $categorySlugs = $options['category_slugs'];
        if ($primaryCategorySlug !== '') {
            $categorySlugs[] = $primaryCategorySlug;
        }
        $categorySlugs = array_values(array_unique(array_filter($categorySlugs)));

        $tagNames = $options['tag_names'];
        if ($options['use_yody_category_as_tag'] && $categoryName !== '') {
            $tagNames[] = $categoryName;
        }
        if ($materialName !== '') {
            $tagNames[] = $materialName;
        }
        $tagNames = array_values(array_unique(array_filter($tagNames)));

        $metaTitle = trim((string) ($data['seo_title'] ?? '')) ?: $productName;
        $metaDescription = trim((string) ($data['seo_description'] ?? '')) ?: $shortDescription;
        $metaKeywords = implode(', ', array_values(array_unique(array_filter([
            $productName,
            $categoryName,
            $materialName,
            implode(', ', array_values(array_unique(array_filter(array_map(
                fn ($variant) => trim((string) ($variant['color']['name'] ?? '')),
                $variants
            ))))),
        ]))));

        $productRow = [
            $productSku,
            $productName,
            $productSlug !== '' ? $productSlug : Str::slug($productName),
            $description,
            $shortDescription,
            $productPrice,
            $productSalePrice,
            null,
            $productStock,
            $metaTitle,
            $metaDescription,
            $metaKeywords,
            null,
            $primaryCategorySlug !== '' ? $primaryCategorySlug : null,
            !empty($categorySlugs) ? implode(',', $categorySlugs) : null,
            !empty($tagNames) ? implode(', ', $tagNames) : null,
            $options['is_featured'] ? 1 : 0,
            !empty($variantRows) ? 1 : 0,
            auth('web')->id() ?? 1,
            $options['is_active'] ? 1 : 0,
            'yody',
        ];

        return [
            'product' => $productRow,
            'images' => $imageRows,
            'temp_images' => $tempImageRows,
            'variants' => $variantRows,
            'faqs' => [],
            'how_tos' => $this->buildHowToRows($productSku, $data['material'] ?? []),
            'summary' => [
                'name' => $productName,
                'sku' => $productSku,
                'slug' => $productSlug,
                'source_url' => $url,
                'primary_category_slug' => $primaryCategorySlug,
                'variant_count' => count($variantRows),
                'image_count' => count($imageRows),
            ],
        ];
    }

    private function deriveProductSku(array $variants, array $data): string
    {
        $variantSkus = array_values(array_filter(array_map(
            fn ($variant) => trim((string) ($variant['sku'] ?? '')),
            $variants
        )));

        if (empty($variantSkus)) {
            return 'YODY-' . ($data['id'] ?? Str::upper(Str::random(8)));
        }

        $segmentsList = array_map(fn ($sku) => explode('-', $sku), $variantSkus);
        $commonSegments = $segmentsList[0];

        foreach ($segmentsList as $segments) {
            $nextCommon = [];
            $limit = min(count($commonSegments), count($segments));

            for ($i = 0; $i < $limit; $i++) {
                if ($commonSegments[$i] !== $segments[$i]) {
                    break;
                }

                $nextCommon[] = $commonSegments[$i];
            }

            $commonSegments = $nextCommon;
        }

        if (!empty($commonSegments)) {
            return implode('-', $commonSegments);
        }

        $firstSegments = explode('-', $variantSkus[0]);
        if (count($firstSegments) > 2) {
            return implode('-', array_slice($firstSegments, 0, -2));
        }

        if (count($firstSegments) > 1) {
            return $firstSegments[0];
        }

        return $variantSkus[0];
    }

    private function normalizeSku(string $sku): string
    {
        $normalizedSku = strtoupper(trim($sku));

        return $normalizedSku !== '' ? $normalizedSku : 'YODY-' . Str::upper(Str::random(8));
    }

    private function buildImageRows(string $productSku, string $productName, array $variants, array &$warnings): array
    {
        $orderedImages = [];

        foreach ($variants as $variant) {
            foreach (($variant['images'] ?? []) as $image) {
                $imageUrl = trim((string) ($image['image_url'] ?? ''));
                if ($imageUrl === '' || isset($orderedImages[$imageUrl])) {
                    continue;
                }

                $orderedImages[$imageUrl] = [
                    'id' => $image['id'] ?? null,
                    'url' => $imageUrl,
                    'position' => (int) ($image['position'] ?? (count($orderedImages) + 1)),
                ];
            }
        }

        $orderedImageList = array_values($orderedImages);
        $downloadedImagePaths = $this->downloadProductImagesToTempYody($productSku, $productName, $orderedImageList);

        $rows = [];
        $tempRows = [];
        $imageUrlToKey = [];
        $order = 1;

        foreach ($orderedImageList as $imageData) {
            $imageUrl = $imageData['url'] ?? '';
            $relativePath = $downloadedImagePaths[$imageUrl] ?? null;

            if ($relativePath === null) {
                $warnings[] = "Không tải được ảnh {$imageUrl} cho SKU {$productSku}. Ảnh này sẽ bị bỏ qua.";
                continue;
            }

            $importFilename = $this->syncTempImageToImports($relativePath);
            if ($importFilename === null) {
                $warnings[] = "KhĂ´ng đồng bộ được ảnh {$imageUrl} vào thư mục imports cho SKU {$productSku}. Ảnh này sẽ bị bỏ qua.";
                continue;
            }

            $imageKey = 'YODYIMG-' . Str::upper(Str::slug($productSku, '-')) . '-' . str_pad((string) $order, 3, '0', STR_PAD_LEFT);
            $rows[] = [
                $productSku,
                $imageKey,
                $importFilename,
                $productName . ' - Ảnh ' . $order,
                $imageUrl,
                $productName,
                $order === 1 ? 1 : 0,
                $order,
            ];

            $tempRows[] = [
                $productSku,
                $imageKey,
                $relativePath,
                $productName . ' - áº¢nh ' . $order,
                $imageUrl,
                $productName,
                $order === 1 ? 1 : 0,
                $order,
            ];

            $imageUrlToKey[$imageUrl] = $imageKey;
            $order++;
        }

        return [$rows, $tempRows, $imageUrlToKey];
    }

    private function buildVariantRows(string $productSku, array $variants, array $imageUrlToKey): array
    {
        $rows = [];
        $productStock = 0;
        $originalPrices = [];
        $discountedPrices = [];

        foreach ($variants as $variant) {
            $originalPrice = $this->resolveOriginalPrice($variant);
            $currentPrice = $this->resolveCurrentPrice($variant);
            $stockQuantity = $this->resolveStockQuantity($variant);
            $firstImageUrl = trim((string) (($variant['images'][0]['image_url'] ?? '')));

            $rows[] = [
                $productSku,
                $currentPrice,
                $stockQuantity,
                trim((string) ($variant['color']['name'] ?? '')),
                trim((string) ($variant['size']['name'] ?? '')),
                $firstImageUrl !== '' ? ($imageUrlToKey[$firstImageUrl] ?? null) : null,
            ];

            $productStock += $stockQuantity;

            if ($originalPrice > 0) {
                $originalPrices[] = $originalPrice;
            }

            if ($originalPrice > 0 && $currentPrice > 0 && $currentPrice < $originalPrice) {
                $discountedPrices[] = $currentPrice;
            }
        }

        $productPrice = !empty($originalPrices)
            ? min($originalPrices)
            : (!empty($rows) ? min(array_column($rows, 1)) : 0);

        $productSalePrice = !empty($discountedPrices) ? min($discountedPrices) : null;

        return [$rows, $productStock, $productPrice, $productSalePrice];
    }

    private function resolveOriginalPrice(array $variant): float
    {
        $originalPrice = (float) ($variant['original_price'] ?? 0);
        $salePrice = (float) ($variant['sale_price'] ?? 0);

        if ($originalPrice > 0) {
            return $originalPrice;
        }

        return $salePrice;
    }

    private function resolveCurrentPrice(array $variant): float
    {
        $originalPrice = (float) ($variant['original_price'] ?? 0);
        $salePrice = (float) ($variant['sale_price'] ?? 0);

        if ($salePrice > 0 && $originalPrice > 0 && $salePrice < $originalPrice) {
            return $salePrice;
        }

        return $salePrice > 0 ? $salePrice : $originalPrice;
    }

    private function resolveStockQuantity(array $variant): int
    {
        $inventory = (int) ($variant['inventory'] ?? 0);
        if ($inventory > 0) {
            return $inventory;
        }

        if (!empty($variant['in_stock'])) {
            return max(1, (int) ($variant['safety_inventory'] ?? 1));
        }

        return 0;
    }

    private function sanitizeDescriptionHtml(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html);
        $html = preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $html);
        $html = preg_replace('/\s+/', ' ', $html);

        return trim($html);
    }

    private function makeShortDescription(string $html): string
    {
        $plainText = trim(preg_replace('/\s+/u', ' ', strip_tags($html)));

        return Str::limit($plainText, 250, '');
    }

    private function buildHowToRows(string $productSku, array $material): array
    {
        $materialName = trim((string) ($material['name'] ?? ''));
        $materialDescription = trim((string) ($material['description'] ?? ''));
        $preserve = $this->splitTextToList($material['preserve'] ?? '');
        $supplies = $this->splitTextToList($material['component'] ?? '');

        $specifications = $material['specifications'] ?? null;
        if (is_array($specifications)) {
            foreach ($specifications as $item) {
                if (is_string($item) && trim($item) !== '') {
                    $supplies[] = trim($item);
                }
            }
        } elseif (is_string($specifications) && trim($specifications) !== '') {
            $supplies = array_merge($supplies, $this->splitTextToList($specifications));
        }

        $preserve = array_values(array_unique(array_filter($preserve)));
        $supplies = array_values(array_unique(array_filter($supplies)));

        if ($materialName === '' && $materialDescription === '' && empty($preserve) && empty($supplies)) {
            return [];
        }

        return [[
            $productSku,
            'Thông tin chất liệu và bảo quản',
            trim(implode(' - ', array_filter([$materialName, $materialDescription]))) ?: 'Thông tin chất liệu sản phẩm từ Yody.',
            !empty($preserve) ? json_encode($preserve, JSON_UNESCAPED_UNICODE) : null,
            !empty($supplies) ? json_encode($supplies, JSON_UNESCAPED_UNICODE) : null,
        ]];
    }

    private function splitTextToList(string $text): array
    {
        $text = trim($text);
        if ($text === '') {
            return [];
        }

        $parts = preg_split('/(?:\r\n|\r|\n|;|•|- )+/u', $text) ?: [];

        return array_values(array_filter(array_map(fn ($item) => trim((string) $item), $parts)));
    }

    private function downloadProductImagesToTempYody(string $productSku, string $productName, array $orderedImages): array
    {
        $downloadedPaths = [];
        $pendingImages = [];
        $reservedRelativePaths = [];

        foreach ($orderedImages as $index => $imageData) {
            $imageUrl = trim((string) ($imageData['url'] ?? ''));
            $order = $index + 1;
            if ($imageUrl === '') {
                continue;
            }

            $pathInfo = $this->buildTempImagePathInfo($productSku, $productName, $imageUrl, $order, $reservedRelativePaths);
            if (file_exists($pathInfo['full_path'])) {
                $downloadedPaths[$imageUrl] = $pathInfo['relative_path'];
                continue;
            }

            $reservedRelativePaths[] = $pathInfo['relative_path'];

            $pendingImages[] = [
                'url' => $imageUrl,
                'full_path' => $pathInfo['full_path'],
                'relative_path' => $pathInfo['relative_path'],
            ];
        }

        foreach (array_chunk($pendingImages, self::IMAGE_FETCH_CONCURRENCY) as $chunkImages) {
            $responses = Http::pool(function (Pool $pool) use ($chunkImages) {
                $requests = [];

                foreach ($chunkImages as $index => $image) {
                    $key = 'img_' . $index;
                    $requests[$key] = $pool
                        ->as($key)
                        ->withHeaders([
                            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36',
                            'Referer' => 'https://yody.vn/',
                        ])
                        ->timeout(self::IMAGE_FETCH_TIMEOUT)
                        ->retry(1, 200)
                        ->get($image['url']);
                }

                return $requests;
            });

            foreach ($chunkImages as $index => $image) {
                $response = $responses['img_' . $index] ?? null;

                if (!$response || !$response->successful()) {
                    continue;
                }

                file_put_contents($image['full_path'], $response->body());
                @chmod($image['full_path'], 0644);
                $downloadedPaths[$image['url']] = $image['relative_path'];
            }
        }

        return $downloadedPaths;
    }

    private function buildTempImagePathInfo(string $productSku, string $productName, string $imageUrl, int $order, array $reservedRelativePaths = []): array
    {
        $productImageDir = $this->getYodyProductImagePath($productSku);

        $path = parse_url($imageUrl, PHP_URL_PATH) ?: '';
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION) ?: 'webp');
        if ($extension === '') {
            $extension = 'webp';
        }

        $baseFileName = Str::slug($productName, '-');
        if ($baseFileName === '') {
            $baseFileName = Str::slug($productSku, '-');
        }

        $filename = $order === 1
            ? $baseFileName . '.' . $extension
            : $baseFileName . '-' . str_pad((string) $order, 3, '0', STR_PAD_LEFT) . '.' . $extension;

        $relativeDirectory = $this->getYodyProductImageRelativeDirectory($productSku);
        $relativePath = $relativeDirectory . '/' . $filename;
        $fullPath = $productImageDir . DIRECTORY_SEPARATOR . $filename;

        $collisionIndex = max(2, $order + 1);
        while (file_exists($fullPath) || in_array($relativePath, $reservedRelativePaths, true)) {
            $filename = $baseFileName . '-' . str_pad((string) $collisionIndex, 3, '0', STR_PAD_LEFT) . '.' . $extension;
            $relativePath = $relativeDirectory . '/' . $filename;
            $fullPath = $productImageDir . DIRECTORY_SEPARATOR . $filename;
            $collisionIndex++;
        }

        return [
            'full_path' => $fullPath,
            'relative_path' => $relativePath,
        ];
    }

    private function syncTempImageToImports(string $tempRelativePath): ?string
    {
        $sourcePath = $this->resolveTempRelativePath($tempRelativePath);
        if (!$sourcePath || !is_file($sourcePath)) {
            return null;
        }

        $importsDirectory = public_path('clients/assets/img/imports');
        $this->ensureDirectory($importsDirectory);

        $preferredFilename = basename($tempRelativePath);
        if ($preferredFilename === '') {
            return null;
        }

        $destinationPath = $this->resolveImportDestinationPath($sourcePath, $importsDirectory, $preferredFilename);
        if ($destinationPath === null) {
            return null;
        }

        if (is_file($destinationPath) && !$this->sameFileContents($sourcePath, $destinationPath)) {
            @unlink($destinationPath);
        }

        if (!is_file($destinationPath)) {
            if (!@copy($sourcePath, $destinationPath)) {
                return null;
            }
        }

        @chmod($destinationPath, 0644);

        return basename($destinationPath);
    }

    private function resolveImportDestinationPath(string $sourcePath, string $importsDirectory, string $preferredFilename): ?string
    {
        $extension = pathinfo($preferredFilename, PATHINFO_EXTENSION);
        $baseName = pathinfo($preferredFilename, PATHINFO_FILENAME);

        if ($baseName === '' || $baseName === '.') {
            return null;
        }

        $candidateFilename = $baseName . ($extension !== '' ? '.' . $extension : '');

        return $importsDirectory . DIRECTORY_SEPARATOR . $candidateFilename;
    }

    private function sameFileContents(string $sourcePath, string $targetPath): bool
    {
        if (!is_file($sourcePath) || !is_file($targetPath)) {
            return false;
        }

        $sourceSize = @filesize($sourcePath);
        $targetSize = @filesize($targetPath);
        if ($sourceSize === false || $targetSize === false || $sourceSize !== $targetSize) {
            return false;
        }

        return hash_file('sha1', $sourcePath) === hash_file('sha1', $targetPath);
    }

    private function buildImportWorkbook(
        array $productRows,
        array $imageRows,
        array $variantRows,
        array $faqRows,
        array $howToRows
    ): string {
        $spreadsheet = new Spreadsheet();

        $this->fillSheet(
            $spreadsheet->getActiveSheet(),
            'products',
            [
                'sku','name','slug','description','short_description','price','sale_price',
                'cost_price','stock_quantity','meta_title','meta_description','meta_keywords',
                'meta_canonical','primary_category_slug','category_slugs','tag_slugs',
                'is_featured','has_variants','created_by','is_active','brand_slug',
            ],
            $productRows
        );

        $this->fillSheet(
            $spreadsheet->createSheet(),
            'images',
            ['sku','image_key','local_path','title','notes','alt','is_primary','order'],
            $imageRows
        );

        $this->fillSheet(
            $spreadsheet->createSheet(),
            'product_variants',
            ['sku','price','stock_quantity','attributes_color','attributes_size','image_key'],
            $variantRows
        );

        $this->fillSheet(
            $spreadsheet->createSheet(),
            'product_faqs',
            ['sku','question','answer','order'],
            $faqRows
        );

        $this->fillSheet(
            $spreadsheet->createSheet(),
            'product_how_tos',
            ['sku','title','description','steps','supplies'],
            $howToRows
        );

        $fileName = 'yody_products_import_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        $fullPath = $this->getYodyTempPath($fileName);
        $writer = new Xlsx($spreadsheet);
        $writer->save($fullPath);

        return $fileName;
    }

    public function getTempLibrarySummary(array $filters = []): array
    {
        $this->syncLegacyManifestToDatabase();

        $search = trim((string) ($filters['q'] ?? ''));
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = max(10, min(100, (int) ($filters['per_page'] ?? 20)));

        if ($this->hasTempIndexTable()) {
            $query = YodyTempProduct::query();

            if ($search !== '') {
                $query->where(function ($builder) use ($search) {
                    $builder->where('sku', 'like', '%' . $search . '%')
                        ->orWhere('name', 'like', '%' . $search . '%')
                        ->orWhere('slug', 'like', '%' . $search . '%')
                        ->orWhere('source_url', 'like', '%' . $search . '%');
                });
            }

            $products = $query
                ->orderByDesc('last_crawled_at')
                ->orderByDesc('id')
                ->paginate($perPage, ['*'], 'page', $page)
                ->withQueryString();

            $products->setCollection($products->getCollection()->map(
                fn (YodyTempProduct $product) => $this->formatTempProductRow([
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'source_url' => $product->source_url,
                    'primary_category_slug' => $product->primary_category_slug,
                    'crawl_batch' => $product->crawl_batch,
                    'variant_count' => $product->variant_count,
                    'image_count' => $product->image_count,
                    'image_files' => $product->image_files ?? [],
                    'preview_relative_path' => $product->preview_relative_path,
                    'first_crawled_at' => optional($product->first_crawled_at)?->format('Y-m-d H:i:s'),
                    'last_crawled_at' => optional($product->last_crawled_at)?->format('Y-m-d H:i:s'),
                ])
            ));

            $excelFiles = $this->getTempExcelFiles();

            return [
                'temp_directory' => $this->getYodyTempPath(),
                'image_directory' => $this->getYodyCrawlsPath(),
                'manifest_path' => $this->getManifestPath(),
                'product_count' => YodyTempProduct::query()->count(),
                'image_count' => (int) (YodyTempProduct::query()->sum('image_count') ?? 0),
                'excel_file_count' => count($excelFiles),
                'products' => $products,
                'excel_files' => $excelFiles,
                'search' => $search,
                'per_page' => $perPage,
            ];
        }

        $manifest = $this->loadManifest();
        $products = collect($manifest['products'] ?? [])
            ->map(function (array $item, string $sku) {
                $imageFiles = array_values(array_filter(array_map(
                    fn ($path) => trim((string) $path),
                    $item['image_files'] ?? []
                )));
                $previewPath = $imageFiles[0] ?? null;

                return [
                    'sku' => $sku,
                    'name' => $item['name'] ?? null,
                    'slug' => $item['slug'] ?? null,
                    'source_url' => $item['source_url'] ?? null,
                    'primary_category_slug' => $item['primary_category_slug'] ?? null,
                    'variant_count' => (int) ($item['variant_count'] ?? 0),
                    'image_count' => count($imageFiles),
                    'image_files' => $imageFiles,
                    'preview_relative_path' => $previewPath,
                    'preview_url' => $previewPath
                        ? route('admin.yody-crawler.preview-image', ['path' => $previewPath])
                        : null,
                    'first_crawled_at' => $item['first_crawled_at'] ?? null,
                    'last_crawled_at' => $item['last_crawled_at'] ?? null,
                ];
            })
            ->sortByDesc(fn (array $item) => $item['last_crawled_at'] ?? '')
            ->values()
            ->all();

        $excelFiles = collect(glob($this->getYodyTempPath('*.xlsx')) ?: [])
            ->map(fn (string $path) => [
                'name' => basename($path),
                'size_bytes' => is_file($path) ? filesize($path) : 0,
                'updated_at' => is_file($path) ? date('Y-m-d H:i:s', filemtime($path)) : null,
                'download_url' => route('admin.yody-crawler.download', ['filename' => basename($path)]),
            ])
            ->sortByDesc(fn (array $item) => $item['updated_at'] ?? '')
            ->values()
            ->all();

        return [
            'temp_directory' => $this->getYodyTempPath(),
            'image_directory' => $this->getYodyCrawlsPath(),
            'manifest_path' => $this->getManifestPath(),
            'product_count' => count($products),
            'image_count' => array_sum(array_map(fn (array $item) => $item['image_count'], $products)),
            'excel_file_count' => count($excelFiles),
            'products' => $products,
            'excel_files' => $excelFiles,
        ];
    }

    public function deleteTempProduct(string $sku): array
    {
        $this->syncLegacyManifestToDatabase();

        $normalizedSku = $this->normalizeSku($sku);
        $productRow = $this->findIndexedTempProduct($normalizedSku);

        if ($productRow !== null) {
            $imageFiles = $productRow['image_files'] ?? [];

            if (!empty($imageFiles)) {
                foreach ($imageFiles as $relativePath) {
                    $absolutePath = $this->resolveTempRelativePath((string) $relativePath);
                    if ($absolutePath && is_file($absolutePath)) {
                        @unlink($absolutePath);
                        $this->deleteEmptyParentDirectories(dirname($absolutePath), $this->getYodyCrawlsPath());
                    }
                }
            } else {
                foreach ($this->getProductImageDirectories($normalizedSku) as $directory) {
                    if (is_dir($directory)) {
                        $this->deleteDirectory($directory);
                    }
                }
            }

            if ($this->hasTempIndexTable()) {
                YodyTempProduct::query()->where('sku', $normalizedSku)->delete();
            }

            $this->removeLegacyManifestProduct($normalizedSku);

            return [
                'sku' => $normalizedSku,
                'name' => $productRow['name'] ?? $normalizedSku,
                'deleted_image_count' => (int) ($productRow['image_count'] ?? 0),
            ];
        }

        $normalizedSku = $this->normalizeSku($sku);
        $manifest = $this->loadManifest();

        if (!$this->manifestHasSku($manifest, $normalizedSku)) {
            throw new \InvalidArgumentException("Không tìm thấy SKU {$normalizedSku} trong thư mục tạm Yody.");
        }

        $productData = $manifest['products'][$normalizedSku] ?? [];
        $imageFiles = array_values(array_filter($productData['image_files'] ?? []));

        foreach ($imageFiles as $relativePath) {
            $absolutePath = $this->resolveTempRelativePath((string) $relativePath);
            if ($absolutePath && is_file($absolutePath)) {
                @unlink($absolutePath);
            }
        }

        $productImageDir = $this->getYodyImagesPath() . DIRECTORY_SEPARATOR . Str::slug($normalizedSku, '-');
        if (is_dir($productImageDir)) {
            $this->deleteDirectory($productImageDir);
        }

        unset($manifest['products'][$normalizedSku]);
        $this->saveManifest($manifest);

        return [
            'sku' => $normalizedSku,
            'name' => $productData['name'] ?? $normalizedSku,
            'deleted_image_count' => count($imageFiles),
        ];
    }

    public function clearTempLibrary(): array
    {
        $summary = $this->getTempLibrarySummary();

        if ($this->hasTempIndexTable()) {
            YodyTempProduct::query()->delete();
        }

        foreach ($summary['excel_files'] as $excelFile) {
            $excelPath = $this->getYodyTempPath($excelFile['name']);
            if (is_file($excelPath)) {
                @unlink($excelPath);
            }
        }

        if (is_file($this->getManifestPath())) {
            @unlink($this->getManifestPath());
        }

        $crawlsPath = $this->getYodyCrawlsPath();
        if (is_dir($crawlsPath)) {
            $this->deleteDirectory($crawlsPath);
        }

        $legacyImagesPath = $this->getYodyImagesPath();
        if (is_dir($legacyImagesPath)) {
            $this->deleteDirectory($legacyImagesPath);
        }

        $this->ensureDirectory($this->getYodyTempPath());
        $this->ensureDirectory($this->getYodyCrawlsPath());

        return [
            'deleted_products' => $summary['product_count'],
            'deleted_images' => $summary['image_count'],
            'deleted_excel_files' => $summary['excel_file_count'],
        ];
    }

    public function resolveTempRelativePath(string $relativePath): ?string
    {
        $candidate = $this->getYodyTempPath(ltrim($relativePath, '/\\'));
        $realBase = realpath($this->getYodyTempPath());
        $realCandidate = realpath($candidate);

        if (!$realBase || !$realCandidate || !str_starts_with($realCandidate, $realBase)) {
            return null;
        }

        return is_file($realCandidate) ? $realCandidate : null;
    }

    private function tempIndexHasSku(string $sku): bool
    {
        $normalizedSku = $this->normalizeSku($sku);

        if ($this->hasTempIndexTable()) {
            return YodyTempProduct::query()->where('sku', $normalizedSku)->exists();
        }

        return $this->manifestHasSku($this->loadManifest(), $normalizedSku);
    }

    private function storeTempProductIndex(array $summary, array $imageRows): void
    {
        $normalizedSku = $this->normalizeSku((string) ($summary['sku'] ?? ''));
        if ($normalizedSku === '') {
            return;
        }

        $previewRelativePath = trim((string) ($imageRows[0][2] ?? '')) ?: $this->findPreviewRelativePath($normalizedSku);
        $payload = [
            'sku' => $normalizedSku,
            'name' => $summary['name'] ?? null,
            'slug' => $summary['slug'] ?? null,
            'source_url' => $summary['source_url'] ?? null,
            'primary_category_slug' => $summary['primary_category_slug'] ?? null,
            'crawl_batch' => $this->currentCrawlBatch,
            'variant_count' => (int) ($summary['variant_count'] ?? 0),
            'image_count' => count($imageRows),
            'image_files' => array_values(array_filter(array_map(
                fn ($row) => trim((string) ($row[2] ?? '')),
                $imageRows
            ))),
            'preview_relative_path' => $previewRelativePath ?: null,
            'first_crawled_at' => now(),
            'last_crawled_at' => now(),
        ];

        if ($this->hasTempIndexTable()) {
            $existing = YodyTempProduct::query()->where('sku', $normalizedSku)->first();

            if ($existing) {
                $payload['first_crawled_at'] = $existing->first_crawled_at ?? now();
                $existing->fill($payload)->save();
            } else {
                YodyTempProduct::query()->create($payload);
            }

            return;
        }

        $manifest = $this->rememberManifestProduct($this->loadManifest(), $summary, $imageRows);
        $this->saveManifest($manifest);
    }

    private function syncLegacyManifestToDatabase(): void
    {
        if (!$this->hasTempIndexTable() || !is_file($this->getManifestPath())) {
            return;
        }

        $manifest = $this->loadManifest();
        $products = $manifest['products'] ?? [];

        foreach ($products as $sku => $item) {
            $normalizedSku = $this->normalizeSku((string) $sku);
            if ($normalizedSku === '') {
                continue;
            }

            $imageFiles = array_values(array_filter(array_map(
                fn ($path) => trim((string) $path),
                $item['image_files'] ?? []
            )));

            $existing = YodyTempProduct::query()->where('sku', $normalizedSku)->first();
            $payload = [
                'sku' => $normalizedSku,
                'name' => $item['name'] ?? null,
                'slug' => $item['slug'] ?? null,
                'source_url' => $item['source_url'] ?? null,
                'primary_category_slug' => $item['primary_category_slug'] ?? null,
                'crawl_batch' => $item['crawl_batch'] ?? null,
                'variant_count' => (int) ($item['variant_count'] ?? 0),
                'image_count' => !empty($imageFiles) ? count($imageFiles) : $this->countProductImages($normalizedSku),
                'image_files' => $imageFiles,
                'preview_relative_path' => $imageFiles[0] ?? $this->findPreviewRelativePath($normalizedSku),
                'first_crawled_at' => $item['first_crawled_at'] ?? now()->toDateTimeString(),
                'last_crawled_at' => $item['last_crawled_at'] ?? now()->toDateTimeString(),
            ];

            if ($existing) {
                $existing->fill($payload)->save();
            } else {
                YodyTempProduct::query()->create($payload);
            }
        }

        $this->saveManifest([
            'products' => [],
            'migrated_to_db_at' => now()->toDateTimeString(),
        ]);
    }

    private function hasTempIndexTable(): bool
    {
        static $hasTable = null;

        if ($hasTable === null) {
            $hasTable = Schema::hasTable('yody_temp_products');
        }

        return $hasTable;
    }

    private function formatTempProductRow(array $item): array
    {
        $previewRelativePath = trim((string) ($item['preview_relative_path'] ?? ''));

        return [
            'sku' => $this->normalizeSku((string) ($item['sku'] ?? '')),
            'name' => $item['name'] ?? null,
            'slug' => $item['slug'] ?? null,
            'source_url' => $item['source_url'] ?? null,
            'primary_category_slug' => $item['primary_category_slug'] ?? null,
            'crawl_batch' => $item['crawl_batch'] ?? null,
            'variant_count' => (int) ($item['variant_count'] ?? 0),
            'image_count' => (int) ($item['image_count'] ?? 0),
            'image_files' => array_values(array_filter($item['image_files'] ?? [])),
            'preview_relative_path' => $previewRelativePath ?: null,
            'preview_url' => $previewRelativePath
                ? route('admin.yody-crawler.preview-image', ['path' => $previewRelativePath])
                : null,
            'first_crawled_at' => $item['first_crawled_at'] ?? null,
            'last_crawled_at' => $item['last_crawled_at'] ?? null,
        ];
    }

    private function getLegacyManifestPaginator(string $search, int $page, int $perPage): array
    {
        $manifest = $this->loadManifest();
        $products = collect($manifest['products'] ?? [])
            ->map(fn (array $item, string $sku) => $this->formatTempProductRow([
                'sku' => $sku,
                'name' => $item['name'] ?? null,
                'slug' => $item['slug'] ?? null,
                'source_url' => $item['source_url'] ?? null,
                'primary_category_slug' => $item['primary_category_slug'] ?? null,
                'variant_count' => (int) ($item['variant_count'] ?? 0),
                'image_count' => count($item['image_files'] ?? []),
                'preview_relative_path' => $item['image_files'][0] ?? null,
                'first_crawled_at' => $item['first_crawled_at'] ?? null,
                'last_crawled_at' => $item['last_crawled_at'] ?? null,
            ]))
            ->filter(function (array $item) use ($search) {
                if ($search === '') {
                    return true;
                }

                foreach (['sku', 'name', 'slug', 'source_url'] as $field) {
                    if (stripos((string) ($item[$field] ?? ''), $search) !== false) {
                        return true;
                    }
                }

                return false;
            })
            ->sortByDesc(fn (array $item) => $item['last_crawled_at'] ?? '')
            ->values();

        $total = $products->count();
        $items = $products->slice(($page - 1) * $perPage, $perPage)->values();

        return [
            new LengthAwarePaginator(
                $items,
                $total,
                $perPage,
                $page,
                ['path' => request()->url(), 'query' => request()->query()]
            ),
            $total,
            array_sum(array_map(fn (array $item) => (int) ($item['image_count'] ?? 0), $products->all())),
        ];
    }

    private function getTempExcelFiles(): array
    {
        return collect(glob($this->getYodyTempPath('*.xlsx')) ?: [])
            ->map(fn (string $path) => [
                'name' => basename($path),
                'size_bytes' => is_file($path) ? filesize($path) : 0,
                'updated_at' => is_file($path) ? date('Y-m-d H:i:s', filemtime($path)) : null,
                'download_url' => route('admin.yody-crawler.download', ['filename' => basename($path)]),
            ])
            ->sortByDesc(fn (array $item) => $item['updated_at'] ?? '')
            ->take(50)
            ->values()
            ->all();
    }

    private function findIndexedTempProduct(string $sku): ?array
    {
        if ($this->hasTempIndexTable()) {
            $product = YodyTempProduct::query()->where('sku', $sku)->first();
            if ($product) {
                return [
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'image_count' => $product->image_count,
                ];
            }
        }

        $manifest = $this->loadManifest();
        if (!$this->manifestHasSku($manifest, $sku)) {
            return null;
        }

        return [
            'sku' => $sku,
            'name' => $manifest['products'][$sku]['name'] ?? $sku,
            'image_count' => count($manifest['products'][$sku]['image_files'] ?? []),
            'image_files' => array_values(array_filter($manifest['products'][$sku]['image_files'] ?? [])),
        ];
    }

    private function removeLegacyManifestProduct(string $sku): void
    {
        $manifest = $this->loadManifest();
        if (!$this->manifestHasSku($manifest, $sku)) {
            return;
        }

        unset($manifest['products'][$sku]);
        $this->saveManifest($manifest);
    }

    private function findPreviewRelativePath(string $sku): ?string
    {
        foreach ($this->getProductImageDirectories($sku) as $directory) {
            if (!is_dir($directory)) {
                continue;
            }

            $files = glob($directory . DIRECTORY_SEPARATOR . '*') ?: [];
            sort($files);
            foreach ($files as $file) {
                if (is_file($file)) {
                    $relative = str_replace($this->getYodyTempPath() . DIRECTORY_SEPARATOR, '', $file);
                    return str_replace(DIRECTORY_SEPARATOR, '/', $relative);
                }
            }
        }

        return null;
    }

    private function countProductImages(string $sku): int
    {
        foreach ($this->getProductImageDirectories($sku) as $directory) {
            if (is_dir($directory)) {
                return count(array_filter(glob($directory . DIRECTORY_SEPARATOR . '*') ?: [], 'is_file'));
            }
        }

        return 0;
    }

    private function getProductImageDirectories(string $sku): array
    {
        if ($sku === '*') {
            $directories = [];
            $shards = glob($this->getYodyImagesPath() . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) ?: [];
            foreach ($shards as $shardPath) {
                $directories = array_merge($directories, glob($shardPath . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) ?: []);
            }

            return array_values(array_unique(array_merge(
                $directories,
                glob($this->getYodyImagesPath() . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) ?: []
            )));
        }

        $slugSku = Str::slug($sku, '-');
        $shard = substr($slugSku, 0, 2);

        return array_values(array_unique([
            $this->getYodyImagesPath() . DIRECTORY_SEPARATOR . $shard . DIRECTORY_SEPARATOR . $slugSku,
            $this->getYodyImagesPath() . DIRECTORY_SEPARATOR . $slugSku,
        ]));
    }

    private function getYodyTempPath(string $relativePath = ''): string
    {
        $basePath = storage_path('app/tmp/yody');
        $this->ensureDirectory($basePath);

        if ($relativePath === '') {
            return $basePath;
        }

        $sanitizedRelativePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, ltrim($relativePath, '/\\'));

        return $basePath . DIRECTORY_SEPARATOR . $sanitizedRelativePath;
    }

    private function getYodyImagesPath(): string
    {
        $imagesPath = $this->getYodyTempPath('images');
        $this->ensureDirectory($imagesPath);

        return $imagesPath;
    }

    private function getYodyCrawlsPath(): string
    {
        $crawlsPath = $this->getYodyTempPath('crawls');
        $this->ensureDirectory($crawlsPath);

        return $crawlsPath;
    }

    private function generateCrawlBatch(): string
    {
        return now()->format('Ymd_His') . '_' . Str::lower(Str::random(6));
    }

    private function getCurrentCrawlRelativeDirectory(): string
    {
        if (!$this->currentCrawlBatch) {
            $this->currentCrawlBatch = $this->generateCrawlBatch();
        }

        return 'crawls/' . $this->currentCrawlBatch;
    }

    private function getYodyProductImagePath(string $productSku): string
    {
        $relativeDirectory = $this->getCurrentCrawlRelativeDirectory();
        $productImagePath = $this->getYodyTempPath($relativeDirectory);
        $this->ensureDirectory($productImagePath);

        return $productImagePath;
    }

    private function getYodyProductImageRelativeDirectory(string $productSku): string
    {
        return $this->getCurrentCrawlRelativeDirectory();
    }

    private function getManifestPath(): string
    {
        return $this->getYodyTempPath('manifest.json');
    }

    private function ensureDirectory(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }

    private function deleteDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = scandir($path);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $fullPath = $path . DIRECTORY_SEPARATOR . $item;
            if (is_dir($fullPath)) {
                $this->deleteDirectory($fullPath);
                continue;
            }

            @unlink($fullPath);
        }

        @rmdir($path);
    }

    private function deleteEmptyParentDirectories(string $path, string $stopAtPath): void
    {
        $normalizedStopAtPath = rtrim($stopAtPath, DIRECTORY_SEPARATOR);
        $currentPath = rtrim($path, DIRECTORY_SEPARATOR);

        while ($currentPath !== '' && $currentPath !== $normalizedStopAtPath) {
            if (!is_dir($currentPath)) {
                $currentPath = dirname($currentPath);
                continue;
            }

            $items = array_diff(scandir($currentPath) ?: [], ['.', '..']);
            if (!empty($items)) {
                break;
            }

            @rmdir($currentPath);
            $currentPath = dirname($currentPath);
        }
    }

    private function loadManifest(): array
    {
        $manifestPath = $this->getManifestPath();

        if (!is_file($manifestPath)) {
            return ['products' => []];
        }

        $decoded = json_decode((string) file_get_contents($manifestPath), true);
        if (!is_array($decoded)) {
            return ['products' => []];
        }

        if (!isset($decoded['products']) || !is_array($decoded['products'])) {
            $decoded['products'] = [];
        }

        return $decoded;
    }

    private function saveManifest(array $manifest): void
    {
        file_put_contents(
            $this->getManifestPath(),
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }

    private function manifestHasSku(array $manifest, string $sku): bool
    {
        return isset($manifest['products'][strtoupper(trim($sku))]);
    }

    private function rememberManifestProduct(array $manifest, array $summary, array $imageRows): array
    {
        $sku = strtoupper(trim((string) ($summary['sku'] ?? '')));
        if ($sku === '') {
            return $manifest;
        }

        $existing = $manifest['products'][$sku] ?? [];
        $manifest['products'][$sku] = [
            'sku' => $sku,
            'name' => $summary['name'] ?? null,
            'slug' => $summary['slug'] ?? null,
            'source_url' => $summary['source_url'] ?? null,
            'primary_category_slug' => $summary['primary_category_slug'] ?? null,
            'variant_count' => (int) ($summary['variant_count'] ?? 0),
            'image_count' => (int) ($summary['image_count'] ?? 0),
            'image_files' => array_values(array_filter(array_map(
                fn ($row) => trim((string) ($row[2] ?? '')),
                $imageRows
            ))),
            'first_crawled_at' => $existing['first_crawled_at'] ?? now()->toDateTimeString(),
            'last_crawled_at' => now()->toDateTimeString(),
        ];

        return $manifest;
    }

    private function fillSheet(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
        string $title,
        array $headers,
        array $rows
    ): void {
        $sheet->setTitle($title);
        $sheet->fromArray($headers, null, 'A1');

        $rowNumber = 2;
        foreach ($rows as $row) {
            $sheet->fromArray($row, null, 'A' . $rowNumber);
            $rowNumber++;
        }
    }

    private function parseCsvList(?string $value): array
    {
        if ($value === null || trim($value) === '') {
            return [];
        }

        $items = preg_split('/\s*,\s*/u', trim($value)) ?: [];

        return array_values(array_unique(array_filter(array_map(
            fn ($item) => trim((string) $item),
            $items
        ))));
    }
}
