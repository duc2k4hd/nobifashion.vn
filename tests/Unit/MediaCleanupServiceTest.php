<?php

namespace Tests\Unit;

use App\Models\Image;
use App\Services\Media\MediaCleanupService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MediaCleanupServiceTest extends TestCase
{
    /**
     * @var string[]
     */
    protected array $createdDirectories = [];

    protected function setUp(): void
    {
        parent::setUp();

        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('Môi trường hiện tại chưa bật pdo_sqlite.');
        }

        Config::set('media.directories', [
            'clothes' => 'tests-media/clothes',
            'posts' => 'tests-media/posts',
            'other' => 'tests-media/other',
        ]);
        Config::set('media.cleanup_directories', [
            'clothes',
            'posts',
        ]);

        Schema::dropIfExists('images');
        Schema::dropIfExists('posts');
        Schema::create('images', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('entity_type', 50)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('role', 50)->nullable();
            $table->string('context', 50)->nullable();
            $table->string('path')->nullable();
            $table->string('url')->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->string('medium_url')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->text('content')->nullable();
            $table->string('thumbnail')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        foreach ($this->createdDirectories as $directory) {
            if (is_dir($directory)) {
                File::deleteDirectory($directory);
            }
        }

        parent::tearDown();
    }

    #[Test]
    public function it_cleans_missing_rows_unassigned_rows_and_orphan_files_without_touching_shared_assets(): void
    {
        $usedPath = $this->createMediaFile('tests-media/clothes/used.webp');
        $sharedPath = $this->createMediaFile('tests-media/clothes/shared.webp');
        $unassignedPath = $this->createMediaFile('tests-media/clothes/unassigned.webp');
        $orphanPath = $this->createMediaFile('tests-media/clothes/orphan.webp');

        Image::query()->insert([
            [
                'id' => 1,
                'name' => 'used.webp',
                'entity_type' => 'product',
                'entity_id' => 10,
                'product_id' => 10,
                'context' => 'product',
                'path' => $usedPath,
                'url' => $usedPath,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'name' => 'missing.webp',
                'entity_type' => 'product',
                'entity_id' => 11,
                'product_id' => 11,
                'context' => 'product',
                'path' => 'tests-media/clothes/missing.webp',
                'url' => 'tests-media/clothes/missing.webp',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'name' => 'unassigned.webp',
                'entity_type' => 'library',
                'context' => 'product',
                'path' => $unassignedPath,
                'url' => $unassignedPath,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 4,
                'name' => 'shared.webp',
                'entity_type' => 'product',
                'entity_id' => 12,
                'product_id' => 12,
                'context' => 'product',
                'path' => $sharedPath,
                'url' => $sharedPath,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 5,
                'name' => 'shared.webp',
                'entity_type' => 'library',
                'context' => 'product',
                'path' => $sharedPath,
                'url' => $sharedPath,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $service = app(MediaCleanupService::class);

        $preview = $service->preview();

        $this->assertSame(3, $preview['database_rows_to_delete']);
        $this->assertSame(2, $preview['physical_files_to_delete']);
        $this->assertSame(1, $preview['missing_database_rows']);
        $this->assertSame(2, $preview['unassigned_database_rows']);
        $this->assertSame(2, $preview['orphan_physical_files']);
        $this->assertSame(1, $preview['preserved_shared_files']);

        $result = $service->cleanup();

        $this->assertSame(3, $result['database_rows_deleted']);
        $this->assertSame(2, $result['physical_files_deleted']);
        $this->assertSame(0, $result['physical_files_failed_count']);
        $this->assertSame([1, 4], Image::query()->orderBy('id')->pluck('id')->all());
        $this->assertFileExists(public_path($usedPath));
        $this->assertFileExists(public_path($sharedPath));
        $this->assertFileDoesNotExist(public_path($unassignedPath));
        $this->assertFileDoesNotExist(public_path($orphanPath));
    }

    #[Test]
    public function it_ignores_records_and_files_outside_cleanup_directories(): void
    {
        Config::set('media.cleanup_directories', ['clothes']);

        $safeOrphanPath = $this->createMediaFile('tests-media/clothes/safe-orphan.webp');
        $unsafeUnassignedPath = $this->createMediaFile('tests-media/other/unsafe-unassigned.webp');
        $unsafeOrphanPath = $this->createMediaFile('tests-media/other/unsafe-orphan.webp');

        Image::query()->insert([
            [
                'id' => 11,
                'name' => 'unsafe-unassigned.webp',
                'entity_type' => 'library',
                'context' => 'product',
                'path' => $unsafeUnassignedPath,
                'url' => $unsafeUnassignedPath,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $service = app(MediaCleanupService::class);

        $preview = $service->preview();

        $this->assertSame(['clothes'], $preview['scanned_directory_keys']);
        $this->assertSame(0, $preview['database_rows_to_delete']);
        $this->assertSame(1, $preview['physical_files_to_delete']);
        $this->assertSame([$safeOrphanPath], $preview['samples']['orphan_physical_files']);

        $result = $service->cleanup();

        $this->assertSame(0, $result['database_rows_deleted']);
        $this->assertSame(1, $result['physical_files_deleted']);
        $this->assertDatabaseHas('images', ['id' => 11]);
        $this->assertFileDoesNotExist(public_path($safeOrphanPath));
        $this->assertFileExists(public_path($unsafeUnassignedPath));
        $this->assertFileExists(public_path($unsafeOrphanPath));
    }

    #[Test]
    public function it_preserves_library_images_that_are_embedded_inside_post_content(): void
    {
        $embeddedImagePath = $this->createMediaFile('tests-media/posts/content-image.webp');

        DB::table('posts')->insert([
            'id' => 1,
            'content' => '<p><img src="https://nobifashion.vn/' . $embeddedImagePath . '?v=1" alt="Content image"></p>',
            'thumbnail' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Image::query()->insert([
            'id' => 21,
            'name' => 'content-image.webp',
            'entity_type' => 'library',
            'context' => 'post',
            'path' => $embeddedImagePath,
            'url' => 'content-image.webp',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = app(MediaCleanupService::class);

        $preview = $service->preview();

        $this->assertSame(0, $preview['database_rows_to_delete']);
        $this->assertSame(0, $preview['physical_files_to_delete']);

        $result = $service->cleanup();

        $this->assertSame(0, $result['database_rows_deleted']);
        $this->assertSame(0, $result['physical_files_deleted']);
        $this->assertDatabaseHas('images', ['id' => 21]);
        $this->assertFileExists(public_path($embeddedImagePath));
    }

    #[Test]
    public function it_can_fall_back_to_url_when_path_is_stale_but_the_file_still_exists(): void
    {
        $actualImagePath = $this->createMediaFile('tests-media/posts/fallback-image.webp');

        DB::table('posts')->insert([
            'id' => 2,
            'content' => '<p><img src="/' . $actualImagePath . '" alt="Fallback image"></p>',
            'thumbnail' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Image::query()->insert([
            'id' => 22,
            'name' => 'fallback-image.webp',
            'entity_type' => 'library',
            'context' => 'post',
            'path' => 'tests-media/posts/missing-image.webp',
            'url' => 'fallback-image.webp',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = app(MediaCleanupService::class);

        $preview = $service->preview();

        $this->assertSame(0, $preview['database_rows_to_delete']);
        $this->assertSame(0, $preview['physical_files_to_delete']);

        $result = $service->cleanup();

        $this->assertSame(0, $result['database_rows_deleted']);
        $this->assertSame(0, $result['physical_files_deleted']);
        $this->assertDatabaseHas('images', ['id' => 22]);
        $this->assertFileExists(public_path($actualImagePath));
    }

    protected function createMediaFile(string $relativePath): string
    {
        $absolutePath = public_path($relativePath);
        $directory = dirname($absolutePath);

        File::ensureDirectoryExists($directory);
        File::put($absolutePath, 'media-cleanup-test');

        $this->createdDirectories[$directory] = $directory;

        return str_replace('\\', '/', $relativePath);
    }
}
