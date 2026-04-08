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
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class MediaScannerService
{
    protected array $typeLabels = [
        'product_image' => 'Ảnh sản phẩm',
        'post_thumbnail' => 'Ảnh bài viết',
        'category_image' => 'Ảnh danh mục',
        'banner_desktop' => 'Banner desktop',
        'banner_mobile' => 'Banner mobile',
        'profile_avatar' => 'Avatar',
        'profile_sub_avatar' => 'Ảnh phụ avatar',
        'library_image' => 'Ảnh thư viện',
        'filesystem_file' => 'File trên ổ đĩa',
    ];

    protected array $statusLabels = [
        'all' => 'Tất cả trạng thái',
        'in_use' => 'Đang dùng',
        'orphan_file' => 'File mồ côi',
        'missing_file' => 'Thiếu file',
        'unassigned_record' => 'Chưa gắn đối tượng',
        'external' => 'URL ngoài',
        'shared_file' => 'Dùng chung nhiều nơi',
    ];

    protected array $allowedTypeFilters = [
        'all',
        'product_image',
        'post_thumbnail',
        'category_image',
        'banner_desktop',
        'banner_mobile',
        'profile_avatar',
        'profile_sub_avatar',
        'library_image',
        'filesystem_file',
    ];

    protected array $allowedStatusFilters = [
        'all',
        'in_use',
        'orphan_file',
        'missing_file',
        'unassigned_record',
        'external',
        'shared_file',
    ];

    protected array $allowedImageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'svg'];

    protected ?Collection $allItems = null;
    protected ?array $filesystemInventory = null;
    protected ?array $dashboardStats = null;

    public function __construct(
        protected FileHelperService $files
    ) {
        $this->directories = config('media.directories', []);
        $this->directoryLabels = collect($this->directories)->mapWithKeys(function ($path, $key) {
            $label = Str::headline(str_replace('_', ' ', $key));

            return [$key => $label . ' (' . $path . ')'];
        })->toArray();
    }

    protected array $directories;
    protected array $directoryLabels;

    public function getDashboardStats(): array
    {
        if ($this->dashboardStats !== null) {
            return $this->dashboardStats;
        }

        $items = $this->getAllItems();
        $inventory = $this->getFilesystemInventory();

        $this->dashboardStats = [
            'library_items' => $items->count(),
            'tracked_records' => $items->where('source_kind', 'record')->count(),
            'physical_files' => count($inventory),
            'in_use' => $items->filter(fn (array $item) => in_array('in_use', $item['status_flags'], true))->count(),
            'orphan_files' => $items->filter(fn (array $item) => in_array('orphan_file', $item['status_flags'], true))->count(),
            'missing_files' => $items->filter(fn (array $item) => in_array('missing_file', $item['status_flags'], true))->count(),
            'unassigned_records' => $items->filter(fn (array $item) => in_array('unassigned_record', $item['status_flags'], true))->count(),
            'external_files' => $items->filter(fn (array $item) => in_array('external', $item['status_flags'], true))->count(),
            'estimated_size' => $this->files->formatBytes((int) array_sum(array_column($inventory, 'size'))),
            'status_counts' => [
                'all' => $items->count(),
                'in_use' => $items->filter(fn (array $item) => in_array('in_use', $item['status_flags'], true))->count(),
                'orphan_file' => $items->filter(fn (array $item) => in_array('orphan_file', $item['status_flags'], true))->count(),
                'missing_file' => $items->filter(fn (array $item) => in_array('missing_file', $item['status_flags'], true))->count(),
                'unassigned_record' => $items->filter(fn (array $item) => in_array('unassigned_record', $item['status_flags'], true))->count(),
                'external' => $items->filter(fn (array $item) => in_array('external', $item['status_flags'], true))->count(),
                'shared_file' => $items->filter(fn (array $item) => in_array('shared_file', $item['status_flags'], true))->count(),
            ],
        ];

        return $this->dashboardStats;
    }

    public function search(array $filters = []): LengthAwarePaginator
    {
        $type = in_array(($filters['type'] ?? 'all'), $this->allowedTypeFilters, true) ? ($filters['type'] ?? 'all') : 'all';
        $folder = array_key_exists(($filters['folder'] ?? 'all'), $this->directories) || ($filters['folder'] ?? 'all') === 'all'
            ? ($filters['folder'] ?? 'all')
            : 'all';
        $status = in_array(($filters['status'] ?? 'all'), $this->allowedStatusFilters, true) ? ($filters['status'] ?? 'all') : 'all';
        $term = trim((string) ($filters['q'] ?? ''));
        $sort = $filters['sort'] ?? 'created_at';
        $direction = strtolower($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $perPage = min(max((int) ($filters['per_page'] ?? 50), 12), 2000);
        $page = max((int) ($filters['page'] ?? 1), 1);

        $items = $this->getAllItems();

        if ($type !== 'all') {
            $items = $items->where('type', $type)->values();
        }
        if ($folder !== 'all') {
            $items = $items->where('folder_key', $folder)->values();
        }
        if ($status !== 'all') {
            $items = $items->filter(fn (array $item) => in_array($status, $item['status_flags'], true))->values();
        }
        if ($term !== '') {
            $needle = Str::lower($term);
            $items = $items->filter(function (array $item) use ($needle) {
                foreach ([
                    $item['file_name'] ?? '',
                    $item['title'] ?? '',
                    $item['alt'] ?? '',
                    $item['description'] ?? '',
                    $item['relative_path'] ?? '',
                    $item['entity_label'] ?? '',
                    $item['type_label'] ?? '',
                    $item['folder_label'] ?? '',
                ] as $haystack) {
                    if ($haystack !== '' && Str::contains(Str::lower($haystack), $needle)) {
                        return true;
                    }
                }

                return false;
            })->values();
        }

        $items = $this->sortItems($items, $sort, $direction);
        $total = $items->count();
        $slice = $items->forPage($page, $perPage)->values();

        return new LengthAwarePaginator($slice, $total, $perPage, $page);
    }

    public function findItem(string $source, ?string $id = null, ?string $path = null): ?array
    {
        return $this->getAllItems()->first(function (array $item) use ($source, $id, $path) {
            if ($item['delete_source'] !== $source) {
                return false;
            }

            if ($source === 'filesystem_file') {
                return $item['relative_path'] === $this->files->normalizeRelativePath($path);
            }

            return (string) $item['delete_id'] === (string) $id;
        });
    }

    public function getTypeLabels(): array
    {
        return $this->typeLabels;
    }

    public function getDirectoryLabels(): array
    {
        return $this->directoryLabels;
    }

    public function getStatusLabels(): array
    {
        return $this->statusLabels;
    }

    protected function getAllItems(): Collection
    {
        if ($this->allItems !== null) {
            return $this->allItems;
        }

        $inventory = $this->getFilesystemInventory();
        $trackedItems = $this->collectTrackedItems($inventory);

        $trackedPaths = $trackedItems->flatMap(fn (array $item) => $item['_managed_paths'] ?? [])->filter()->unique()->values()->all();
        $hiddenPaths = $trackedItems->flatMap(fn (array $item) => $item['_hidden_paths'] ?? [])->filter()->unique()->values()->all();
        $usageCounts = $trackedItems
            ->filter(fn (array $item) => ($item['is_local'] ?? false) && !empty($item['relative_path']))
            ->countBy('relative_path');

        $trackedItems = $trackedItems
            ->map(fn (array $item) => $this->finalizeTrackedItem($item, (int) ($usageCounts[$item['relative_path']] ?? 0)))
            ->values();

        $filesystemItems = $this->collectFilesystemItems($inventory, $trackedPaths, $hiddenPaths);

        $this->allItems = $trackedItems->concat($filesystemItems)->values();

        return $this->allItems;
    }

    protected function collectTrackedItems(array $inventory): Collection
    {
        $ownerMaps = [
            'product' => Product::query()->select('id', 'name')->get()->keyBy('id'),
            'post' => Post::query()->select('id', 'title', 'slug')->get()->keyBy('id'),
            'category' => Category::query()->select('id', 'name', 'slug')->get()->keyBy('id'),
            'banner' => Banner::query()->select('id', 'title')->get()->keyBy('id'),
            'profile' => Profile::query()->select('id', 'full_name', 'nickname')->get()->keyBy('id'),
        ];

        return Image::query()
            ->latest('created_at')
            ->get()
            ->map(fn (Image $image) => $this->mapImageRecord($image, $inventory, $ownerMaps));
    }

    protected function mapImageRecord(Image $image, array $inventory, array $ownerMaps): array
    {
        $entityType = $image->entity_type ?: ($image->product_id ? 'product' : null);
        $entityId = $image->entity_id ?: $image->product_id;
        $sourceInfo = $this->resolveSourceInfo($image, $entityType, $entityId);

        $fallbackFolderKey = $this->defaultFolderKeyFor($entityType, $image->context);
        $relativePath = $this->normalizeStoredPath($image->path ?: $image->url, $fallbackFolderKey);
        $fileMeta = $relativePath ? ($inventory[$relativePath] ?? $this->inspectSinglePath($relativePath)) : null;
        $folderKey = $this->detectFolderKey($relativePath) ?? $fallbackFolderKey ?? 'other';
        $previewPath = $this->normalizeStoredPath($image->thumbnail_url ?: ($image->path ?: $image->url), $folderKey) ?: $relativePath;
        $mediumPath = $this->normalizeStoredPath($image->medium_url, $folderKey);

        return [
            'key' => $sourceInfo['type'] . ':' . $image->id,
            'id' => (string) $image->id,
            'type' => $sourceInfo['type'],
            'type_label' => $this->typeLabels[$sourceInfo['type']] ?? $sourceInfo['type'],
            'source_kind' => 'record',
            'folder_key' => $folderKey,
            'folder_label' => $this->directoryLabels[$folderKey] ?? null,
            'file_name' => $image->name ?: ($relativePath ? basename($relativePath) : basename((string) ($image->url ?: $image->path))),
            'title' => $image->title,
            'alt' => $image->alt,
            'description' => $image->notes,
            'relative_path' => $relativePath,
            'original' => $this->buildAssetUrl($relativePath ?: ($image->url ?: $image->path)),
            'preview' => $this->buildAssetUrl($previewPath ?: ($image->url ?: $image->path)),
            'size' => $fileMeta['size'] ?? $image->size,
            'size_human' => isset($fileMeta['size']) ? $this->files->formatBytes((int) $fileMeta['size']) : ($image->size ? $this->files->formatBytes((int) $image->size) : null),
            'dimensions' => $fileMeta['dimensions'] ?? $image->dimensions,
            'mime_type' => $fileMeta['mime_type'] ?? $image->mime_type,
            'extension' => $fileMeta['extension'] ?? $image->extension,
            'created_at' => optional($image->created_at)->toDateTimeString(),
            'updated_at' => optional($image->updated_at)->toDateTimeString(),
            'entity_label' => $this->resolveEntityLabel($entityType, $entityId, $ownerMaps, $image),
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'entity_edit_url' => $this->resolveEntityEditUrl($entityType, $entityId),
            'delete_source' => $sourceInfo['source'],
            'delete_id' => $sourceInfo['id'],
            'can_edit_meta' => true,
            'can_assign' => $relativePath !== null || Str::startsWith((string) ($image->url ?: $image->path), ['http://', 'https://']),
            'is_local' => $relativePath !== null,
            'has_entity' => $entityId !== null,
            'is_external' => $relativePath === null && Str::startsWith((string) ($image->url ?: $image->path), ['http://', 'https://']),
            'is_primary' => (bool) $image->is_primary,
            'metadata' => [
                'role' => $image->role,
                'is_primary' => (bool) $image->is_primary,
                'order' => $image->order,
            ],
            '_managed_paths' => array_values(array_filter([$relativePath, $previewPath, $mediumPath])),
            '_hidden_paths' => array_values(array_filter(array_unique([
                $previewPath !== $relativePath ? $previewPath : null,
                $mediumPath,
                $this->guessWebpVariant($relativePath),
            ]))),
            '_sort_created_at' => optional($image->created_at)->timestamp ?? 0,
        ];
    }

    protected function resolveSourceInfo(Image $image, ?string $entityType, ?int $entityId): array
    {
        return match (true) {
            $entityType === 'product' => ['type' => 'product_image', 'source' => 'product_image', 'id' => (string) $image->id],
            $entityType === 'post' => ['type' => 'post_thumbnail', 'source' => 'post_thumbnail', 'id' => (string) $entityId],
            $entityType === 'category' => ['type' => 'category_image', 'source' => 'category_image', 'id' => (string) $entityId],
            $entityType === 'banner' && $image->role === 'mobile' => ['type' => 'banner_mobile', 'source' => 'banner_mobile', 'id' => (string) $entityId],
            $entityType === 'banner' => ['type' => 'banner_desktop', 'source' => 'banner_desktop', 'id' => (string) $entityId],
            $entityType === 'profile' && $image->role === 'sub_avatar' => ['type' => 'profile_sub_avatar', 'source' => 'profile_sub_avatar', 'id' => (string) $entityId],
            $entityType === 'profile' => ['type' => 'profile_avatar', 'source' => 'profile_avatar', 'id' => (string) $entityId],
            default => ['type' => 'library_image', 'source' => 'library_image', 'id' => (string) $image->id],
        };
    }

    protected function resolveEntityLabel(?string $entityType, ?int $entityId, array $ownerMaps, Image $image): ?string
    {
        if (!$entityType || !$entityId) {
            return null;
        }

        return match ($entityType) {
            'product' => $ownerMaps['product'][$entityId]->name ?? ($image->title ?: "Sản phẩm #{$entityId}"),
            'post' => $ownerMaps['post'][$entityId]->title ?? "Bài viết #{$entityId}",
            'category' => $ownerMaps['category'][$entityId]->name ?? "Danh mục #{$entityId}",
            'banner' => $ownerMaps['banner'][$entityId]->title ?? "Banner #{$entityId}",
            'profile' => $ownerMaps['profile'][$entityId]->full_name
                ?? $ownerMaps['profile'][$entityId]->nickname
                ?? "Profile #{$entityId}",
            default => null,
        };
    }

    protected function resolveEntityEditUrl(?string $entityType, ?int $entityId): ?string
    {
        if (!$entityType || !$entityId) {
            return null;
        }

        return match ($entityType) {
            'product' => Route::has('admin.products.edit') ? route('admin.products.edit', $entityId) : null,
            'post' => Route::has('admin.posts.edit') ? route('admin.posts.edit', $entityId) : null,
            'category' => Route::has('admin.categories.edit') ? route('admin.categories.edit', $entityId) : null,
            'banner' => Route::has('admin.banners.edit') ? route('admin.banners.edit', $entityId) : null,
            default => null,
        };
    }

    protected function finalizeTrackedItem(array $item, int $usageCount): array
    {
        $statusFlags = $this->resolveStatusFlags($item, $usageCount);
        $item['usage_count'] = max($usageCount, ($item['relative_path'] || $item['is_external']) ? 1 : 0);
        $item['is_shared'] = in_array('shared_file', $statusFlags, true);
        $item['status_flags'] = $statusFlags;
        $item['primary_status'] = $this->resolvePrimaryStatus($statusFlags);
        $item['status_labels'] = array_values(array_map(fn (string $status) => $this->statusLabels[$status] ?? $status, $statusFlags));
        unset($item['_managed_paths'], $item['_hidden_paths']);

        return $item;
    }

    protected function collectFilesystemItems(array $inventory, array $trackedPaths, array $hiddenPaths): Collection
    {
        $trackedLookup = array_fill_keys($trackedPaths, true);
        $hiddenLookup = array_fill_keys($hiddenPaths, true);

        return collect($inventory)
            ->reject(fn (array $meta, string $relativePath) => isset($trackedLookup[$relativePath]) || isset($hiddenLookup[$relativePath]))
            ->map(function (array $meta, string $relativePath) {
                $folderKey = $this->detectFolderKey($relativePath) ?? 'other';
                $fileName = basename($relativePath);

                return [
                    'key' => 'filesystem_file:' . md5($relativePath),
                    'id' => md5($relativePath),
                    'type' => 'filesystem_file',
                    'type_label' => $this->typeLabels['filesystem_file'],
                    'source_kind' => 'filesystem',
                    'folder_key' => $folderKey,
                    'folder_label' => $this->directoryLabels[$folderKey] ?? null,
                    'file_name' => $fileName,
                    'title' => pathinfo($fileName, PATHINFO_FILENAME),
                    'alt' => null,
                    'description' => 'File đang có trên ổ đĩa nhưng chưa gắn vào bản ghi nào.',
                    'relative_path' => $relativePath,
                    'original' => $this->buildAssetUrl($relativePath),
                    'preview' => $this->buildAssetUrl($relativePath),
                    'size' => $meta['size'] ?? null,
                    'size_human' => isset($meta['size']) ? $this->files->formatBytes((int) $meta['size']) : null,
                    'dimensions' => $meta['dimensions'] ?? null,
                    'mime_type' => $meta['mime_type'] ?? null,
                    'extension' => $meta['extension'] ?? null,
                    'created_at' => $meta['created_at'] ?? null,
                    'updated_at' => $meta['updated_at'] ?? null,
                    'entity_label' => null,
                    'entity_type' => null,
                    'entity_id' => null,
                    'entity_edit_url' => null,
                    'delete_source' => 'filesystem_file',
                    'delete_id' => $relativePath,
                    'can_edit_meta' => false,
                    'can_assign' => true,
                    'is_local' => true,
                    'has_entity' => false,
                    'is_external' => false,
                    'is_primary' => false,
                    'usage_count' => 0,
                    'is_shared' => false,
                    'status_flags' => ['orphan_file'],
                    'primary_status' => 'orphan_file',
                    'status_labels' => [$this->statusLabels['orphan_file']],
                    'metadata' => [],
                    '_sort_created_at' => $meta['timestamp'] ?? 0,
                ];
            })->values();
    }

    protected function sortItems(Collection $items, string $sort, string $direction): Collection
    {
        $descending = $direction === 'desc';

        $sorted = match ($sort) {
            'file_name' => $items->sortBy('file_name', SORT_NATURAL | SORT_FLAG_CASE, $descending),
            'entity_id' => $items->sortBy(fn (array $item) => (int) ($item['entity_id'] ?? 0), SORT_NUMERIC, $descending),
            'size' => $items->sortBy(fn (array $item) => (int) ($item['size'] ?? 0), SORT_NUMERIC, $descending),
            default => $items->sortBy(fn (array $item) => (int) ($item['_sort_created_at'] ?? 0), SORT_NUMERIC, $descending),
        };

        return $sorted->map(function (array $item) {
            unset($item['_sort_created_at']);
            return $item;
        })->values();
    }

    protected function getFilesystemInventory(): array
    {
        if ($this->filesystemInventory !== null) {
            return $this->filesystemInventory;
        }

        $inventory = [];

        foreach ($this->directories as $relativeDirectory) {
            $absoluteDirectory = public_path(trim($relativeDirectory, '/'));
            if (!is_dir($absoluteDirectory)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($absoluteDirectory, RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterator as $fileInfo) {
                if (!$fileInfo->isFile()) {
                    continue;
                }

                $extension = strtolower($fileInfo->getExtension());
                if (!in_array($extension, $this->allowedImageExtensions, true)) {
                    continue;
                }

                $relativePath = $this->files->absoluteToRelativePath($fileInfo->getPathname());
                if (!$relativePath) {
                    continue;
                }

                $inventory[$relativePath] = [
                    'size' => $fileInfo->getSize(),
                    'mime_type' => @mime_content_type($fileInfo->getPathname()) ?: null,
                    'extension' => $extension,
                    'dimensions' => $this->readImageDimensions($fileInfo->getPathname()),
                    'timestamp' => $fileInfo->getMTime(),
                    'created_at' => date('Y-m-d H:i:s', $fileInfo->getMTime()),
                    'updated_at' => date('Y-m-d H:i:s', $fileInfo->getMTime()),
                ];
            }
        }

        $this->filesystemInventory = $inventory;
        return $this->filesystemInventory;
    }

    protected function inspectSinglePath(?string $relativePath): ?array
    {
        if (!$relativePath || Str::startsWith($relativePath, ['http://', 'https://'])) {
            return null;
        }

        $absolutePath = $this->files->toAbsolutePath($relativePath);
        if (!$absolutePath || !is_file($absolutePath)) {
            return null;
        }

        return [
            'size' => filesize($absolutePath) ?: null,
            'mime_type' => @mime_content_type($absolutePath) ?: null,
            'extension' => strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION)),
            'dimensions' => $this->readImageDimensions($absolutePath),
            'timestamp' => filemtime($absolutePath) ?: 0,
            'created_at' => date('Y-m-d H:i:s', filemtime($absolutePath) ?: time()),
            'updated_at' => date('Y-m-d H:i:s', filemtime($absolutePath) ?: time()),
        ];
    }

    protected function readImageDimensions(string $absolutePath): ?array
    {
        if (strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION)) === 'svg') {
            return null;
        }

        $info = @getimagesize($absolutePath);
        return $info ? ['width' => $info[0] ?? null, 'height' => $info[1] ?? null] : null;
    }

    protected function normalizeStoredPath(?string $path, ?string $fallbackFolderKey = null): ?string
    {
        $normalized = $this->files->normalizeRelativePath($path);
        if (!$normalized) {
            return null;
        }
        if (Str::startsWith($normalized, ['http://', 'https://'])) {
            return null;
        }
        if ($this->detectFolderKey($normalized)) {
            return $normalized;
        }
        if ($fallbackFolderKey && isset($this->directories[$fallbackFolderKey])) {
            return trim($this->directories[$fallbackFolderKey], '/') . '/' . ltrim($normalized, '/');
        }

        return $normalized;
    }

    protected function buildAssetUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }
        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        return asset(ltrim($path, '/'));
    }

    protected function detectFolderKey(?string $relativePath): ?string
    {
        if (!$relativePath || Str::startsWith($relativePath, ['http://', 'https://'])) {
            return null;
        }

        $normalized = trim($relativePath, '/');
        foreach ($this->directories as $key => $directory) {
            $prefix = trim(str_replace('\\', '/', $directory), '/');
            if ($normalized === $prefix || Str::startsWith($normalized, $prefix . '/')) {
                return $key;
            }
        }

        return null;
    }

    protected function defaultFolderKeyFor(?string $entityType, ?string $context): ?string
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

    protected function guessWebpVariant(?string $relativePath): ?string
    {
        if (!$relativePath || Str::endsWith($relativePath, ['.webp', '.avif', '.svg'])) {
            return null;
        }

        $info = pathinfo($relativePath);
        if (empty($info['dirname']) || empty($info['filename'])) {
            return null;
        }

        return trim($info['dirname'], '/') . '/' . $info['filename'] . '.webp';
    }

    protected function resolveStatusFlags(array $item, int $usageCount): array
    {
        $flags = [];

        if ($item['is_external'] ?? false) {
            $flags[] = 'external';
        } elseif (!empty($item['relative_path'])) {
            $absolutePath = $this->files->toAbsolutePath($item['relative_path']);
            if (!$absolutePath || !is_file($absolutePath)) {
                $flags[] = 'missing_file';
            }
        } else {
            $flags[] = 'missing_file';
        }

        if (($item['has_entity'] ?? false) === false && !in_array('missing_file', $flags, true)) {
            $flags[] = 'unassigned_record';
        }

        if (!$flags) {
            $flags[] = 'in_use';
        }

        if ($usageCount > 1 && !in_array('external', $flags, true)) {
            $flags[] = 'shared_file';
        }

        return array_values(array_unique($flags));
    }

    protected function resolvePrimaryStatus(array $flags): string
    {
        foreach (['missing_file', 'orphan_file', 'unassigned_record', 'external', 'in_use', 'shared_file'] as $candidate) {
            if (in_array($candidate, $flags, true)) {
                return $candidate;
            }
        }

        return 'in_use';
    }
}
