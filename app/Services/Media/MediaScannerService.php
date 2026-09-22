<?php

namespace App\Services\Media;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Image;
use App\Models\Post;
use App\Models\Product;
use App\Models\Profile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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

    public function getDashboardStats(array $filters = []): array
    {
        $filters = $filters === [] ? [] : $this->normalizeFilters($filters);

        if ($filters !== [] && $this->requiresDeepScan($filters)) {
            $specificFolderKey = $filters['folder'] !== 'all' ? $filters['folder'] : null;
            return $this->buildLegacyDashboardStats($specificFolderKey);
        }

        if ($this->dashboardStats !== null) {
            return $this->dashboardStats;
        }

        $this->dashboardStats = $this->buildFastDashboardStats();

        return $this->dashboardStats;
    }

    public function search(array $filters = []): LengthAwarePaginator
    {
        $filters = $this->normalizeFilters($filters);

        if ($this->requiresDeepScan($filters)) {
            return $this->legacySearch($filters);
        }

        return $this->fastSearch($filters);
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

    protected function normalizeFilters(array $filters): array
    {
        $type = in_array(($filters['type'] ?? 'all'), $this->allowedTypeFilters, true) ? ($filters['type'] ?? 'all') : 'all';
        $folder = array_key_exists(($filters['folder'] ?? 'all'), $this->directories) || ($filters['folder'] ?? 'all') === 'all'
            ? ($filters['folder'] ?? 'all')
            : 'all';
        $status = in_array(($filters['status'] ?? 'all'), $this->allowedStatusFilters, true) ? ($filters['status'] ?? 'all') : 'all';

        return [
            'type' => $type,
            'folder' => $folder,
            'status' => $status,
            'q' => trim((string) ($filters['q'] ?? '')),
            'sort' => $filters['sort'] ?? 'created_at',
            'direction' => strtolower($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc',
            'per_page' => min(max((int) ($filters['per_page'] ?? 50), 12), 10000),
            'page' => max((int) ($filters['page'] ?? 1), 1),
        ];
    }

    protected function requiresDeepScan(array $filters): bool
    {
        return in_array($filters['status'], ['orphan_file', 'missing_file'], true)
            || $filters['type'] === 'filesystem_file'
            || $filters['folder'] === 'imports';
    }

    protected function legacySearch(array $filters): LengthAwarePaginator
    {
        $specificFolderKey = $filters['folder'] !== 'all' ? $filters['folder'] : null;
        $items = $this->getAllItems($specificFolderKey);

        if ($filters['type'] !== 'all') {
            $items = $items->where('type', $filters['type'])->values();
        }
        if ($filters['folder'] !== 'all') {
            $items = $items->where('folder_key', $filters['folder'])->values();
        }
        if ($filters['status'] !== 'all') {
            $items = $items->filter(fn (array $item) => in_array($filters['status'], $item['status_flags'], true))->values();
        }
        if ($filters['q'] !== '') {
            $needle = Str::lower($filters['q']);
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

        $items = $this->sortItems($items, $filters['sort'], $filters['direction']);
        $total = $items->count();
        $slice = $items->forPage($filters['page'], $filters['per_page'])->values();

        return new LengthAwarePaginator($slice, $total, $filters['per_page'], $filters['page']);
    }

    protected function fastSearch(array $filters): LengthAwarePaginator
    {
        $query = $this->buildFastSearchQuery($filters);
        $this->applyFastSort($query, $filters['sort'], $filters['direction']);

        /** @var LengthAwarePaginator<Image> $paginator */
        $paginator = $query->paginate($filters['per_page'], ['images.*'], 'page', $filters['page']);
        $images = collect($paginator->items());
        $ownerLabels = $this->loadOwnerLabelsForImages($images);
        $usageCounts = $this->loadUsageCountsForImages($images);

        $paginator->setCollection(
            $images->map(fn (Image $image) => $this->mapFastImageRecord($image, $ownerLabels, $usageCounts))
        );

        return $paginator;
    }

    protected function buildFastSearchQuery(array $filters): Builder
    {
        $query = Image::query()->select('images.*');

        $this->applyTypeFilterToQuery($query, $filters['type']);
        $this->applyFolderFilterToQuery($query, $filters['folder']);
        $this->applyStatusFilterToQuery($query, $filters['status']);
        $this->applyKeywordFilterToQuery($query, $filters['q']);

        return $query;
    }

    protected function applyTypeFilterToQuery(Builder $query, string $type): void
    {
        match ($type) {
            'product_image' => $query->where(function (Builder $builder) {
                $builder->where('entity_type', 'product')
                    ->orWhereNotNull('product_id');
            }),
            'post_thumbnail' => $query->where('entity_type', 'post'),
            'category_image' => $query->where('entity_type', 'category'),
            'banner_desktop' => $query->where('entity_type', 'banner')
                ->where(function (Builder $builder) {
                    $builder->whereNull('role')
                        ->orWhere('role', 'desktop');
                }),
            'banner_mobile' => $query->where('entity_type', 'banner')->where('role', 'mobile'),
            'profile_avatar' => $query->where('entity_type', 'profile')
                ->where(function (Builder $builder) {
                    $builder->whereNull('role')
                        ->orWhere('role', 'avatar');
                }),
            'profile_sub_avatar' => $query->where('entity_type', 'profile')->where('role', 'sub_avatar'),
            'library_image' => $query->where(function (Builder $builder) {
                $builder->where(function (Builder $inner) {
                    $inner->whereNull('entity_type')
                        ->whereNull('product_id');
                })->orWhere('entity_type', 'library');
            }),
            default => null,
        };
    }

    protected function applyFolderFilterToQuery(Builder $query, string $folder): void
    {
        if ($folder === 'all' || !isset($this->directories[$folder])) {
            return;
        }

        $directory = trim(str_replace('\\', '/', $this->directories[$folder]), '/');

        $query->where(function (Builder $builder) use ($directory, $folder) {
            $builder->where('path', $directory)
                ->orWhere('path', 'like', $directory . '/%')
                ->orWhere('url', $directory)
                ->orWhere('url', 'like', $directory . '/%');

            match ($folder) {
                'clothes' => $builder->orWhere(function (Builder $inner) {
                    $inner->where('entity_type', 'product')
                        ->orWhereNotNull('product_id');
                }),
                'posts' => $builder->orWhere('entity_type', 'post'),
                'categories' => $builder->orWhere('entity_type', 'category'),
                'banners' => $builder->orWhere('entity_type', 'banner'),
                'accounts_avatars' => $builder->orWhere('entity_type', 'profile'),
                default => null,
            };
        });
    }

    protected function applyStatusFilterToQuery(Builder $query, string $status): void
    {
        $assetExpression = $this->storedAssetExpression();

        match ($status) {
            'external' => $this->applyExternalCondition($query),
            'unassigned_record' => $this->applyUnassignedCondition($query),
            'in_use' => $query
                ->where(function (Builder $builder) {
                    $builder->whereNotNull('entity_id')
                        ->orWhereNotNull('product_id');
                })
                ->where(function (Builder $builder) {
                    $this->applyLocalCondition($builder);
                }),
            'shared_file' => $query
                ->where(function (Builder $builder) {
                    $this->applyLocalCondition($builder);
                })
                ->whereRaw("{$assetExpression} IS NOT NULL")
                ->whereIn(DB::raw($assetExpression), $this->buildSharedAssetKeysQuery()),
            default => null,
        };
    }

    protected function applyKeywordFilterToQuery(Builder $query, string $term): void
    {
        if ($term === '') {
            return;
        }

        $needle = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $term) . '%';

        $query->where(function (Builder $builder) use ($needle) {
            $builder->where('name', 'like', $needle)
                ->orWhere('title', 'like', $needle)
                ->orWhere('alt', 'like', $needle)
                ->orWhere('notes', 'like', $needle)
                ->orWhere('path', 'like', $needle)
                ->orWhere('url', 'like', $needle)
                ->orWhereRaw('CAST(COALESCE(entity_id, product_id, 0) AS CHAR) LIKE ?', [$needle]);
        });
    }

    protected function applyFastSort(Builder $query, string $sort, string $direction): void
    {
        match ($sort) {
            'file_name' => $query->orderByRaw(
                "COALESCE(NULLIF(name, ''), NULLIF(path, ''), NULLIF(url, '')) {$direction}"
            )->orderBy('id', 'desc'),
            'entity_id' => $query->orderByRaw(
                "COALESCE(entity_id, product_id, 0) {$direction}"
            )->orderBy('id', 'desc'),
            'size' => $query->orderBy('size', $direction)->orderBy('id', 'desc'),
            default => $query->orderBy('created_at', $direction)->orderBy('id', $direction),
        };
    }

    protected function buildFastDashboardStats(): array
    {
        $total = Image::query()->count();
        $external = Image::query()
            ->where(function (Builder $builder) {
                $this->applyExternalCondition($builder);
            })
            ->count();
        $unassigned = Image::query()
            ->where(function (Builder $builder) {
                $this->applyUnassignedCondition($builder);
            })
            ->count();
        $shared = (int) $this->buildSharedAssetAggregatesQuery()->get()->sum('aggregate');
        $physicalFiles = (int) DB::query()
            ->fromSub($this->buildLocalAssetKeysBaseQuery(), 'local_assets')
            ->distinct()
            ->count('asset_key');
        $estimatedSize = (int) Image::query()->sum('size');
        $inUse = max($total - $external - $unassigned, 0);

        return [
            'library_items' => $total,
            'tracked_records' => $total,
            'physical_files' => $physicalFiles,
            'in_use' => $inUse,
            'orphan_files' => 0,
            'missing_files' => 0,
            'unassigned_records' => $unassigned,
            'external_files' => $external,
            'estimated_size' => $this->files->formatBytes($estimatedSize),
            'status_counts' => [
                'all' => $total,
                'in_use' => $inUse,
                'orphan_file' => 0,
                'missing_file' => 0,
                'unassigned_record' => $unassigned,
                'external' => $external,
                'shared_file' => $shared,
            ],
        ];
    }

    protected function buildLegacyDashboardStats(?string $specificFolderKey = null): array
    {
        $items = $this->getAllItems($specificFolderKey);
        $inventory = $this->getFilesystemInventory($specificFolderKey);

        return [
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
    }

    protected function loadOwnerLabelsForImages(Collection $images): array
    {
        $idsByType = [
            'product' => [],
            'post' => [],
            'category' => [],
            'banner' => [],
            'profile' => [],
        ];

        foreach ($images as $image) {
            $entityType = $image->entity_type ?: ($image->product_id ? 'product' : null);
            $entityId = $image->entity_id ?: $image->product_id;
            if (!$entityType || !$entityId || !array_key_exists($entityType, $idsByType)) {
                continue;
            }

            $idsByType[$entityType][] = (int) $entityId;
        }

        return [
            'product' => !empty($idsByType['product'])
                ? Product::query()->whereIn('id', array_unique($idsByType['product']))->pluck('name', 'id')->all()
                : [],
            'post' => !empty($idsByType['post'])
                ? Post::query()->whereIn('id', array_unique($idsByType['post']))->pluck('title', 'id')->all()
                : [],
            'category' => !empty($idsByType['category'])
                ? Category::query()->whereIn('id', array_unique($idsByType['category']))->pluck('name', 'id')->all()
                : [],
            'banner' => !empty($idsByType['banner'])
                ? Banner::query()->whereIn('id', array_unique($idsByType['banner']))->pluck('title', 'id')->all()
                : [],
            'profile' => !empty($idsByType['profile'])
                ? Profile::query()
                    ->whereIn('id', array_unique($idsByType['profile']))
                    ->get(['id', 'full_name', 'nickname'])
                    ->mapWithKeys(fn (Profile $profile) => [
                        $profile->id => $profile->full_name ?: $profile->nickname ?: "Profile #{$profile->id}",
                    ])->all()
                : [],
        ];
    }

    protected function loadUsageCountsForImages(Collection $images): array
    {
        $assetKeys = $images
            ->map(fn (Image $image) => $this->storedAssetKey($image))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($assetKeys === []) {
            return [];
        }

        return DB::query()
            ->fromSub($this->buildLocalAssetKeysBaseQuery(), 'local_assets')
            ->whereIn('asset_key', $assetKeys)
            ->selectRaw('asset_key, COUNT(*) AS aggregate')
            ->groupBy('asset_key')
            ->pluck('aggregate', 'asset_key')
            ->map(fn ($count) => (int) $count)
            ->all();
    }

    protected function mapFastImageRecord(Image $image, array $ownerLabels, array $usageCounts): array
    {
        $entityType = $image->entity_type ?: ($image->product_id ? 'product' : null);
        $entityId = $image->entity_id ?: $image->product_id;
        $sourceInfo = $this->resolveSourceInfo($image, $entityType, $entityId);
        $fallbackFolderKey = $this->defaultFolderKeyFor($entityType, $image->context);
        $relativePath = $this->resolvePrimaryPath($image, $fallbackFolderKey);
        $rawReference = $this->resolveRawReference($image);
        $folderKey = $this->detectFolderKey($relativePath) ?? $fallbackFolderKey ?? 'other';
        $previewPath = $this->normalizeStoredPath($image->thumbnail_url, $folderKey) ?: $relativePath;
        $isExternal = $relativePath === null && Str::startsWith((string) $rawReference, ['http://', 'https://']);
        $usageCount = (int) ($usageCounts[$this->storedAssetKey($image)] ?? 0);
        $statusFlags = $this->resolveFastStatusFlags($relativePath, $isExternal, $entityId !== null, $usageCount);
        $statusLabels = array_values(array_map(fn (string $status) => $this->statusLabels[$status] ?? $status, $statusFlags));

        return [
            'key' => $sourceInfo['type'] . ':' . $image->id,
            'id' => (string) $image->id,
            'type' => $sourceInfo['type'],
            'type_label' => $this->typeLabels[$sourceInfo['type']] ?? $sourceInfo['type'],
            'source_kind' => 'record',
            'folder_key' => $folderKey,
            'folder_label' => $this->directoryLabels[$folderKey] ?? null,
            'file_name' => $image->name ?: ($relativePath ? basename($relativePath) : basename((string) $rawReference)),
            'title' => $image->title,
            'alt' => $image->alt,
            'description' => $image->notes,
            'relative_path' => $relativePath,
            'original' => $this->buildAssetUrl($relativePath ?: $rawReference),
            'preview' => $this->buildAssetUrl($previewPath ?: ($relativePath ?: $rawReference)),
            'size' => $image->size,
            'size_human' => $image->size ? $this->files->formatBytes((int) $image->size) : null,
            'dimensions' => $image->dimensions,
            'mime_type' => $image->mime_type,
            'extension' => $image->extension,
            'created_at' => optional($image->created_at)->toDateTimeString(),
            'updated_at' => optional($image->updated_at)->toDateTimeString(),
            'entity_label' => $this->resolveFastEntityLabel($entityType, $entityId, $ownerLabels, $image),
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'entity_edit_url' => $this->resolveEntityEditUrl($entityType, $entityId),
            'delete_source' => $sourceInfo['source'],
            'delete_id' => $sourceInfo['id'],
            'can_edit_meta' => true,
            'can_assign' => $relativePath !== null || $isExternal,
            'is_local' => $relativePath !== null,
            'has_entity' => $entityId !== null,
            'is_external' => $isExternal,
            'is_primary' => (bool) $image->is_primary,
            'usage_count' => max($usageCount, ($relativePath || $isExternal) ? 1 : 0),
            'is_shared' => in_array('shared_file', $statusFlags, true),
            'status_flags' => $statusFlags,
            'primary_status' => $this->resolvePrimaryStatus($statusFlags),
            'status_labels' => $statusLabels,
            'metadata' => [
                'role' => $image->role,
                'is_primary' => (bool) $image->is_primary,
                'order' => $image->order,
            ],
        ];
    }

    protected function resolveFastStatusFlags(?string $relativePath, bool $isExternal, bool $hasEntity, int $usageCount): array
    {
        $flags = [];

        if ($isExternal) {
            $flags[] = 'external';
        } elseif ($relativePath) {
            if (! $this->files->fileExists($relativePath)) {
                $flags[] = 'missing_file';
            }
        } else {
            $flags[] = 'missing_file';
        }

        if (! $hasEntity && !in_array('missing_file', $flags, true)) {
            $flags[] = 'unassigned_record';
        }

        if ($flags === []) {
            $flags[] = 'in_use';
        }

        if ($usageCount > 1 && !in_array('external', $flags, true)) {
            $flags[] = 'shared_file';
        }

        return array_values(array_unique($flags));
    }

    protected function resolveFastEntityLabel(?string $entityType, ?int $entityId, array $ownerLabels, Image $image): ?string
    {
        if (!$entityType || !$entityId) {
            return null;
        }

        return match ($entityType) {
            'product' => $ownerLabels['product'][$entityId] ?? ($image->title ?: "Sản phẩm #{$entityId}"),
            'post' => $ownerLabels['post'][$entityId] ?? "Bài viết #{$entityId}",
            'category' => $ownerLabels['category'][$entityId] ?? "Danh mục #{$entityId}",
            'banner' => $ownerLabels['banner'][$entityId] ?? "Banner #{$entityId}",
            'profile' => $ownerLabels['profile'][$entityId] ?? "Profile #{$entityId}",
            default => null,
        };
    }

    protected function storedAssetExpression(): string
    {
        return "COALESCE(NULLIF(path, ''), NULLIF(url, ''))";
    }

    protected function buildLocalAssetKeysBaseQuery(): \Illuminate\Database\Query\Builder
    {
        $assetExpression = $this->storedAssetExpression();

        return DB::table('images')
            ->selectRaw("{$assetExpression} AS asset_key")
            ->whereRaw("{$assetExpression} IS NOT NULL")
            ->whereRaw("{$assetExpression} NOT LIKE 'http://%'")
            ->whereRaw("{$assetExpression} NOT LIKE 'https://%'");
    }

    protected function buildSharedAssetKeysQuery(): \Illuminate\Database\Query\Builder
    {
        return DB::query()
            ->fromSub($this->buildLocalAssetKeysBaseQuery(), 'local_assets')
            ->select('asset_key')
            ->groupBy('asset_key')
            ->havingRaw('COUNT(*) > 1');
    }

    protected function buildSharedAssetAggregatesQuery(): \Illuminate\Database\Query\Builder
    {
        return DB::query()
            ->fromSub($this->buildLocalAssetKeysBaseQuery(), 'local_assets')
            ->selectRaw('asset_key, COUNT(*) AS aggregate')
            ->groupBy('asset_key')
            ->havingRaw('COUNT(*) > 1');
    }

    protected function storedAssetKey(Image $image): ?string
    {
        $assetKey = trim((string) ($image->path ?: $image->url));
        if ($assetKey === '' || Str::startsWith($assetKey, ['http://', 'https://'])) {
            return null;
        }

        return $assetKey;
    }

    protected function applyExternalCondition(Builder $query): void
    {
        $query->where(function (Builder $builder) {
            $builder->where('path', 'like', 'http://%')
                ->orWhere('path', 'like', 'https://%')
                ->orWhere('url', 'like', 'http://%')
                ->orWhere('url', 'like', 'https://%');
        });
    }

    protected function applyLocalCondition(Builder $query): void
    {
        $query->where(function (Builder $builder) {
            $builder->whereNull('path')
                ->orWhere(function (Builder $inner) {
                    $inner->where('path', 'not like', 'http://%')
                        ->where('path', 'not like', 'https://%');
                });
        })->where(function (Builder $builder) {
            $builder->whereNull('url')
                ->orWhere(function (Builder $inner) {
                    $inner->where('url', 'not like', 'http://%')
                        ->where('url', 'not like', 'https://%');
                });
        });
    }

    protected function applyUnassignedCondition(Builder $query): void
    {
        $query->where(function (Builder $builder) {
            $builder->where(function (Builder $inner) {
                $inner->whereNull('entity_id')
                    ->whereNull('product_id');
            })->orWhere('entity_type', 'library');
        });
    }

    protected function getAllItems(?string $specificFolderKey = null): Collection
    {
        if ($specificFolderKey === null && $this->allItems !== null) {
            return $this->allItems;
        }

        $inventory = $this->getFilesystemInventory($specificFolderKey);
        $trackedItems = $this->collectTrackedItems($inventory, $specificFolderKey);

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

    protected function collectTrackedItems(array $inventory, ?string $specificFolderKey = null): Collection
    {
        $ownerMaps = [
            'product' => Product::query()->select('id', 'name')->get()->keyBy('id'),
            'post' => Post::query()->select('id', 'title', 'slug')->get()->keyBy('id'),
            'category' => Category::query()->select('id', 'name', 'slug')->get()->keyBy('id'),
            'banner' => Banner::query()->select('id', 'title')->get()->keyBy('id'),
            'profile' => Profile::query()->select('id', 'full_name', 'nickname')->get()->keyBy('id'),
        ];

        $query = Image::query()->latest('created_at');
        if ($specificFolderKey && isset($this->directories[$specificFolderKey])) {
            $directory = trim(str_replace('\\', '/', $this->directories[$specificFolderKey]), '/');
            $query->where(function (Builder $builder) use ($directory, $specificFolderKey) {
                $builder->where('path', $directory)
                    ->orWhere('path', 'like', $directory . '/%')
                    ->orWhere('url', $directory)
                    ->orWhere('url', 'like', $directory . '/%');
                match ($specificFolderKey) {
                    'clothes' => $builder->orWhere(function (Builder $inner) {
                        $inner->where('entity_type', 'product')->orWhereNotNull('product_id');
                    }),
                    'posts' => $builder->orWhere('entity_type', 'post'),
                    'categories' => $builder->orWhere('entity_type', 'category'),
                    'banners' => $builder->orWhere('entity_type', 'banner'),
                    'accounts_avatars' => $builder->orWhere('entity_type', 'profile'),
                    default => null,
                };
            });
        }

        return $query->get()
            ->map(fn (Image $image) => $this->mapImageRecord($image, $inventory, $ownerMaps));
    }

    protected function mapImageRecord(Image $image, array $inventory, array $ownerMaps): array
    {
        $entityType = $image->entity_type ?: ($image->product_id ? 'product' : null);
        $entityId = $image->entity_id ?: $image->product_id;
        $sourceInfo = $this->resolveSourceInfo($image, $entityType, $entityId);

        $fallbackFolderKey = $this->defaultFolderKeyFor($entityType, $image->context);
        $relativePath = $this->resolvePrimaryPath($image, $fallbackFolderKey);
        $rawReference = $this->resolveRawReference($image);
        $fileMeta = $relativePath ? ($inventory[$relativePath] ?? $this->inspectSinglePath($relativePath)) : null;
        $folderKey = $this->detectFolderKey($relativePath) ?? $fallbackFolderKey ?? 'other';
        $previewPath = $this->normalizeStoredPath($image->thumbnail_url, $folderKey) ?: $relativePath;
        $mediumPath = $this->normalizeStoredPath($image->medium_url, $folderKey);

        return [
            'key' => $sourceInfo['type'] . ':' . $image->id,
            'id' => (string) $image->id,
            'type' => $sourceInfo['type'],
            'type_label' => $this->typeLabels[$sourceInfo['type']] ?? $sourceInfo['type'],
            'source_kind' => 'record',
            'folder_key' => $folderKey,
            'folder_label' => $this->directoryLabels[$folderKey] ?? null,
            'file_name' => $image->name ?: ($relativePath ? basename($relativePath) : basename((string) $rawReference)),
            'title' => $image->title,
            'alt' => $image->alt,
            'description' => $image->notes,
            'relative_path' => $relativePath,
            'original' => $this->buildAssetUrl($relativePath ?: $rawReference),
            'preview' => $this->buildAssetUrl($previewPath ?: ($relativePath ?: $rawReference)),
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
            'can_assign' => $relativePath !== null || Str::startsWith((string) $rawReference, ['http://', 'https://']),
            'is_local' => $relativePath !== null,
            'has_entity' => $entityId !== null,
            'is_external' => $relativePath === null && Str::startsWith((string) $rawReference, ['http://', 'https://']),
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

    protected function getFilesystemInventory(?string $specificFolderKey = null): array
    {
        if ($specificFolderKey === null && $this->filesystemInventory !== null) {
            return $this->filesystemInventory;
        }

        $inventory = [];
        $dirsToScan = $specificFolderKey && isset($this->directories[$specificFolderKey]) 
            ? [$specificFolderKey => $this->directories[$specificFolderKey]]
            : $this->directories;

        foreach ($dirsToScan as $relativeDirectory) {
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

    protected function resolvePrimaryPath(Image $image, ?string $fallbackFolderKey = null): ?string
    {
        $candidates = array_values(array_unique(array_filter([
            $this->normalizeStoredPath($image->path, $fallbackFolderKey),
            $this->normalizeStoredPath($image->url, $fallbackFolderKey),
        ])));

        if ($candidates === []) {
            return null;
        }

        foreach ($candidates as $candidate) {
            if ($this->files->fileExists($candidate)) {
                return $candidate;
            }
        }

        foreach ($candidates as $candidate) {
            if ($this->detectFolderKey($candidate) !== null) {
                return $candidate;
            }
        }

        return $candidates[0];
    }

    protected function resolveRawReference(Image $image): ?string
    {
        foreach ([$image->path, $image->url] as $candidate) {
            $value = trim((string) $candidate);
            if ($value !== '') {
                return $value;
            }
        }

        return null;
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
