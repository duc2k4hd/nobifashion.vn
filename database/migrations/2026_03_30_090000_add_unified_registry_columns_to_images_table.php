<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected array $directories = [];

    public function __construct()
    {
        $this->directories = config('media.directories', []);
    }

    public function up(): void
    {
        if (!Schema::hasTable('images')) {
            return;
        }

        Schema::table('images', function (Blueprint $table) {
            if (!Schema::hasColumn('images', 'name')) {
                $table->string('name')->nullable()->after('id');
            }
            if (!Schema::hasColumn('images', 'entity_type')) {
                $table->string('entity_type', 50)->nullable()->after('product_id')->index();
            }
            if (!Schema::hasColumn('images', 'entity_id')) {
                $table->unsignedBigInteger('entity_id')->nullable()->after('entity_type')->index();
            }
            if (!Schema::hasColumn('images', 'role')) {
                $table->string('role', 50)->nullable()->after('entity_id')->index();
            }
        });

        $this->backfillExistingImageRows();
        $this->backfillPosts();
        $this->backfillCategories();
        $this->backfillBanners();
        $this->backfillProfiles();
    }

    public function down(): void
    {
        if (!Schema::hasTable('images')) {
            return;
        }

        Schema::table('images', function (Blueprint $table) {
            foreach (['role', 'entity_id', 'entity_type', 'name'] as $column) {
                if (Schema::hasColumn('images', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    protected function backfillExistingImageRows(): void
    {
        DB::table('images')
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    $context = $row->context ?: ($row->product_id ? 'product' : $this->inferContextFromPath($row->path ?: $row->url));
                    $folderKey = $this->defaultFolderKeyFor($context, $row->product_id ? 'product' : null);
                    $path = $this->normalizeStoredPath($row->path ?: $row->url, $folderKey);
                    $url = $this->normalizeStoredPath($row->url ?: $row->path, $folderKey) ?: $row->url;
                    $name = $row->name ?: $this->extractFileName($path ?: $url);

                    DB::table('images')
                        ->where('id', $row->id)
                        ->update([
                            'name' => $name,
                            'path' => $path ?: $row->path,
                            'url' => $url ?: $row->url,
                            'entity_type' => $row->entity_type ?: ($row->product_id ? 'product' : null),
                            'entity_id' => $row->entity_id ?: ($row->product_id ?: null),
                            'role' => $row->role ?: $this->resolveDefaultRole($row->product_id, $row->is_primary ?? false),
                            'context' => $context ?: $row->context,
                        ]);
                }
            });
    }

    protected function backfillPosts(): void
    {
        if (!Schema::hasTable('posts')) {
            return;
        }

        $existing = $this->loadExistingKeys();
        $rowsToInsert = [];

        DB::table('posts')
            ->select('id', 'title', 'thumbnail', 'thumbnail_alt_text', 'created_at', 'updated_at')
            ->whereNotNull('thumbnail')
            ->orderBy('id')
            ->chunkById(200, function ($posts) use (&$existing, &$rowsToInsert) {
                foreach ($posts as $post) {
                    $key = $this->makeKey('post', (int) $post->id, 'thumbnail');
                    if (isset($existing[$key])) {
                        continue;
                    }

                    $rowsToInsert[] = $this->buildRow(
                        entityType: 'post',
                        entityId: (int) $post->id,
                        role: 'thumbnail',
                        storedPath: $post->thumbnail,
                        title: $post->title,
                        alt: $post->thumbnail_alt_text,
                        context: 'post',
                        createdAt: $post->created_at,
                        updatedAt: $post->updated_at
                    );

                    $existing[$key] = true;
                }

                $this->flushInsertRows($rowsToInsert);
            });

        $this->flushInsertRows($rowsToInsert);
    }

    protected function backfillCategories(): void
    {
        if (!Schema::hasTable('categories')) {
            return;
        }

        $existing = $this->loadExistingKeys();
        $rowsToInsert = [];

        DB::table('categories')
            ->select('id', 'name', 'image', 'description', 'created_at', 'updated_at')
            ->whereNotNull('image')
            ->orderBy('id')
            ->chunkById(200, function ($categories) use (&$existing, &$rowsToInsert) {
                foreach ($categories as $category) {
                    $key = $this->makeKey('category', (int) $category->id, 'image');
                    if (isset($existing[$key])) {
                        continue;
                    }

                    $rowsToInsert[] = $this->buildRow(
                        entityType: 'category',
                        entityId: (int) $category->id,
                        role: 'image',
                        storedPath: $category->image,
                        title: $category->name,
                        alt: $category->name,
                        context: 'category',
                        description: $category->description,
                        createdAt: $category->created_at,
                        updatedAt: $category->updated_at
                    );

                    $existing[$key] = true;
                }

                $this->flushInsertRows($rowsToInsert);
            });

        $this->flushInsertRows($rowsToInsert);
    }

    protected function backfillBanners(): void
    {
        if (!Schema::hasTable('banners')) {
            return;
        }

        $existing = $this->loadExistingKeys();
        $rowsToInsert = [];

        DB::table('banners')
            ->select('id', 'title', 'description', 'image_desktop', 'image_mobile', 'created_at', 'updated_at')
            ->orderBy('id')
            ->chunkById(200, function ($banners) use (&$existing, &$rowsToInsert) {
                foreach ($banners as $banner) {
                    if ($banner->image_desktop) {
                        $key = $this->makeKey('banner', (int) $banner->id, 'desktop');
                        if (!isset($existing[$key])) {
                            $rowsToInsert[] = $this->buildRow(
                                entityType: 'banner',
                                entityId: (int) $banner->id,
                                role: 'desktop',
                                storedPath: $banner->image_desktop,
                                title: $banner->title,
                                alt: $banner->title,
                                context: 'banner',
                                description: $banner->description,
                                createdAt: $banner->created_at,
                                updatedAt: $banner->updated_at
                            );
                            $existing[$key] = true;
                        }
                    }

                    if ($banner->image_mobile) {
                        $key = $this->makeKey('banner', (int) $banner->id, 'mobile');
                        if (!isset($existing[$key])) {
                            $rowsToInsert[] = $this->buildRow(
                                entityType: 'banner',
                                entityId: (int) $banner->id,
                                role: 'mobile',
                                storedPath: $banner->image_mobile,
                                title: $banner->title,
                                alt: $banner->title,
                                context: 'banner',
                                description: $banner->description,
                                createdAt: $banner->created_at,
                                updatedAt: $banner->updated_at
                            );
                            $existing[$key] = true;
                        }
                    }
                }

                $this->flushInsertRows($rowsToInsert);
            });

        $this->flushInsertRows($rowsToInsert);
    }

    protected function backfillProfiles(): void
    {
        if (!Schema::hasTable('profiles')) {
            return;
        }

        $existing = $this->loadExistingKeys();
        $rowsToInsert = [];

        DB::table('profiles')
            ->select('id', 'full_name', 'nickname', 'avatar', 'sub_avatar', 'created_at', 'updated_at')
            ->orderBy('id')
            ->chunkById(200, function ($profiles) use (&$existing, &$rowsToInsert) {
                foreach ($profiles as $profile) {
                    $label = $profile->full_name ?: $profile->nickname ?: ('Profile #' . $profile->id);

                    if ($profile->avatar) {
                        $key = $this->makeKey('profile', (int) $profile->id, 'avatar');
                        if (!isset($existing[$key])) {
                            $rowsToInsert[] = $this->buildRow(
                                entityType: 'profile',
                                entityId: (int) $profile->id,
                                role: 'avatar',
                                storedPath: $profile->avatar,
                                title: $label,
                                alt: $label,
                                context: 'profile',
                                createdAt: $profile->created_at,
                                updatedAt: $profile->updated_at
                            );
                            $existing[$key] = true;
                        }
                    }

                    if ($profile->sub_avatar) {
                        $key = $this->makeKey('profile', (int) $profile->id, 'sub_avatar');
                        if (!isset($existing[$key])) {
                            $rowsToInsert[] = $this->buildRow(
                                entityType: 'profile',
                                entityId: (int) $profile->id,
                                role: 'sub_avatar',
                                storedPath: $profile->sub_avatar,
                                title: $label,
                                alt: $label,
                                context: 'profile',
                                createdAt: $profile->created_at,
                                updatedAt: $profile->updated_at
                            );
                            $existing[$key] = true;
                        }
                    }
                }

                $this->flushInsertRows($rowsToInsert);
            });

        $this->flushInsertRows($rowsToInsert);
    }

    protected function loadExistingKeys(): array
    {
        return DB::table('images')
            ->select('entity_type', 'entity_id', 'role')
            ->whereNotNull('entity_type')
            ->whereNotNull('entity_id')
            ->whereNotNull('role')
            ->get()
            ->mapWithKeys(fn ($row) => [$this->makeKey($row->entity_type, (int) $row->entity_id, $row->role) => true])
            ->all();
    }

    protected function flushInsertRows(array &$rows): void
    {
        if (empty($rows)) {
            return;
        }

        DB::table('images')->insert($rows);
        $rows = [];
    }

    protected function buildRow(
        string $entityType,
        int $entityId,
        string $role,
        string $storedPath,
        ?string $title,
        ?string $alt,
        string $context,
        ?string $description = null,
        ?string $createdAt = null,
        ?string $updatedAt = null
    ): array {
        $folderKey = $this->defaultFolderKeyFor($context, $entityType);
        $path = $this->normalizeStoredPath($storedPath, $folderKey);
        $metadata = $this->inspectPublicFile($path);
        $name = $this->extractFileName($path ?: $storedPath);
        $timestamp = now()->toDateTimeString();

        return [
            'product_id' => $entityType === 'product' ? $entityId : null,
            'name' => $name,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'role' => $role,
            'title' => $title ?: $name,
            'notes' => $description,
            'alt' => $alt ?: $title ?: $name,
            'url' => $path ?: $storedPath,
            'path' => $path ?: $storedPath,
            'extension' => $metadata['extension'] ?? strtolower(pathinfo((string) $storedPath, PATHINFO_EXTENSION)),
            'mime_type' => $metadata['mime_type'] ?? null,
            'size' => $metadata['size'] ?? 0,
            'width' => $metadata['width'] ?? null,
            'height' => $metadata['height'] ?? null,
            'context' => $context,
            'file_modified_at' => $metadata['modified_at'] ?? null,
            'thumbnail_url' => null,
            'medium_url' => null,
            'is_primary' => $role === 'primary',
            'order' => 0,
            'created_at' => $createdAt ?: $timestamp,
            'updated_at' => $updatedAt ?: $timestamp,
        ];
    }

    protected function resolveDefaultRole(?int $productId, bool $isPrimary): string
    {
        if ($productId) {
            return $isPrimary ? 'primary' : 'gallery';
        }

        return 'library';
    }

    protected function makeKey(string $entityType, int $entityId, string $role): string
    {
        return $entityType . ':' . $entityId . ':' . $role;
    }

    protected function extractFileName(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        $parsed = parse_url($path, PHP_URL_PATH);
        return basename($parsed ?: $path);
    }

    protected function inferContextFromPath(?string $path): ?string
    {
        $normalized = $this->normalizeStoredPath($path, null);
        if (!$normalized) {
            return null;
        }

        $folderKey = $this->detectFolderKey($normalized);

        return match ($folderKey) {
            'clothes' => 'product',
            'posts' => 'post',
            'categories' => 'category',
            'banners', 'accounts_banners', 'general_banners' => 'banner',
            'accounts_avatars', 'users' => 'profile',
            default => 'library',
        };
    }

    protected function defaultFolderKeyFor(?string $context, ?string $entityType): ?string
    {
        return match ($entityType ?: $context) {
            'product' => 'clothes',
            'post' => 'posts',
            'category' => 'categories',
            'banner' => 'banners',
            'profile' => 'accounts_avatars',
            default => null,
        };
    }

    protected function normalizeStoredPath(?string $path, ?string $fallbackFolderKey): ?string
    {
        if (!$path) {
            return null;
        }

        $normalized = trim(str_replace('\\', '/', $path));
        if ($normalized === '') {
            return null;
        }

        if (str_starts_with($normalized, 'http://') || str_starts_with($normalized, 'https://')) {
            return $normalized;
        }

        $normalized = ltrim($normalized, '/');
        if (str_starts_with($normalized, 'public/')) {
            $normalized = substr($normalized, 7);
        }

        if ($this->detectFolderKey($normalized)) {
            return $normalized;
        }

        if ($fallbackFolderKey && isset($this->directories[$fallbackFolderKey])) {
            return trim($this->directories[$fallbackFolderKey], '/') . '/' . ltrim($normalized, '/');
        }

        return $normalized;
    }

    protected function detectFolderKey(?string $relativePath): ?string
    {
        if (!$relativePath || str_starts_with($relativePath, 'http://') || str_starts_with($relativePath, 'https://')) {
            return null;
        }

        $normalized = trim($relativePath, '/');
        foreach ($this->directories as $key => $directory) {
            $directory = trim(str_replace('\\', '/', $directory), '/');
            if ($normalized === $directory || str_starts_with($normalized, $directory . '/')) {
                return $key;
            }
        }

        return null;
    }

    protected function inspectPublicFile(?string $relativePath): array
    {
        if (!$relativePath || str_starts_with($relativePath, 'http://') || str_starts_with($relativePath, 'https://')) {
            return [];
        }

        $absolutePath = public_path($relativePath);
        if (!is_file($absolutePath)) {
            return [];
        }

        $info = @getimagesize($absolutePath) ?: null;

        return [
            'size' => filesize($absolutePath) ?: 0,
            'mime_type' => @mime_content_type($absolutePath) ?: null,
            'extension' => strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION)),
            'width' => $info[0] ?? null,
            'height' => $info[1] ?? null,
            'modified_at' => date('Y-m-d H:i:s', filemtime($absolutePath) ?: time()),
        ];
    }
};
