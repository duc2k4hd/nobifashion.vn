<?php

namespace Tests\Unit;

use App\Models\Image;
use App\Services\Media\MediaScannerService;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

class MediaScannerServiceTest extends TestCase
{
    protected MediaScannerService $scanner;

    /**
     * @var string[]
     */
    protected array $createdFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->scanner = app(MediaScannerService::class);
    }

    protected function tearDown(): void
    {
        foreach ($this->createdFiles as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }

        parent::tearDown();
    }

    #[Test]
    public function it_normalizes_media_filters_before_searching(): void
    {
        $filters = $this->invokeProtected('normalizeFilters', [[
            'type' => 'invalid-type',
            'folder' => 'invalid-folder',
            'status' => 'invalid-status',
            'q' => '  keyword  ',
            'direction' => 'invalid',
            'per_page' => 50000,
            'page' => -10,
        ]]);

        $this->assertSame('all', $filters['type']);
        $this->assertSame('all', $filters['folder']);
        $this->assertSame('all', $filters['status']);
        $this->assertSame('keyword', $filters['q']);
        $this->assertSame('desc', $filters['direction']);
        $this->assertSame(2000, $filters['per_page']);
        $this->assertSame(1, $filters['page']);
    }

    #[Test]
    public function it_only_uses_deep_scan_for_expensive_media_filters(): void
    {
        $this->assertFalse($this->invokeProtected('requiresDeepScan', [[
            'type' => 'all',
            'status' => 'all',
        ]]));

        $this->assertTrue($this->invokeProtected('requiresDeepScan', [[
            'type' => 'filesystem_file',
            'status' => 'all',
        ]]));

        $this->assertTrue($this->invokeProtected('requiresDeepScan', [[
            'type' => 'all',
            'status' => 'orphan_file',
        ]]));

        $this->assertTrue($this->invokeProtected('requiresDeepScan', [[
            'type' => 'all',
            'status' => 'missing_file',
        ]]));
    }

    #[Test]
    public function it_maps_fast_media_item_using_table_metadata_and_local_file_state(): void
    {
        $relativePath = 'clients/assets/img/clothes/media-scanner-fast-test.webp';
        $absolutePath = public_path($relativePath);

        File::ensureDirectoryExists(dirname($absolutePath));
        File::put($absolutePath, 'fast-media-test');
        $this->createdFiles[] = $absolutePath;

        $image = new Image([
            'id' => 999,
            'name' => 'media-scanner-fast-test.webp',
            'path' => $relativePath,
            'url' => $relativePath,
            'context' => 'product',
            'size' => 2048,
            'mime_type' => 'image/webp',
            'extension' => 'webp',
            'is_primary' => false,
            'order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $item = $this->invokeProtected('mapFastImageRecord', [
            $image,
            [],
            [$relativePath => 2],
        ]);

        $this->assertSame('library_image', $item['type']);
        $this->assertSame($relativePath, $item['relative_path']);
        $this->assertSame('clothes', $item['folder_key']);
        $this->assertSame(2, $item['usage_count']);
        $this->assertTrue($item['is_shared']);
        $this->assertContains('unassigned_record', $item['status_flags']);
        $this->assertContains('shared_file', $item['status_flags']);
        $this->assertSame('Ảnh thư viện', $item['type_label']);
    }

    protected function invokeProtected(string $method, array $arguments = []): mixed
    {
        $reflection = new ReflectionMethod($this->scanner, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($this->scanner, $arguments);
    }
}
