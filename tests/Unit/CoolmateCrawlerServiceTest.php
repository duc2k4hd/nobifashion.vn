<?php

namespace Tests\Unit;

use App\Services\CoolmateCrawlerService;
use Illuminate\Container\Container;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class CoolmateCrawlerServiceTest extends TestCase
{
    private Factory $httpFactory;

    private string $tempDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $container = new Container;
        $container->instance('log', new NullLogger);
        $this->httpFactory = new Factory;
        $container->instance(Factory::class, $this->httpFactory);
        Facade::setFacadeApplication($container);

        $this->tempDirectory = sys_get_temp_dir()
            .DIRECTORY_SEPARATOR
            .'coolmate_test_'.bin2hex(random_bytes(6));
        mkdir($this->tempDirectory, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->deleteTestDirectory($this->tempDirectory);
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);

        parent::tearDown();
    }

    public function test_crawl_posts_exports_full_content_csv_and_downloads_images_with_required_names(): void
    {
        $sourceUrl = 'https://www.coolmate.me/blog/top-ao-dep-2026';
        $longContent = str_repeat('Nội dung dài để kiểm tra CSV không làm mất dữ liệu. ', 1200);
        $csvEdgeCase = 'Đường dẫn C:\temp\"quoted", có dấu phẩy'."\n".'và xuống dòng trong một ô.';
        $html = <<<HTML
        <html>
            <head>
                <meta property="og:title" content="Top CoOl BlOg Áo Đẹp 2025">
                <meta property="og:description" content="Coolmate ra mắt từ năm 2024">
                <meta property="og:image" content="https://cdn.coolmate.me/main.webp">
                <meta property="og:image:alt" content="Ảnh CoolMate 2023">
                <meta property="article:section" content="Làm đẹp">
                <meta property="article:tag" content="CoolBlog 2022">
                <meta property="article:published_time" content="2025-07-25T10:30:00+07:00">
                <link rel="canonical" href="{$sourceUrl}">
            </head>
            <body>
                <article>
                    <h1 class="entry-title">Top CoOl BlOg Áo Đẹp 2025</h1>
                    <div class="entry-content single-page">
                        <p>CoolMate từng được yêu thích năm 2023. <a class="link-test" href="https://coolmate.me" data-id="1">cOoL bLoG 2024</a></p>
                        <p>{$longContent}</p>
                        <p>{$csvEdgeCase}</p>
                        <img src="https://cdn.coolmate.me/content-1.webp" alt="CoolBlog Son 2024">
                        <img data-src="https://cdn.coolmate.me/content-2.jpeg">
                    </div>
                </article>
            </body>
        </html>
        HTML;

        $this->httpFactory->fake([
            $sourceUrl => $this->httpFactory->response($html, 200, ['Content-Type' => 'text/html']),
            'https://cdn.coolmate.me/main.webp' => $this->httpFactory->response('MAIN', 200, ['Content-Type' => 'image/webp']),
            'https://cdn.coolmate.me/content-1.webp' => $this->httpFactory->response('EXTRA-1', 200, ['Content-Type' => 'image/webp']),
            'https://cdn.coolmate.me/content-2.jpeg' => $this->httpFactory->response('EXTRA-2', 200, ['Content-Type' => 'image/jpeg']),
            '*' => $this->httpFactory->response('', 500),
        ]);

        $mainDirectory = $this->tempDirectory.DIRECTORY_SEPARATOR.'main';
        mkdir($mainDirectory, 0755, true);
        file_put_contents($mainDirectory.DIRECTORY_SEPARATOR.'top-khanh-beauty-ao-dep.webp', 'OLD');

        $service = $this->makeService();
        $results = $service->crawlPostsToCsv([
            $sourceUrl,
            'http://coolmate.me/blog/top-ao-dep-2026?utm_source=test',
            'https://example.com/not-allowed',
        ]);

        $this->assertSame(3, $results['total']);
        $this->assertSame(1, $results['success']);
        $this->assertSame(1, $results['failed']);
        $this->assertSame(1, $results['skipped']);
        $this->assertSame(1, $results['skipped_duplicate']);
        $this->assertSame(0, $results['skipped_history']);
        $this->assertSame(3, $results['image_downloaded_count']);
        $this->assertSame(8, $results['page_batch_size']);
        $this->assertSame(16, $results['image_batch_size']);
        $this->assertGreaterThan(32767, $results['posts'][0]['content_length']);

        $mainPath = $mainDirectory.DIRECTORY_SEPARATOR.'top-khanh-beauty-ao-dep.webp';
        $extraOnePath = $this->tempDirectory.DIRECTORY_SEPARATOR.'extra'
            .DIRECTORY_SEPARATOR.'khanh-beauty-son-2026-1.webp';
        $extraTwoPath = $this->tempDirectory.DIRECTORY_SEPARATOR.'extra'
            .DIRECTORY_SEPARATOR.'top-khanh-beauty-ao-dep-2026-2.jpeg';

        $this->assertSame('MAIN', file_get_contents($mainPath));
        $this->assertSame('EXTRA-1', file_get_contents($extraOnePath));
        $this->assertSame('EXTRA-2', file_get_contents($extraTwoPath));
        $this->assertFileExists($results['file_path']);

        $csvHandle = fopen($results['file_path'], 'rb');
        $headers = fgetcsv($csvHandle, null, ',', '"', '');
        $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
        $headersByName = array_flip($headers);
        $csvRow = fgetcsv($csvHandle, null, ',', '"', '');

        $this->assertSame('ID', $headers[0]);
        $this->assertSame('Nội dung', $headers[4]);
        $this->assertSame('Thumbnail URL', $headers[6]);
        $this->assertSame('Ảnh phụ', $headers[7]);
        $this->assertSame('Chi tiết ảnh (JSON)', $headers[22]);
        $this->assertSame('Top Khánh Beauty Áo Đẹp', $csvRow[1]);
        $this->assertSame('top-khanh-beauty-ao-dep', $csvRow[2]);
        $this->assertSame(
            'storage/app/tmp/coolmate/main/top-khanh-beauty-ao-dep.webp',
            $csvRow[6]
        );
        $this->assertSame(
            'storage/app/tmp/coolmate/extra/khanh-beauty-son-2026-1.webp, '
            .'storage/app/tmp/coolmate/extra/top-khanh-beauty-ao-dep-2026-2.jpeg',
            $csvRow[7]
        );
        $this->assertSame('Ảnh Khánh Beauty 2026', $csvRow[8]);
        $this->assertSame('Khánh Beauty 2026', $csvRow[11]);
        $this->assertSame('Top Khánh Beauty Áo Đẹp 2026', $csvRow[12]);
        $this->assertSame('Khánh Beauty ra mắt từ năm 2026', $csvRow[13]);
        $this->assertSame('2026-07-25 10:30:00', $csvRow[17]);
        $this->assertSame('2', $csvRow[21]);

        $fullContent = $csvRow[4];
        for ($part = 2; isset($headersByName["Nội dung {$part}"]); $part++) {
            $fullContent .= $csvRow[$headersByName["Nội dung {$part}"]];
        }
        $this->assertGreaterThan(32767, mb_strlen($fullContent, 'UTF-8'));
        foreach ($csvRow as $cell) {
            $this->assertLessThanOrEqual(30000, mb_strlen($cell, 'UTF-8'));
        }
        $this->assertStringContainsString($longContent, $fullContent);
        $this->assertStringContainsString(
            str_replace("\n", ' ', $csvEdgeCase),
            $fullContent
        );
        $this->assertStringNotContainsString("\r", $fullContent);
        $this->assertStringNotContainsString("\n", $fullContent);
        $this->assertCount(2, file($results['file_path']));

        $this->assertStringContainsString(
            'storage/app/tmp/coolmate/extra/khanh-beauty-son-2026-1.webp',
            $fullContent
        );
        $this->assertStringContainsString(
            'storage/app/tmp/coolmate/extra/top-khanh-beauty-ao-dep-2026-2.jpeg',
            $fullContent
        );
        $this->assertStringContainsString('<em>Khánh Beauty 2026</em>', $fullContent);
        $this->assertStringNotContainsString('<a', $fullContent);
        $this->assertStringNotContainsString('Coolmate', $fullContent);
        $this->assertStringNotContainsString('CoolBlog', $fullContent);
        $this->assertStringContainsString(
            'alt="Khánh Beauty Son 2026" title="Khánh Beauty Son 2026"',
            $fullContent
        );
        $this->assertStringContainsString(
            'alt="Top Khánh Beauty Áo Đẹp 2026" title="Top Khánh Beauty Áo Đẹp 2026"',
            $fullContent
        );
        $imageDetails = json_decode($csvRow[22], true, flags: JSON_THROW_ON_ERROR);
        $this->assertCount(3, $imageDetails);
        $this->assertSame('khanh-beauty-son-2026', $imageDetails[1]['image_slug']);
        $this->assertSame(
            'top-khanh-beauty-ao-dep-2026',
            $imageDetails[2]['image_slug']
        );
        $this->assertFalse(fgetcsv($csvHandle, null, ',', '"', ''));
        fclose($csvHandle);

        $this->assertNull($service->resolveExportPath('../outside.csv'));
        $this->assertSame(
            realpath($results['file_path']),
            $service->resolveExportPath($results['file_name'])
        );

        $requestCount = count($this->httpFactory->recorded());
        $secondResults = $service->crawlPostsToCsv([$sourceUrl]);

        $this->assertSame(0, $secondResults['success']);
        $this->assertSame(0, $secondResults['failed']);
        $this->assertSame(1, $secondResults['skipped']);
        $this->assertSame(1, $secondResults['skipped_history']);
        $this->assertSame(0, $secondResults['skipped_duplicate']);
        $this->assertNull($secondResults['file_name']);
        $this->assertCount($requestCount, $this->httpFactory->recorded());

        $recrawlResults = $service->crawlPostsToCsv([$sourceUrl], true);

        $this->assertTrue($recrawlResults['recrawl_existing']);
        $this->assertSame(1, $recrawlResults['success']);
        $this->assertSame(0, $recrawlResults['skipped']);
        $this->assertSame(0, $recrawlResults['skipped_history']);
        $this->assertNotNull($recrawlResults['file_name']);

        $registry = json_decode(
            file_get_contents($this->tempDirectory.DIRECTORY_SEPARATOR.'crawled_urls.json'),
            true,
            flags: JSON_THROW_ON_ERROR
        );
        $this->assertArrayHasKey($sourceUrl, $registry['urls']);
        $this->assertSame(
            'top-khanh-beauty-ao-dep',
            $registry['urls'][$sourceUrl]['slug']
        );
    }

    public function test_json_export_replaces_invalid_utf8_instead_of_failing(): void
    {
        $service = $this->makeService();
        $method = new \ReflectionMethod($service, 'encodeJson');
        $method->setAccessible(true);

        $json = $method->invoke($service, ['title' => "Son lỗi \xC3\x28"]);
        $decoded = json_decode($json, true, flags: JSON_THROW_ON_ERROR);

        $this->assertTrue(mb_check_encoding($decoded['title'], 'UTF-8'));
        $this->assertStringContainsString("\u{FFFD}", $decoded['title']);
    }

    private function makeService(): CoolmateCrawlerService
    {
        return new class($this->tempDirectory) extends CoolmateCrawlerService
        {
            public function __construct(private readonly string $testTempDirectory) {}

            protected function tempRootDirectory(): string
            {
                return $this->testTempDirectory;
            }
        };
    }

    private function deleteTestDirectory(string $directory): void
    {
        $resolvedDirectory = realpath($directory);
        $resolvedTemp = realpath(sys_get_temp_dir());

        if (
            $resolvedDirectory === false
            || $resolvedTemp === false
            || ! str_starts_with(
                $resolvedDirectory,
                $resolvedTemp.DIRECTORY_SEPARATOR.'coolmate_test_'
            )
        ) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $resolvedDirectory,
                \FilesystemIterator::SKIP_DOTS
            ),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }

        rmdir($resolvedDirectory);
    }
}
