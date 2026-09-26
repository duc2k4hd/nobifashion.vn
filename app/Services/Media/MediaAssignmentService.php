<?php

namespace App\Services\Media;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Image;
use App\Models\Post;
use App\Models\Product;
use App\Models\Profile;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
        // Nếu ID thuộc bảng images, xóa trực tiếp bản ghi trong bảng images
        if (is_numeric($id) && DB::table('images')->where('id', (int) $id)->exists()) {
            $image = Image::find((int) $id);
            if ($image) {
                if ($deletePhysical && $image->path) {
                    $abs = public_path($image->path);
                    if ($abs && is_file($abs)) {
                        @unlink($abs);
                    }
                }
                return (bool) $image->delete();
            }
        }

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

    public function deleteByManagedPath(string $relativePath): array
    {
        $normalizedPath = $this->registry->normalizeStoredPath($relativePath);
        if (
            ! $normalizedPath
            || Str::startsWith($normalizedPath, ['http://', 'https://'])
            || ! $this->files->isManagedMediaPath($normalizedPath, config('media.directories', []))
        ) {
            return [
                'success' => false,
                'deleted_records' => 0,
                'file_deleted' => false,
            ];
        }

        $basename = basename($normalizedPath);
        $images = Image::query()
            ->where(function($q) use ($normalizedPath, $basename) {
                $q->where('path', $normalizedPath)
                  ->orWhere('url', $normalizedPath)
                  ->orWhere('thumbnail_url', $normalizedPath)
                  ->orWhere('medium_url', $normalizedPath)
                  ->orWhere('path', 'LIKE', '%' . $basename)
                  ->orWhere('url', 'LIKE', '%' . $basename);
            })
            ->get()
            ->filter(fn (Image $image) => $this->imageMatchesManagedPath($image, $normalizedPath))
            ->values();

        $deletedRecords = 0;

        DB::transaction(function () use ($images, &$deletedRecords) {
            foreach ($images as $image) {
                [$source, $sourceId] = $this->resolveSourcePair($image);
                if (! $this->delete($source, (string) $sourceId, false)) {
                    throw new \RuntimeException('Không thể xóa bản ghi media đang tham chiếu file.');
                }

                $deletedRecords++;
            }
        });

        $fileDeleted = ! $this->files->fileExists($normalizedPath)
            || $this->files->deleteManagedFile($normalizedPath, config('media.directories', []));

        return [
            'success' => $fileDeleted,
            'deleted_records' => $deletedRecords,
            'file_deleted' => $fileDeleted,
        ];
    }

    /**
     * Xóa hàng loạt media theo batch siêu tốc (Bulk SQL + Bulk Unlink)
     * Thay thế vòng lặp N*5 queries bằng 2-3 queries duy nhất cho toàn bộ mẻ.
     *
     * @param array $items Mảng các item dạng [['source' => ..., 'id' => ..., 'path' => ...]]
     * @return array{deleted_count: int, preserved_files_count: int, failed_count: int, failure_messages: array}
     */
    public function deleteBatch(array $items): array
    {
        $deletedCount = 0;
        $preservedFilesCount = 0;
        $failedCount = 0;
        $failureMessages = [];

        $imageIds = [];
        $filesystemPaths = [];
        $otherItems = [];

        foreach ($items as $item) {
            $source = $item['source'] ?? '';
            $id = $item['id'] ?? null;
            $path = $item['path'] ?? null;

            if ($source === 'filesystem_file') {
                if ($path) {
                    $filesystemPaths[] = $path;
                } else {
                    $failedCount++;
                }
            } elseif (in_array($source, ['product_image', 'library_image', 'post_thumbnail', 'category_image', 'banner_desktop', 'banner_mobile', 'profile_avatar', 'profile_sub_avatar'], true)) {
                if ($id) {
                    $imageIds[] = (int) $id;
                } else {
                    $failedCount++;
                }
            } else {
                if ($id) {
                    $otherItems[] = $item;
                } else {
                    $failedCount++;
                }
            }
        }

        // 1. Xử lý bulk cho bảng images (chiếm >99% dữ liệu)
        if (!empty($imageIds)) {
            $records = DB::table('images')
                ->whereIn('id', $imageIds)
                ->select(['id', 'path', 'url', 'thumbnail_url', 'medium_url', 'context'])
                ->get();

            $allCandidates = [];

            foreach ($records as $rec) {
                $ctx = $rec->context === 'product' ? 'clothes' : null;
                $orig = $this->registry->normalizeStoredPath($rec->path ?: $rec->url, $ctx);
                $thumb = $rec->thumbnail_url ? $this->registry->normalizeStoredPath($rec->thumbnail_url, $ctx) : null;
                $med = $rec->medium_url ? $this->registry->normalizeStoredPath($rec->medium_url, $ctx) : null;

                if ($orig && !Str::startsWith($orig, ['http://', 'https://'])) {
                    $allCandidates[$orig] = true;
                }
                if ($thumb && !Str::startsWith($thumb, ['http://', 'https://'])) {
                    $allCandidates[$thumb] = true;
                }
                if ($med && !Str::startsWith($med, ['http://', 'https://'])) {
                    $allCandidates[$med] = true;
                }
            }

            // Tìm các file dùng chung (đang được record KHÁC ngoài danh sách cần xóa tham chiếu)
            $candidateKeys = array_keys($allCandidates);
            $sharedMap = [];

            if (!empty($candidateKeys)) {
                foreach (array_chunk($candidateKeys, 500) as $chunk) {
                    $sharedInDb = DB::table('images')
                        ->whereNotIn('id', $imageIds)
                        ->where(function ($q) use ($chunk) {
                            $q->whereIn('path', $chunk)
                              ->orWhereIn('url', $chunk);
                        })
                        ->pluck('path')
                        ->merge(
                            DB::table('images')
                                ->whereNotIn('id', $imageIds)
                                ->whereIn('url', $chunk)
                                ->pluck('url')
                        )
                        ->filter()
                        ->flip()
                        ->all();

                    $sharedMap = array_merge($sharedMap, $sharedInDb);
                }
            }

            // Xóa file vật lý bằng @unlink siêu tốc
            $physicalPreserved = 0;
            foreach ($allCandidates as $relPath => $_) {
                if (isset($sharedMap[$relPath])) {
                    $physicalPreserved++;
                    continue; // File đang dùng chung, giữ lại
                }

                $abs = public_path($relPath);
                if ($abs && is_file($abs)) {
                    @unlink($abs);
                }
            }

            // Xóa toàn bộ DB records bằng 1 câu DELETE duy nhất
            $deletedRows = DB::table('images')->whereIn('id', $imageIds)->delete();
            $deletedCount += $deletedRows;
            $preservedFilesCount += $physicalPreserved;
        }

        // 2. Xử lý nhóm file mồ côi (filesystem_file)
        if (!empty($filesystemPaths)) {
            $directories = config('media.directories', []);
            foreach ($filesystemPaths as $relPath) {
                $normalized = $this->registry->normalizeStoredPath($relPath);
                if ($normalized && $this->files->isManagedMediaPath($normalized, $directories)) {
                    $abs = public_path($normalized);
                    if ($abs && is_file($abs)) {
                        @unlink($abs);
                    }
                    $deletedCount++;
                } else {
                    $failedCount++;
                }
            }
        }

        // 3. Xử lý các entity lẻ khác nếu có
        foreach ($otherItems as $item) {
            try {
                $ok = $this->delete($item['source'], (string) $item['id'], true);
                if ($ok) {
                    $deletedCount++;
                } else {
                    $failedCount++;
                }
            } catch (\Throwable $e) {
                $failedCount++;
                $failureMessages[] = $e->getMessage();
            }
        }

        return [
            'deleted_count' => $deletedCount,
            'preserved_files_count' => $preservedFilesCount,
            'failed_count' => $failedCount,
            'failure_messages' => array_unique($failureMessages),
        ];
    }

    /**
     * Xóa 1 batch theo scope (ví dụ: 'unassigned_record') siêu tốc, tiết kiệm RAM
     */
    public function deleteScopeChunk(string $scope = 'unassigned_record', int $batchSize = 1000): array
    {
        $batchSize = max(50, min($batchSize, 10000));

        if ($scope === 'unassigned_record') {
            $records = DB::table('images')
                ->where(function ($builder) {
                    $builder->where(function ($inner) {
                        $inner->whereNull('entity_id')
                              ->whereNull('product_id');
                    })->orWhere('entity_type', 'library');
                })
                ->select(['id'])
                ->limit($batchSize)
                ->get();

            if ($records->isEmpty()) {
                return [
                    'processed' => 0,
                    'preserved_files_count' => 0,
                    'remaining' => 0,
                    'finished' => true,
                ];
            }

            $items = $records->map(fn ($r) => [
                'source' => 'product_image',
                'id' => $r->id,
            ])->all();

            $res = $this->deleteBatch($items);

            $remaining = DB::table('images')
                ->where(function ($builder) {
                    $builder->where(function ($inner) {
                        $inner->whereNull('entity_id')
                              ->whereNull('product_id');
                    })->orWhere('entity_type', 'library');
                })
                ->count();

            return [
                'processed' => $res['deleted_count'],
                'preserved_files_count' => $res['preserved_files_count'],
                'remaining' => $remaining,
                'finished' => ($remaining === 0),
            ];
        }

        return [
            'processed' => 0,
            'preserved_files_count' => 0,
            'remaining' => 0,
            'finished' => true,
        ];
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

        $path = $image->path ?: $image->url;
        $abs = $path ? public_path($path) : null;
        $fileExists = $abs && is_file($abs);

        if ($deletePhysical && $fileExists) {
            $this->deleteManagedPath($path);
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

    protected function isImageReferencedInPostContent(Image $image): bool
    {
        $normalizedPath = $this->registry->normalizeStoredPath(
            $image->path ?: $image->url,
            $this->defaultFolderKeyForImage($image)
        );

        if (! $normalizedPath || ! Schema::hasTable('posts')) {
            return false;
        }

        $basename = basename($normalizedPath);
        $postQuery = DB::table('posts')->select(['content']);
        if (Schema::hasColumn('posts', 'deleted_at')) {
            $postQuery->whereNull('deleted_at');
        }

        $postQuery->where('content', 'like', '%' . $basename . '%');

        foreach ($postQuery->cursor() as $post) {
            if (in_array($normalizedPath, $this->extractManagedPathsFromHtml((string) ($post->content ?? '')), true)) {
                return true;
            }
        }

        return false;
    }

    protected function imageMatchesManagedPath(Image $image, string $relativePath): bool
    {
        $fallbackFolderKey = $this->defaultFolderKeyForImage($image);

        foreach ([
            $image->path,
            $image->url,
            $image->thumbnail_url,
            $image->medium_url,
        ] as $candidate) {
            $normalized = $this->registry->normalizeStoredPath($candidate, $fallbackFolderKey);
            if ($normalized === $relativePath) {
                return true;
            }
        }

        return false;
    }

    protected function resolveSourcePair(Image $image): array
    {
        return match (true) {
            $image->entity_type === 'product' || $image->product_id !== null => ['product_image', $image->id],
            $image->entity_type === 'post' => ['post_thumbnail', $image->entity_id],
            $image->entity_type === 'category' => ['category_image', $image->entity_id],
            $image->entity_type === 'banner' && $image->role === 'mobile' => ['banner_mobile', $image->entity_id],
            $image->entity_type === 'banner' => ['banner_desktop', $image->entity_id],
            $image->entity_type === 'profile' && $image->role === 'sub_avatar' => ['profile_sub_avatar', $image->entity_id],
            $image->entity_type === 'profile' => ['profile_avatar', $image->entity_id],
            default => ['library_image', $image->id],
        };
    }

    protected function defaultFolderKeyForImage(Image $image): ?string
    {
        return match ($image->entity_type ?: ($image->product_id ? 'product' : $image->context)) {
            'product' => 'clothes',
            'post' => 'posts',
            'category' => 'categories',
            'banner' => 'banners',
            'profile' => 'accounts_avatars',
            default => null,
        };
    }

    /**
     * @return string[]
     */
    protected function extractManagedPathsFromHtml(string $html): array
    {
        if (trim($html) === '') {
            return [];
        }

        preg_match_all('/<img\b[^>]*\bsrc\s*=\s*(["\'])(.*?)\1/i', $html, $matches);

        $paths = [];
        foreach ($matches[2] ?? [] as $src) {
            $managedPath = $this->normalizeEmbeddedMediaPath($src, 'posts');
            if ($managedPath) {
                $paths[$managedPath] = $managedPath;
            }
        }

        return array_values($paths);
    }

    protected function normalizeEmbeddedMediaPath(?string $src, ?string $fallbackFolderKey = null): ?string
    {
        $decoded = trim(html_entity_decode((string) $src, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($decoded === '' || Str::startsWith($decoded, ['data:', 'blob:'])) {
            return null;
        }

        $parsedPath = parse_url($decoded, PHP_URL_PATH);
        $path = is_string($parsedPath) && $parsedPath !== '' ? $parsedPath : $decoded;

        if (Str::startsWith($decoded, ['http://', 'https://'])) {
            $normalized = $this->files->normalizeRelativePath($path);

            return $this->files->isManagedMediaPath($normalized, config('media.directories', []))
                ? $normalized
                : null;
        }

        return $this->registry->normalizeStoredPath($path, $fallbackFolderKey);
    }
}
