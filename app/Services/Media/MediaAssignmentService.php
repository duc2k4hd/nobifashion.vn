<?php

namespace App\Services\Media;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Image;
use App\Models\Post;
use App\Models\Profile;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class MediaAssignmentService
{
    protected FileHelperService $files;

    public function __construct(FileHelperService $files)
    {
        $this->files = $files;
    }

    /**
     * Gán ảnh mới upload vào entity tương ứng.
     *
     * @param  array{original:string, thumbnail?:string, medium?:string, webp?:string}  $paths
     * @param  array{title?:string, alt?:string, caption?:string, description?:string, is_primary?:bool}  $meta
     */
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

    /**
     * Cập nhật metadata cho media.
     */
    public function updateMeta(string $source, string $id, array $data): bool
    {
        return match ($source) {
            'product_image' => $this->updateProductImageMeta((int) $id, $data),
            'post_thumbnail' => $this->updatePostThumbnailMeta((int) $id, $data),
            'category_image' => $this->updateCategoryMeta((int) $id, $data),
            'banner_desktop', 'banner_mobile' => $this->updateBannerMeta($source, (int) $id, $data),
            'profile_avatar', 'profile_sub_avatar' => $this->updateProfileMeta($source, (int) $id, $data),
            default => false,
        };
    }

    /**
     * Xóa media khỏi entity tương ứng.
     */
    public function delete(string $source, string $id): bool
    {
        return match ($source) {
            'product_image' => $this->deleteProductImage((int) $id),
            'post_thumbnail' => $this->clearPostThumbnail((int) $id),
            'category_image' => $this->clearCategoryImage((int) $id),
            'banner_desktop' => $this->clearBannerImage((int) $id, 'desktop'),
            'banner_mobile' => $this->clearBannerImage((int) $id, 'mobile'),
            'profile_avatar' => $this->clearProfileImage((int) $id, 'avatar'),
            'profile_sub_avatar' => $this->clearProfileImage((int) $id, 'sub_avatar'),
            default => false,
        };
    }

    /**
     * Chọn ảnh hiện có làm ảnh chính / thumbnail mới.
     */
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

    protected function assignProductImage(int $productId, array $paths, array $meta): array
    {
        $currentOrder = (int) Image::where('product_id', $productId)->max('order');

        $image = Image::create([
            'product_id' => $productId,
            'title' => $meta['title'] ?? null,
            'notes' => $meta['description'] ?? null,
            'alt' => $meta['alt'] ?? null,
            'url' => $paths['original'],
            'thumbnail_url' => $paths['thumbnail'] ?? null,
            'medium_url' => $paths['medium'] ?? null,
            'is_primary' => (bool) ($meta['is_primary'] ?? false),
            'order' => $currentOrder + 1,
        ]);

        if ($image->is_primary) {
            Image::where('product_id', $productId)
                ->where('id', '!=', $image->id)
                ->update(['is_primary' => false]);
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
        $post->thumbnail = $paths['original'];
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
        $category->image = $paths['original'];
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
            $banner->image_desktop = $paths['original'];
        } else {
            $banner->image_mobile = $paths['original'];
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
        $profile->{$column} = $paths['original'];
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
            if ($image->is_primary) {
                Image::where('product_id', $image->product_id)
                    ->where('id', '!=', $image->id)
                    ->update(['is_primary' => false]);
            }
        }

        return $image->save();
    }

    protected function updatePostThumbnailMeta(int $postId, array $data): bool
    {
        $post = Post::findOrFail($postId);
        if (isset($data['alt'])) {
            $post->thumbnail_alt_text = $data['alt'];
        }

        return $post->save();
    }

    protected function updateCategoryMeta(int $categoryId, array $data): bool
    {
        $category = Category::findOrFail($categoryId);
        if (isset($data['description'])) {
            $category->description = $data['description'];
        }
        return $category->save();
    }

    protected function updateBannerMeta(string $source, int $bannerId, array $data): bool
    {
        $banner = Banner::findOrFail($bannerId);
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
        if (isset($data['title'])) {
            $profile->nickname = $data['title'];
        }
        if (isset($data['description'])) {
            $profile->bio = $data['description'];
        }
        return $profile->save();
    }

    protected function deleteProductImage(int $imageId): bool
    {
        $image = Image::findOrFail($imageId);
        $this->files->deleteFile($image->url);
        $this->files->deleteFile($image->thumbnail_url);
        $this->files->deleteFile($image->medium_url);
        return (bool) $image->delete();
    }

    protected function clearPostThumbnail(int $postId): bool
    {
        $post = Post::findOrFail($postId);
        $this->files->deleteFile($post->thumbnail);
        $post->thumbnail = null;
        $post->thumbnail_alt_text = null;
        return $post->save();
    }

    protected function clearCategoryImage(int $categoryId): bool
    {
        $category = Category::findOrFail($categoryId);
        $this->files->deleteFile($category->image);
        $category->image = null;
        return $category->save();
    }

    protected function clearBannerImage(int $bannerId, string $mode): bool
    {
        $banner = Banner::findOrFail($bannerId);
        if ($mode === 'desktop') {
            $this->files->deleteFile($banner->image_desktop);
            $banner->image_desktop = null;
        } else {
            $this->files->deleteFile($banner->image_mobile);
            $banner->image_mobile = null;
        }
        return $banner->save();
    }

    protected function clearProfileImage(int $profileId, string $column): bool
    {
        $profile = Profile::findOrFail($profileId);
        $this->files->deleteFile($profile->{$column});
        $profile->{$column} = null;
        return $profile->save();
    }
}


