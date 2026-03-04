<?php

namespace App\Services\Media;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Image;
use App\Models\Post;
use App\Models\Product;
use App\Models\Profile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class MediaScannerService
{
    protected FileHelperService $files;
    protected array $directories;
    protected array $directoryLabels;
    protected array $typeLabels;
    protected array $allowedFilters = [
        'all',
        'product_image',
        'post_thumbnail',
        'category_image',
        'banner_desktop',
        'banner_mobile',
        'profile_avatar',
        'profile_sub_avatar',
    ];

    public function __construct(FileHelperService $files)
    {
        $this->files = $files;
        $this->directories = config('media.directories', []);
        $this->directoryLabels = collect($this->directories)->map(function ($path, $key) {
            $label = Str::headline(str_replace('_', ' ', $key));
            return $label . ' (' . $path . ')';
        })->toArray();
        $this->typeLabels = [
            'product_image' => 'Ảnh sản phẩm',
            'post_thumbnail' => 'Ảnh bài viết',
            'category_image' => 'Ảnh danh mục',
            'banner_desktop' => 'Banner desktop',
            'banner_mobile' => 'Banner mobile',
            'profile_avatar' => 'Avatar',
            'profile_sub_avatar' => 'Ảnh phụ avatar',
        ];
    }

    public function getDashboardStats(): array
    {
        $productImages = Image::count();
        $postThumbnails = Post::whereNotNull('thumbnail')->count();
        $categoryImages = Category::whereNotNull('image')->count();
        $bannerDesktop = Banner::whereNotNull('image_desktop')->count();
        $bannerMobile = Banner::whereNotNull('image_mobile')->count();
        $profileAvatar = Profile::whereNotNull('avatar')->count();
        $profileSub = Profile::whereNotNull('sub_avatar')->count();

        $estimatedBytes = $this->calculateEstimatedDiskUsage();

        return [
            'product_images' => $productImages,
            'post_thumbnails' => $postThumbnails,
            'category_images' => $categoryImages,
            'banner_images' => $bannerDesktop + $bannerMobile,
            'profile_avatars' => $profileAvatar + $profileSub,
            'total_images' => $productImages + $postThumbnails + $categoryImages + $bannerDesktop + $bannerMobile + $profileAvatar + $profileSub,
            'estimated_size' => $this->files->formatBytes($estimatedBytes),
        ];
    }

    /**
     * Tìm kiếm media.
     *
     * @param  array{type?:string,q?:string,sort?:string,direction?:string,per_page?:int,page?:int}  $filters
     */
    public function search(array $filters = []): LengthAwarePaginator
    {
        $type = $filters['type'] ?? 'all';
        if (!in_array($type, $this->allowedFilters, true)) {
            $type = 'all';
        }

        $term = trim($filters['q'] ?? '');
        $sort = $filters['sort'] ?? 'created_at';
        $direction = strtolower($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $perPage = (int) ($filters['per_page'] ?? 30);
        $page = max((int) ($filters['page'] ?? 1), 1);

        $items = collect();

        if ($this->shouldInclude('product_image', $type)) {
            $items = $items->merge($this->mapProductImages($term));
        }
        if ($this->shouldInclude('post_thumbnail', $type)) {
            $items = $items->merge($this->mapPosts($term));
        }
        if ($this->shouldInclude('category_image', $type)) {
            $items = $items->merge($this->mapCategories($term));
        }

        if ($type === 'banner_desktop' || $type === 'banner_mobile') {
            $items = $items->merge($this->mapBanners($term, $type));
        } elseif ($type === 'all') {
            $items = $items->merge($this->mapBanners($term));
        }

        if ($type === 'profile_avatar' || $type === 'profile_sub_avatar') {
            $items = $items->merge($this->mapProfiles($term, $type));
        } elseif ($type === 'all') {
            $items = $items->merge($this->mapProfiles($term));
        }

        $items = $this->sortItems($items, $sort, $direction);

        $total = $items->count();
        $slice = $items->forPage($page, $perPage)->values();

        return new LengthAwarePaginator($slice, $total, $perPage, $page);
    }

    protected function shouldInclude(string $target, string $filter): bool
    {
        return $filter === 'all' || $filter === $target;
    }

    protected function mapProductImages(string $term): Collection
    {
        $query = Image::query()
            ->with('product:id,name,slug')
            ->latest('created_at')
            ->limit(800);

        if ($term !== '') {
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', '%' . $term . '%')
                    ->orWhere('alt', 'like', '%' . $term . '%')
                    ->orWhere('url', 'like', '%' . $term . '%');
            });
        }

        return $query->get()->map(function (Image $image) {
            $folderKey = $this->detectFolderKey($image->url);
            return [
                'id' => (string) $image->id,
                'type' => 'product_image',
                'preview' => $this->toAsset($image->thumbnail_url ?: $image->url),
                'original' => $this->toAsset($image->url),
                'folder_key' => $folderKey,
                'folder_label' => $this->directoryLabels[$folderKey] ?? null,
                'type_label' => $this->typeLabels['product_image'] ?? 'Ảnh sản phẩm',
                'file_name' => basename($image->url),
                'alt' => $image->alt,
                'title' => $image->title,
                'description' => $image->notes,
                'created_at' => optional($image->created_at)->toDateTimeString(),
                'entity_label' => optional($image->product)->name,
                'entity_id' => optional($image->product)->id,
                'dimensions' => $image->dimensions,
                'metadata' => [
                    'is_primary' => $image->is_primary,
                    'order' => $image->order,
                ],
            ];
        });
    }

    protected function mapPosts(string $term): Collection
    {
        $query = Post::query()
            ->select('id', 'title', 'thumbnail', 'thumbnail_alt_text', 'created_at')
            ->whereNotNull('thumbnail')
            ->latest('created_at')
            ->limit(400);

        if ($term !== '') {
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', '%' . $term . '%')
                    ->orWhere('thumbnail', 'like', '%' . $term . '%');
            });
        }

        return $query->get()->map(function (Post $post) {
            $folderKey = 'posts';
            return [
                'id' => (string) $post->id,
                'type' => 'post_thumbnail',
                'preview' => $this->toProjectAsset($post->thumbnail, 'posts'),
                'original' => $this->toProjectAsset($post->thumbnail, 'posts'),
                'folder_key' => $folderKey,
                'folder_label' => $this->directoryLabels[$folderKey] ?? null,
                'type_label' => $this->typeLabels['post_thumbnail'] ?? 'Ảnh bài viết',
                'file_name' => basename($post->thumbnail),
                'alt' => $post->thumbnail_alt_text,
                'title' => $post->title,
                'created_at' => optional($post->created_at)->toDateTimeString(),
                'entity_label' => $post->title,
                'entity_id' => $post->id,
            ];
        });
    }

    protected function mapCategories(string $term): Collection
    {
        $query = Category::query()
            ->select('id', 'name', 'image', 'created_at')
            ->whereNotNull('image')
            ->latest('created_at')
            ->limit(300);

        if ($term !== '') {
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', '%' . $term . '%')
                    ->orWhere('image', 'like', '%' . $term . '%');
            });
        }

        return $query->get()->map(function (Category $category) {
            $folderKey = 'categories';
            return [
                'id' => (string) $category->id,
                'type' => 'category_image',
                'preview' => $this->toProjectAsset($category->image, 'categories'),
                'original' => $this->toProjectAsset($category->image, 'categories'),
                'folder_key' => $folderKey,
                'folder_label' => $this->directoryLabels[$folderKey] ?? null,
                'type_label' => $this->typeLabels['category_image'] ?? 'Ảnh danh mục',
                'file_name' => basename($category->image),
                'title' => $category->name,
                'created_at' => optional($category->created_at)->toDateTimeString(),
                'entity_label' => $category->name,
                'entity_id' => $category->id,
            ];
        });
    }

    protected function mapBanners(string $term, ?string $onlyType = null): Collection
    {
        $query = Banner::query()
            ->select('id', 'title', 'image_desktop', 'image_mobile', 'created_at')
            ->latest('created_at')
            ->limit(300);

        if ($term !== '') {
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', '%' . $term . '%')
                    ->orWhere('image_desktop', 'like', '%' . $term . '%')
                    ->orWhere('image_mobile', 'like', '%' . $term . '%');
            });
        }

        return $query->get()->flatMap(function (Banner $banner) use ($onlyType) {
            $items = collect();
            if ($banner->image_desktop && (!$onlyType || $onlyType === 'banner_desktop')) {
                $folderKey = 'banners';
                $items->push([
                    'id' => (string) $banner->id,
                    'type' => 'banner_desktop',
                    'preview' => $this->toProjectAsset($banner->image_desktop, 'banners'),
                    'original' => $this->toProjectAsset($banner->image_desktop, 'banners'),
                    'folder_key' => $folderKey,
                    'folder_label' => $this->directoryLabels[$folderKey] ?? null,
                    'type_label' => $this->typeLabels['banner_desktop'] ?? 'Banner desktop',
                    'file_name' => basename($banner->image_desktop),
                    'title' => $banner->title . ' (Desktop)',
                    'created_at' => optional($banner->created_at)->toDateTimeString(),
                    'entity_label' => $banner->title,
                    'entity_id' => $banner->id,
                ]);
            }
            if ($banner->image_mobile && (!$onlyType || $onlyType === 'banner_mobile')) {
                $folderKey = 'banners';
                $items->push([
                    'id' => (string) $banner->id,
                    'type' => 'banner_mobile',
                    'preview' => $this->toProjectAsset($banner->image_mobile, 'banners'),
                    'original' => $this->toProjectAsset($banner->image_mobile, 'banners'),
                    'folder_key' => $folderKey,
                    'folder_label' => $this->directoryLabels[$folderKey] ?? null,
                    'type_label' => $this->typeLabels['banner_mobile'] ?? 'Banner mobile',
                    'file_name' => basename($banner->image_mobile),
                    'title' => $banner->title . ' (Mobile)',
                    'created_at' => optional($banner->created_at)->toDateTimeString(),
                    'entity_label' => $banner->title,
                    'entity_id' => $banner->id,
                ]);
            }

            return $items;
        });
    }

    protected function mapProfiles(string $term, ?string $onlyType = null): Collection
    {
        $query = Profile::query()
            ->select('id', 'full_name', 'avatar', 'sub_avatar', 'created_at')
            ->latest('created_at')
            ->limit(400);

        if ($term !== '') {
            $query->where(function ($q) use ($term) {
                $q->where('full_name', 'like', '%' . $term . '%')
                    ->orWhere('avatar', 'like', '%' . $term . '%')
                    ->orWhere('sub_avatar', 'like', '%' . $term . '%');
            });
        }

        return $query->get()->flatMap(function (Profile $profile) use ($onlyType) {
            $items = collect();
            if ($profile->avatar && (!$onlyType || $onlyType === 'profile_avatar')) {
                $folderKey = 'accounts_avatars';
                $items->push([
                    'id' => (string) $profile->id,
                    'type' => 'profile_avatar',
                    'preview' => $this->toProjectAsset($profile->avatar, 'accounts_avatars'),
                    'original' => $this->toProjectAsset($profile->avatar, 'accounts_avatars'),
                    'folder_key' => $folderKey,
                    'folder_label' => $this->directoryLabels[$folderKey] ?? null,
                    'type_label' => $this->typeLabels['profile_avatar'] ?? 'Avatar',
                    'file_name' => basename($profile->avatar),
                    'title' => $profile->full_name . ' (Avatar)',
                    'created_at' => optional($profile->created_at)->toDateTimeString(),
                    'entity_label' => $profile->full_name,
                    'entity_id' => $profile->id,
                ]);
            }
            if ($profile->sub_avatar && (!$onlyType || $onlyType === 'profile_sub_avatar')) {
                $folderKey = 'accounts_avatars';
                $items->push([
                    'id' => (string) $profile->id,
                    'type' => 'profile_sub_avatar',
                    'preview' => $this->toProjectAsset($profile->sub_avatar, 'accounts_avatars'),
                    'original' => $this->toProjectAsset($profile->sub_avatar, 'accounts_avatars'),
                    'folder_key' => $folderKey,
                    'folder_label' => $this->directoryLabels[$folderKey] ?? null,
                    'type_label' => $this->typeLabels['profile_sub_avatar'] ?? 'Ảnh phụ avatar',
                    'file_name' => basename($profile->sub_avatar),
                    'title' => $profile->full_name . ' (Ảnh phụ)',
                    'created_at' => optional($profile->created_at)->toDateTimeString(),
                    'entity_label' => $profile->full_name,
                    'entity_id' => $profile->id,
                ]);
            }

            return $items;
        });
    }

    protected function sortItems(Collection $items, string $sort, string $direction): Collection
    {
        $direction = strtolower($direction) === 'asc' ? 'asc' : 'desc';

        $sorted = match ($sort) {
            'file_name' => $items->sortBy('file_name', SORT_NATURAL | SORT_FLAG_CASE, $direction === 'desc'),
            'entity_id' => $items->sortBy('entity_id', SORT_REGULAR, $direction === 'desc'),
            default => $items->sortBy('created_at', SORT_REGULAR, $direction === 'desc'),
        };

        return $sorted->values();
    }

    protected function toAsset(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        $normalized = trim($path);

        if (Str::startsWith($normalized, ['http://', 'https://'])) {
            return $normalized;
        }

        $trimmed = ltrim($normalized, '/');
        if (Str::startsWith($trimmed, 'public/')) {
            $trimmed = substr($trimmed, 7);
        }

        return asset($trimmed);
    }

    protected function toProjectAsset(?string $path, string $folderKey): ?string
    {
        if (!$path) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        $directory = $this->directories[$folderKey] ?? $this->directories['other'] ?? '';
        $directory = rtrim($directory, '/') . '/';

        // Tránh trường hợp $path đã có $directory (backward compatibility)
        if (Str::startsWith($path, $directory)) {
            return asset($path);
        }

        return asset($directory . $path);
    }

    protected function detectFolderKey(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        $normalized = ltrim(str_replace('public/', '', trim($path)), '/');
        foreach ($this->directories as $key => $relative) {
            $relative = ltrim($relative, '/');
            if (Str::startsWith($normalized, $relative)) {
                return $key;
            }
        }

        return null;
    }

    public function getTypeLabels(): array
    {
        return $this->typeLabels;
    }

    public function getDirectoryLabels(): array
    {
        return $this->directoryLabels;
    }

    protected function calculateEstimatedDiskUsage(): int
    {
        $bytes = 0;

        Image::select('url', 'thumbnail_url', 'medium_url')
            ->chunk(500, function ($chunk) use (&$bytes) {
                foreach ($chunk as $image) {
                    $bytes += $this->safeFilesize($image->url);
                    $bytes += $this->safeFilesize($image->thumbnail_url);
                    $bytes += $this->safeFilesize($image->medium_url);
                }
            });

        Post::select('thumbnail')->whereNotNull('thumbnail')
            ->chunk(500, function ($chunk) use (&$bytes) {
                foreach ($chunk as $post) {
                    $bytes += $this->safeProjectFilesize($post->thumbnail, 'posts');
                }
            });

        Category::select('image')->whereNotNull('image')
            ->chunk(500, function ($chunk) use (&$bytes) {
                foreach ($chunk as $category) {
                    $bytes += $this->safeProjectFilesize($category->image, 'categories');
                }
            });

        Banner::select('image_desktop', 'image_mobile')
            ->chunk(500, function ($chunk) use (&$bytes) {
                foreach ($chunk as $banner) {
                    $bytes += $this->safeProjectFilesize($banner->image_desktop, 'banners');
                    $bytes += $this->safeProjectFilesize($banner->image_mobile, 'banners');
                }
            });

        Profile::select('avatar', 'sub_avatar')
            ->chunk(500, function ($chunk) use (&$bytes) {
                foreach ($chunk as $profile) {
                    $bytes += $this->safeProjectFilesize($profile->avatar, 'accounts_avatars');
                    $bytes += $this->safeProjectFilesize($profile->sub_avatar, 'accounts_avatars');
                }
            });

        return $bytes;
    }

    protected function safeFilesize(?string $relativePath): int
    {
        $absolute = $this->files->toAbsolutePath($relativePath);
        if ($absolute && is_file($absolute)) {
            return filesize($absolute) ?: 0;
        }
        return 0;
    }

    protected function safeProjectFilesize(?string $path, string $folderKey): int
    {
        if (!$path || Str::startsWith($path, ['http://', 'https://'])) {
            return 0;
        }

        $directory = $this->directories[$folderKey] ?? $this->directories['other'] ?? '';
        $directory = rtrim($directory, '/') . '/';

        if (Str::startsWith($path, $directory)) {
            return $this->safeFilesize($path);
        }

        return $this->safeFilesize($directory . $path);
    }
}


