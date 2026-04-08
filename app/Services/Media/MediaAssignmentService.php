<?php

namespace App\Services\Media;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Image;
use App\Models\Post;
use App\Models\Product;
use App\Models\Profile;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;

class MediaAssignmentService
{
    public function __construct(
        protected FileHelperService $files,
        protected ImageRegistryService $registry
    ) {
    }

    public function assignUploadedFile(string $targetType, int $targetId, array $paths, array $meta = []): array
    {
        return match ($targetType) {
            'product' => $this->assignProductImage($targetId, $paths, $meta),
            'post' => $this->assignPostThumbnail($targetId, $paths, $meta),
            'category' => $this->assignCategoryImage($targetId, $paths),
            'banner_desktop' => $this->assignBannerImage($targetId, $paths, 'desktop'),
            'banner_mobile' => $this->assignBannerImage($targetId, $paths, 'mobile'),
            'profile_avatar' => $this->assignProfileImage($targetId, $paths, 'avatar'),
            'profile_sub_avatar' => $this->assignProfileImage($targetId, $paths, 'sub_avatar'),
            default => throw new ModelNotFoundException('Target type không hợp lệ.'),
        };
    }

    public function updateMeta(string $source, string $id, array $data): bool
    {
        return match ($source) {
            'product_image' => $this->updateProductImageMeta((int) $id, $data),
            'post_thumbnail' => $this->updatePostThumbnailMeta((int) $id, $data),
            'category_image' => $this->updateCategoryMeta((int) $id, $data),
            'banner_desktop', 'banner_mobile' => $this->updateBannerMeta($source, (int) $id, $data),
            'profile_avatar', 'profile_sub_avatar' => $this->updateProfileMeta($source, (int) $id, $data),
            'library_image' => $this->updateLibraryImageMeta((int) $id, $data),
            default => false,
        };
    }

    public function delete(string $source, string $id, bool $deletePhysical = true): bool
    {
        return match ($source) {
            'product_image' => $this->deleteProductImage((int) $id, $deletePhysical),
            'post_thumbnail' => $this->clearPostThumbnail((int) $id, $deletePhysical),
            'category_image' => $this->clearCategoryImage((int) $id, $deletePhysical),
            'banner_desktop' => $this->clearBannerImage((int) $id, 'desktop', $deletePhysical),
            'banner_mobile' => $this->clearBannerImage((int) $id, 'mobile', $deletePhysical),
            'profile_avatar' => $this->clearProfileImage((int) $id, 'avatar', $deletePhysical),
            'profile_sub_avatar' => $this->clearProfileImage((int) $id, 'sub_avatar', $deletePhysical),
            'library_image' => $this->deleteLibraryImage((int) $id, $deletePhysical),
            default => false,
        };
    }

    public function assignExisting(string $targetType, int $targetId, array $paths, array $meta = []): array
    {
        $original = $paths['original'] ?? null;
        if (!$original) {
            throw new \InvalidArgumentException('Thiếu đường dẫn ảnh gốc để gán.');
        }

        $paths = [
            'original' => ltrim($original, '/'),
            'thumbnail' => isset($paths['thumbnail']) ? ltrim($paths['thumbnail'], '/') : null,
            'medium' => isset($paths['medium']) ? ltrim($paths['medium'], '/') : null,
        ];

        return $this->assignUploadedFile($targetType, $targetId, $paths, $meta);
    }

    public function attachLibraryImage(int $imageId, string $targetType, int $targetId): array
    {
        $image = $this->registry->rebindLibraryImage($imageId, $targetType, $targetId);

        if ($targetType === 'product') {
            $nextOrder = (int) Image::where('product_id', $targetId)
                ->where('id', '!=', $image->id)
                ->max('order');

            $image->product_id = $targetId;
            $image->entity_type = 'product';
            $image->entity_id = $targetId;
            $image->role = $image->is_primary ? 'primary' : 'gallery';
            $image->path = $this->prepareLegacyValueForFolder($image->path ?: $image->url, 'clothes');
            $image->url = $this->prepareLegacyValueForFolder($image->url ?: $image->path, 'clothes');
            $image->thumbnail_url = $this->prepareLegacyValueForFolder($image->thumbnail_url, 'clothes');
            $image->medium_url = $this->prepareLegacyValueForFolder($image->medium_url, 'clothes');
            $image->order = $nextOrder + 1;
            $image->context = 'product';
            $image->save();

            return [
                'id' => $image->id,
                'type' => 'product_image',
                'path' => $image->url,
            ];
        }

        return $this->assignUploadedFile($targetType, $targetId, [
            'original' => $image->path ?: $image->url,
            'thumbnail' => $image->thumbnail_url,
            'medium' => $image->medium_url,
        ], [
            'title' => $image->title,
            'alt' => $image->alt,
            'description' => $image->notes,
        ]);
    }

    protected function assignProductImage(int $productId, array $paths, array $meta): array
    {
        $product = Product::findOrFail($productId);
        $currentOrder = (int) Image::where('product_id', $productId)->max('order');
        $original = $this->registry->normalizeStoredPath($paths['original'] ?? null, 'clothes');
        $storedOriginal = $this->prepareLegacyValueForFolder($original, 'clothes');
        $storedThumbnail = $this->prepareLegacyValueForFolder($paths['thumbnail'] ?? null, 'clothes');
        $storedMedium = $this->prepareLegacyValueForFolder($paths['medium'] ?? null, 'clothes');

        $image = Image::create([
            'product_id' => $productId,
            'entity_type' => 'product',
            'entity_id' => $productId,
            'role' => (bool) ($meta['is_primary'] ?? false) ? 'primary' : 'gallery',
            'name' => basename((string) ($storedOriginal ?: $original)),
            'title' => $meta['title'] ?? $product->name,
            'notes' => $meta['description'] ?? null,
            'alt' => $meta['alt'] ?? null,
            'url' => $storedOriginal ?: $original,
            'path' => $storedOriginal ?: $original,
            'thumbnail_url' => $storedThumbnail,
            'medium_url' => $storedMedium,
            'context' => 'product',
            'is_primary' => (bool) ($meta['is_primary'] ?? false),
            'order' => $currentOrder + 1,
        ]);

        $this->syncFileMetadata($image);
        $image->save();

        if ($image->is_primary) {
            Image::where('product_id', $productId)
                ->where('id', '!=', $image->id)
                ->update(['is_primary' => false, 'role' => 'gallery']);
        }

        return [
            'id' => $image->id,
            'type' => 'product_image',
            'path' => $image->url,
        ];
    }

    protected function assignPostThumbnail(int $postId, array $paths, array $meta): array
    {
        $post = Post::findOrFail($postId);
        $post->thumbnail = $this->prepareLegacyValueForFolder($paths['original'], 'posts');
        if (isset($meta['alt'])) {
            $post->thumbnail_alt_text = $meta['alt'];
        }
        $post->save();

        return [
            'id' => $post->id,
            'type' => 'post_thumbnail',
            'path' => $post->thumbnail,
        ];
    }

    protected function assignCategoryImage(int $categoryId, array $paths): array
    {
        $category = Category::findOrFail($categoryId);
        $category->image = $this->prepareLegacyValueForFolder($paths['original'], 'categories');
        $category->save();

        return [
            'id' => $category->id,
            'type' => 'category_image',
            'path' => $category->image,
        ];
    }

    protected function assignBannerImage(int $bannerId, array $paths, string $mode): array
    {
        $banner = Banner::findOrFail($bannerId);
        if ($mode === 'desktop') {
            $banner->image_desktop = $this->prepareLegacyValueForFolder($paths['original'], 'banners');
        } else {
            $banner->image_mobile = $this->prepareLegacyValueForFolder($paths['original'], 'banners');
        }
        $banner->save();

        return [
            'id' => $banner->id,
            'type' => $mode === 'desktop' ? 'banner_desktop' : 'banner_mobile',
            'path' => $mode === 'desktop' ? $banner->image_desktop : $banner->image_mobile,
        ];
    }

    protected function assignProfileImage(int $profileId, array $paths, string $column): array
    {
        $profile = Profile::findOrFail($profileId);
        $profile->{$column} = $this->prepareLegacyValueForFolder($paths['original'], 'accounts_avatars');
        $profile->save();

        return [
            'id' => $profile->id,
            'type' => $column === 'avatar' ? 'profile_avatar' : 'profile_sub_avatar',
            'path' => $profile->{$column},
        ];
    }

    protected function updateProductImageMeta(int $imageId, array $data): bool
    {
        $image = Image::findOrFail($imageId);
        $image->fill([
            'title' => $data['title'] ?? $image->title,
            'notes' => $data['description'] ?? $image->notes,
            'alt' => $data['alt'] ?? $image->alt,
        ]);

        if (array_key_exists('is_primary', $data)) {
            $image->is_primary = (bool) $data['is_primary'];
            $image->role = $image->is_primary ? 'primary' : 'gallery';

            if ($image->is_primary) {
                Image::where('product_id', $image->product_id)
                    ->where('id', '!=', $image->id)
                    ->update(['is_primary' => false, 'role' => 'gallery']);
            }
        }

        return $image->save();
    }

    protected function updatePostThumbnailMeta(int $postId, array $data): bool
    {
        $post = Post::findOrFail($postId);
        $image = $this->registry->findByEntity('post', $postId, 'thumbnail');

        if ($image) {
            $image->fill([
                'title' => $data['title'] ?? $image->title,
                'notes' => $data['description'] ?? $image->notes,
                'alt' => $data['alt'] ?? $image->alt,
            ]);
            $image->save();
        }

        if (isset($data['alt'])) {
            $post->thumbnail_alt_text = $data['alt'];
        }

        return $post->save();
    }

    protected function updateCategoryMeta(int $categoryId, array $data): bool
    {
        $category = Category::findOrFail($categoryId);
        $image = $this->registry->findByEntity('category', $categoryId, 'image');

        if ($image) {
            $image->fill([
                'title' => $data['title'] ?? $image->title,
                'notes' => $data['description'] ?? $image->notes,
                'alt' => $data['alt'] ?? $image->alt,
            ]);
            $image->save();
        }

        if (isset($data['description'])) {
            $category->description = $data['description'];
        }

        return $category->save();
    }

    protected function updateBannerMeta(string $source, int $bannerId, array $data): bool
    {
        $banner = Banner::findOrFail($bannerId);
        $role = $source === 'banner_mobile' ? 'mobile' : 'desktop';
        $image = $this->registry->findByEntity('banner', $bannerId, $role);

        if ($image) {
            $image->fill([
                'title' => $data['title'] ?? $image->title,
                'notes' => $data['description'] ?? $image->notes,
                'alt' => $data['alt'] ?? $image->alt,
            ]);
            $image->save();
        }

        if (isset($data['title'])) {
            $banner->title = $data['title'];
        }
        if (isset($data['description'])) {
            $banner->description = $data['description'];
        }

        return $banner->save();
    }

    protected function updateProfileMeta(string $source, int $profileId, array $data): bool
    {
        $profile = Profile::findOrFail($profileId);
        $role = $source === 'profile_sub_avatar' ? 'sub_avatar' : 'avatar';
        $image = $this->registry->findByEntity('profile', $profileId, $role);

        if ($image) {
            $image->fill([
                'title' => $data['title'] ?? $image->title,
                'notes' => $data['description'] ?? $image->notes,
                'alt' => $data['alt'] ?? $image->alt,
            ]);
            $image->save();
        }

        if (isset($data['title'])) {
            $profile->nickname = $data['title'];
        }
        if (isset($data['description'])) {
            $profile->bio = $data['description'];
        }

        return $profile->save();
    }

    protected function updateLibraryImageMeta(int $imageId, array $data): bool
    {
        $image = Image::findOrFail($imageId);
        $image->fill([
            'title' => $data['title'] ?? $image->title,
            'notes' => $data['description'] ?? $image->notes,
            'alt' => $data['alt'] ?? $image->alt,
        ]);

        return $image->save();
    }

    protected function deleteProductImage(int $imageId, bool $deletePhysical = true): bool
    {
        $image = Image::findOrFail($imageId);
        if ($deletePhysical) {
            $this->deleteManagedPath($image->path ?: $image->url, 'clothes');
            $this->deleteManagedPath($image->thumbnail_url, 'clothes');
            $this->deleteManagedPath($image->medium_url, 'clothes');
        }

        return (bool) $image->delete();
    }

    protected function clearPostThumbnail(int $postId, bool $deletePhysical = true): bool
    {
        $post = Post::findOrFail($postId);
        if ($deletePhysical) {
            $this->deleteManagedPath($post->thumbnail, 'posts');
        }

        $post->thumbnail = null;
        $post->thumbnail_alt_text = null;

        return $post->save();
    }

    protected function clearCategoryImage(int $categoryId, bool $deletePhysical = true): bool
    {
        $category = Category::findOrFail($categoryId);
        if ($deletePhysical) {
            $this->deleteManagedPath($category->image, 'categories');
        }

        $category->image = null;
        return $category->save();
    }

    protected function clearBannerImage(int $bannerId, string $mode, bool $deletePhysical = true): bool
    {
        $banner = Banner::findOrFail($bannerId);
        $label = $mode === 'desktop' ? 'desktop' : 'mobile';

        throw new \DomainException("Banner bắt buộc phải có ảnh {$label}. Hãy gán ảnh mới thay vì xóa trắng.");
    }

    protected function clearProfileImage(int $profileId, string $column, bool $deletePhysical = true): bool
    {
        $profile = Profile::findOrFail($profileId);
        if ($deletePhysical) {
            $this->deleteManagedPath($profile->{$column}, 'accounts_avatars');
        }

        $profile->{$column} = null;
        return $profile->save();
    }

    protected function deleteLibraryImage(int $imageId, bool $deletePhysical = true): bool
    {
        $image = Image::findOrFail($imageId);
        if ($deletePhysical) {
            $this->deleteManagedPath($image->path ?: $image->url);
        }

        return (bool) $image->delete();
    }

    protected function syncFileMetadata(Image $image): void
    {
        $path = $image->path ?: $image->url;
        if (!$path) {
            return;
        }

        $absolute = $this->files->toAbsolutePath($path);
        if ((!$absolute || !is_file($absolute)) && $image->context === 'product') {
            $normalized = $this->registry->normalizeStoredPath($path, 'clothes');
            $absolute = $normalized ? $this->files->toAbsolutePath($normalized) : $absolute;
        }
        if (!$absolute || !is_file($absolute)) {
            return;
        }

        $image->extension = strtolower(pathinfo($absolute, PATHINFO_EXTENSION));
        $image->mime_type = @mime_content_type($absolute) ?: $image->mime_type;
        $image->size = filesize($absolute) ?: $image->size;
        $image->file_modified_at = date('Y-m-d H:i:s', filemtime($absolute) ?: time());

        $dimensions = @getimagesize($absolute);
        if ($dimensions) {
            $image->width = $dimensions[0] ?? $image->width;
            $image->height = $dimensions[1] ?? $image->height;
        }
    }

    protected function prepareLegacyValueForFolder(?string $path, string $folderKey): ?string
    {
        $normalized = $this->files->normalizeRelativePath($path);
        if (!$normalized) {
            return null;
        }

        if (Str::startsWith($normalized, ['http://', 'https://'])) {
            return $normalized;
        }

        $directories = config('media.directories', []);
        $targetDirectory = trim((string) ($directories[$folderKey] ?? ''), '/');
        if ($targetDirectory === '') {
            return basename($normalized);
        }

        if ($this->files->isManagedMediaPath($normalized, $directories) && Str::startsWith($normalized, $targetDirectory . '/')) {
            return basename($normalized);
        }

        $sourceAbsolute = $this->files->toAbsolutePath($normalized);
        if ($sourceAbsolute && is_file($sourceAbsolute)) {
            $targetFilename = basename($normalized);
            $targetRelative = $targetDirectory . '/' . $targetFilename;
            $targetAbsolute = public_path($targetRelative);

            $this->files->ensureDirectory(dirname($targetAbsolute));
            if (is_file($targetAbsolute) && realpath($targetAbsolute) !== realpath($sourceAbsolute) && md5_file($targetAbsolute) !== md5_file($sourceAbsolute)) {
                $info = pathinfo($targetFilename);
                $targetFilename = ($info['filename'] ?? 'media') . '-' . now()->format('YmdHis') . '-' . Str::random(4) . '.' . ($info['extension'] ?? 'jpg');
                $targetRelative = $targetDirectory . '/' . $targetFilename;
                $targetAbsolute = public_path($targetRelative);
            }

            if (!is_file($targetAbsolute)) {
                copy($sourceAbsolute, $targetAbsolute);
                @chmod($targetAbsolute, 0644);
            }

            return $targetFilename;
        }

        return basename($normalized);
    }

    protected function deleteManagedPath(?string $path, ?string $fallbackFolderKey = null): void
    {
        $normalized = $this->registry->normalizeStoredPath($path, $fallbackFolderKey);
        if ($normalized && Str::startsWith($normalized, ['http://', 'https://'])) {
            return;
        }

        $this->files->deleteFile($normalized ?: $path);
    }
}
