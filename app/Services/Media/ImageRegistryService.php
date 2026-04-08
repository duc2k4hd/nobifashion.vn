<?php

namespace App\Services\Media;

use App\Models\Image;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ImageRegistryService
{
    public function __construct(
        protected FileHelperService $files
    ) {
    }

    public function registerLooseImage(string $relativePath, array $meta = [], ?string $folderKey = null): Image
    {
        $normalizedPath = $this->normalizeStoredPath($relativePath, $folderKey);

        $image = new Image();
        $image->fill([
            'product_id' => null,
            'entity_type' => null,
            'entity_id' => null,
            'role' => 'library',
            'name' => basename($normalizedPath ?: $relativePath),
            'title' => Arr::get($meta, 'title', basename($normalizedPath ?: $relativePath)),
            'notes' => Arr::get($meta, 'description', Arr::get($meta, 'notes')),
            'alt' => Arr::get($meta, 'alt'),
            'path' => $normalizedPath,
            'url' => $normalizedPath,
            'context' => Arr::get($meta, 'context', $this->inferContext($folderKey, $normalizedPath)),
            'thumbnail_url' => null,
            'medium_url' => null,
            'is_primary' => false,
            'order' => 0,
        ]);

        $this->fillFileMetadata($image, $normalizedPath);
        $image->save();

        return $image;
    }

    public function syncEntityImage(
        string $entityType,
        int $entityId,
        string $role,
        ?string $storedPath,
        array $meta = []
    ): ?Image {
        $image = $this->findByEntity($entityType, $entityId, $role);

        if (!$storedPath) {
            if ($image) {
                $image->delete();
            }

            return null;
        }

        $normalizedPath = $this->normalizeStoredPath(
            $storedPath,
            $this->defaultFolderKey($entityType, $role)
        );

        $image ??= new Image();
        $image->fill([
            'product_id' => $entityType === 'product' ? $entityId : null,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'role' => $role,
            'name' => basename($normalizedPath ?: $storedPath),
            'title' => Arr::get($meta, 'title', $image->title ?: basename($normalizedPath ?: $storedPath)),
            'notes' => Arr::get($meta, 'description', Arr::get($meta, 'notes', $image->notes)),
            'alt' => Arr::get($meta, 'alt', $image->alt),
            'path' => $normalizedPath,
            'url' => $normalizedPath,
            'context' => Arr::get($meta, 'context', $this->contextForEntity($entityType)),
            'thumbnail_url' => $entityType === 'product' ? Arr::get($meta, 'thumbnail_url', $image->thumbnail_url) : null,
            'medium_url' => $entityType === 'product' ? Arr::get($meta, 'medium_url', $image->medium_url) : null,
            'is_primary' => $entityType === 'product'
                ? (bool) Arr::get($meta, 'is_primary', $role === 'primary')
                : false,
            'order' => (int) Arr::get($meta, 'order', $image->order ?? 0),
        ]);

        $this->fillFileMetadata($image, $normalizedPath);
        $image->save();

        return $image;
    }

    public function rebindLibraryImage(int $imageId, string $targetType, int $targetId): Image
    {
        $image = Image::findOrFail($imageId);

        $role = match ($targetType) {
            'product' => $image->is_primary ? 'primary' : 'gallery',
            'post' => 'thumbnail',
            'category' => 'image',
            'banner_desktop' => 'desktop',
            'banner_mobile' => 'mobile',
            'profile_avatar' => 'avatar',
            'profile_sub_avatar' => 'sub_avatar',
            default => 'library',
        };

        $entityType = match ($targetType) {
            'product' => 'product',
            'post' => 'post',
            'category' => 'category',
            'banner_desktop', 'banner_mobile' => 'banner',
            'profile_avatar', 'profile_sub_avatar' => 'profile',
            default => null,
        };

        $image->fill([
            'product_id' => $entityType === 'product' ? $targetId : null,
            'entity_type' => $entityType,
            'entity_id' => $entityType ? $targetId : null,
            'role' => $role,
            'context' => $entityType ? $this->contextForEntity($entityType) : 'library',
        ]);
        $image->save();

        return $image;
    }

    public function findByEntity(string $entityType, int $entityId, string $role): ?Image
    {
        return Image::query()
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->where('role', $role)
            ->latest('id')
            ->first();
    }

    public function normalizeStoredPath(?string $storedPath, ?string $fallbackFolderKey = null): ?string
    {
        $normalized = $this->files->normalizeRelativePath($storedPath);
        if (!$normalized) {
            return null;
        }

        if (Str::startsWith($normalized, ['http://', 'https://'])) {
            return $normalized;
        }

        if ($this->files->isManagedMediaPath($normalized)) {
            return $normalized;
        }

        $directories = config('media.directories', []);
        if ($fallbackFolderKey && isset($directories[$fallbackFolderKey])) {
            return trim($directories[$fallbackFolderKey], '/') . '/' . ltrim($normalized, '/');
        }

        return $normalized;
    }

    protected function fillFileMetadata(Image $image, ?string $relativePath): void
    {
        if (!$relativePath || Str::startsWith($relativePath, ['http://', 'https://'])) {
            return;
        }

        $absolutePath = $this->files->toAbsolutePath($relativePath);
        if (!$absolutePath || !is_file($absolutePath)) {
            return;
        }

        $image->extension = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
        $image->mime_type = @mime_content_type($absolutePath) ?: $image->mime_type;
        $image->size = filesize($absolutePath) ?: $image->size;
        $image->file_modified_at = date('Y-m-d H:i:s', filemtime($absolutePath) ?: time());

        $dimensions = @getimagesize($absolutePath);
        if ($dimensions) {
            $image->width = $dimensions[0] ?? $image->width;
            $image->height = $dimensions[1] ?? $image->height;
        }
    }

    protected function inferContext(?string $folderKey, ?string $normalizedPath): string
    {
        if ($folderKey) {
            return match ($folderKey) {
                'clothes' => 'product',
                'posts' => 'post',
                'categories' => 'category',
                'banners', 'accounts_banners', 'general_banners' => 'banner',
                'accounts_avatars', 'users' => 'profile',
                default => 'library',
            };
        }

        if ($normalizedPath) {
            return match (true) {
                str_contains($normalizedPath, '/clothes/') => 'product',
                str_contains($normalizedPath, '/posts/') => 'post',
                str_contains($normalizedPath, '/categories/') => 'category',
                str_contains($normalizedPath, '/banners/') => 'banner',
                str_contains($normalizedPath, '/accounts/') => 'profile',
                default => 'library',
            };
        }

        return 'library';
    }

    protected function contextForEntity(string $entityType): string
    {
        return match ($entityType) {
            'product' => 'product',
            'post' => 'post',
            'category' => 'category',
            'banner' => 'banner',
            'profile' => 'profile',
            default => 'library',
        };
    }

    protected function defaultFolderKey(string $entityType, string $role): ?string
    {
        return match ($entityType) {
            'product' => 'clothes',
            'post' => 'posts',
            'category' => 'categories',
            'banner' => 'banners',
            'profile' => 'accounts_avatars',
            default => null,
        };
    }
}
