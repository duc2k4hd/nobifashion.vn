<?php

namespace App\Services\Media;

use App\Models\Image;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class MediaCleanupService
{
    /**
     * @var string[]
     */
    protected array $allowedImageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'svg'];

    public function __construct(
        protected FileHelperService $files
    ) {
        $this->directories = config('media.directories', []);
        $cleanupKeys = config('media.cleanup_directories', ['banners', 'brands', 'categories', 'clothes', 'posts']);
        $this->cleanupDirectoryKeys = array_values(array_unique(array_map('strval', $cleanupKeys)));
        $this->cleanupDirectories = array_intersect_key(
            $this->directories,
            array_flip($this->cleanupDirectoryKeys)
        );
    }

    protected array $directories;
    protected array $cleanupDirectoryKeys;
    protected array $cleanupDirectories;

    /**
     * @return array<string, mixed>
     */
    public function preview(): array
    {
        return $this->finalizeSummary($this->buildPlan(), true);
    }

    /**
     * @return array<string, mixed>
     */
    public function cleanup(): array
    {
        $plan = $this->buildPlan();
        $deletedRows = 0;
        $deletedFiles = 0;
        $failedFiles = [];

        if ($plan['database_row_ids'] !== []) {
            $deletedRows = DB::transaction(
                fn () => Image::query()->whereIn('id', $plan['database_row_ids'])->delete()
            );
        }

        foreach ($plan['physical_file_paths'] as $relativePath) {
            if ($this->files->deleteManagedFile($relativePath, $this->cleanupDirectories)) {
                $deletedFiles++;
                continue;
            }

            $failedFiles[] = $relativePath;
        }

        return $this->finalizeSummary($plan, false, $deletedRows, $deletedFiles, $failedFiles);
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildPlan(): array
    {
        $records = [];
        $usageCounts = [];
        $explicitManagedReferences = $this->collectExplicitManagedReferences();

        $images = Image::query()->get([
            'id',
            'product_id',
            'entity_type',
            'entity_id',
            'role',
            'context',
            'path',
            'url',
            'thumbnail_url',
            'medium_url',
        ]);

        foreach ($images as $image) {
            $record = $this->mapImageRecord($image);
            if (! $record['is_cleanup_eligible']) {
                continue;
            }

            $records[] = $record;

            if ($record['asset_key'] !== null) {
                $usageCounts[$record['asset_key']] = ($usageCounts[$record['asset_key']] ?? 0) + 1;
            }
        }

        $missingRecords = [];
        $unassignedRecords = [];
        $rowIdsToDelete = [];
        $preservedSharedFiles = 0;

        foreach ($records as &$record) {
            $usageCount = $record['asset_key'] !== null
                ? (int) ($usageCounts[$record['asset_key']] ?? 0)
                : 0;

            $record['usage_count'] = $usageCount;
            $record['is_explicitly_referenced'] = $this->recordHasExplicitReference(
                $record,
                $explicitManagedReferences
            );

            if ($record['is_broken']) {
                $missingRecords[$record['id']] = $record;
                $rowIdsToDelete[$record['id']] = true;
                continue;
            }

            if (! $record['is_unassigned'] || $record['is_explicitly_referenced']) {
                continue;
            }

            $unassignedRecords[$record['id']] = $record;
            $rowIdsToDelete[$record['id']] = true;

            if ($record['asset_key'] !== null && $usageCount > 1) {
                $preservedSharedFiles++;
            }
        }
        unset($record);

        $remainingReferences = $explicitManagedReferences;
        foreach ($records as $record) {
            if (isset($rowIdsToDelete[$record['id']])) {
                continue;
            }

            foreach ($record['managed_paths'] as $managedPath) {
                $remainingReferences[$managedPath] = true;
            }
        }

        $orphanFiles = [];
        foreach ($this->scanFilesystemPaths() as $relativePath) {
            if (! isset($remainingReferences[$relativePath])) {
                $orphanFiles[$relativePath] = $relativePath;
            }
        }

        return [
            'database_row_ids' => array_map('intval', array_keys($rowIdsToDelete)),
            'physical_file_paths' => array_values($orphanFiles),
            'missing_database_rows' => count($missingRecords),
            'unassigned_database_rows' => count($unassignedRecords),
            'orphan_physical_files' => count($orphanFiles),
            'preserved_shared_files' => $preservedSharedFiles,
            'missing_database_samples' => $this->sampleRelativePaths($missingRecords),
            'unassigned_database_samples' => $this->sampleRelativePaths($unassignedRecords),
            'orphan_file_samples' => array_slice(array_values($orphanFiles), 0, 5),
            'scanned_directory_keys' => array_keys($this->cleanupDirectories),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function finalizeSummary(
        array $plan,
        bool $dryRun,
        int $deletedRows = 0,
        int $deletedFiles = 0,
        array $failedFiles = []
    ): array {
        $databaseRowsToDelete = count($plan['database_row_ids'] ?? []);
        $physicalFilesToDelete = count($plan['physical_file_paths'] ?? []);

        return [
            'success' => true,
            'dry_run' => $dryRun,
            'database_rows_to_delete' => $databaseRowsToDelete,
            'physical_files_to_delete' => $physicalFilesToDelete,
            'database_rows_deleted' => $dryRun ? 0 : $deletedRows,
            'physical_files_deleted' => $dryRun ? 0 : $deletedFiles,
            'physical_files_failed_count' => $dryRun ? 0 : count($failedFiles),
            'physical_files_failed_samples' => $dryRun ? [] : array_slice($failedFiles, 0, 5),
            'missing_database_rows' => (int) ($plan['missing_database_rows'] ?? 0),
            'unassigned_database_rows' => (int) ($plan['unassigned_database_rows'] ?? 0),
            'orphan_physical_files' => (int) ($plan['orphan_physical_files'] ?? 0),
            'preserved_shared_files' => (int) ($plan['preserved_shared_files'] ?? 0),
            'samples' => [
                'missing_database_rows' => $plan['missing_database_samples'] ?? [],
                'unassigned_database_rows' => $plan['unassigned_database_samples'] ?? [],
                'orphan_physical_files' => $plan['orphan_file_samples'] ?? [],
            ],
            'scanned_directory_keys' => $plan['scanned_directory_keys'] ?? array_keys($this->cleanupDirectories),
            'message' => $this->buildMessage(
                $dryRun,
                $databaseRowsToDelete,
                $physicalFilesToDelete,
                $deletedRows,
                $deletedFiles,
                count($failedFiles),
                (int) ($plan['preserved_shared_files'] ?? 0)
            ),
        ];
    }

    protected function buildMessage(
        bool $dryRun,
        int $databaseRowsToDelete,
        int $physicalFilesToDelete,
        int $deletedRows,
        int $deletedFiles,
        int $failedFiles,
        int $preservedSharedFiles
    ): string {
        if ($dryRun) {
            if ($databaseRowsToDelete === 0 && $physicalFilesToDelete === 0) {
                return 'Không phát hiện record lỗi hoặc file rác cần dọn dẹp.';
            }

            return "Phát hiện {$databaseRowsToDelete} record database và {$physicalFilesToDelete} file vật lý có thể dọn dẹp.";
        }

        $message = "Đã xóa {$deletedRows} record database và {$deletedFiles} file vật lý.";

        if ($failedFiles > 0) {
            $message .= " {$failedFiles} file vật lý không xóa được.";
        }

        if ($preservedSharedFiles > 0) {
            $message .= " {$preservedSharedFiles} file dùng chung được giữ lại.";
        }

        return $message;
    }

    /**
     * @return array{
     *     id:int,
     *     asset_key:?string,
     *     managed_paths:array<int, string>,
     *     is_cleanup_eligible:bool,
     *     is_broken:bool,
     *     is_unassigned:bool,
     *     usage_count:int
     * }
     */
    protected function mapImageRecord(Image $image): array
    {
        $entityType = $image->entity_type ?: ($image->product_id ? 'product' : null);
        $entityId = $image->entity_id ?: $image->product_id;
        $fallbackFolderKey = $this->defaultFolderKeyFor($entityType, $image->context);
        $originalPath = $this->resolvePrimaryPath($image, $fallbackFolderKey);
        $folderKey = $this->detectFolderKey($originalPath) ?? $fallbackFolderKey;
        $thumbnailPath = $this->normalizeStoredPath($image->thumbnail_url, $folderKey);
        $mediumPath = $this->normalizeStoredPath($image->medium_url, $folderKey);
        $sourceValues = array_values(array_filter(array_map(
            static fn (?string $value): ?string => ($value = trim((string) $value)) !== '' ? $value : null,
            [$image->path, $image->url]
        )));
        $isExternal = $originalPath === null
            && collect($sourceValues)->contains(
                static fn (string $value): bool => Str::startsWith($value, ['http://', 'https://'])
            );
        $isLibrary = $image->entity_type === 'library';
        $hasEntity = $entityId !== null && ! $isLibrary;
        $managedPaths = array_values(array_unique(array_filter([
            $originalPath,
            $thumbnailPath,
            $mediumPath,
            $this->guessWebpVariant($originalPath),
        ])));
        $cleanupEligiblePaths = array_values(array_filter(
            $managedPaths,
            fn (string $path) => $this->isCleanupPath($path)
        ));
        $isCleanupEligible = $cleanupEligiblePaths !== []
            || ($folderKey !== null && $this->isCleanupFolderKey($folderKey));

        return [
            'id' => (int) $image->id,
            'asset_key' => $this->isCleanupPath($originalPath) ? $originalPath : null,
            'managed_paths' => $cleanupEligiblePaths,
            'is_cleanup_eligible' => $isCleanupEligible,
            'is_broken' => ! $isExternal && ($originalPath === null || ! $this->files->fileExists($originalPath)),
            'is_unassigned' => ! $hasEntity,
            'usage_count' => 0,
        ];
    }

    /**
     * @return array<string, true>
     */
    protected function collectExplicitManagedReferences(): array
    {
        $references = [];

        if (! Schema::hasTable('posts')) {
            return $references;
        }

        $postQuery = DB::table('posts')->select(['content', 'thumbnail']);
        if (Schema::hasColumn('posts', 'deleted_at')) {
            $postQuery->whereNull('deleted_at');
        }

        foreach ($postQuery->cursor() as $post) {
            $thumbnailPath = $this->normalizeStoredPath($post->thumbnail ?? null, 'posts');
            if ($this->isCleanupPath($thumbnailPath)) {
                $references[$thumbnailPath] = true;
            }

            foreach ($this->extractManagedPathsFromHtml((string) ($post->content ?? '')) as $managedPath) {
                $references[$managedPath] = true;
            }
        }

        return $references;
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
            if ($this->isCleanupPath($managedPath)) {
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

            return $this->isCleanupPath($normalized) ? $normalized : null;
        }

        return $this->normalizeStoredPath($path, $fallbackFolderKey);
    }

    /**
     * @param  array{managed_paths:array<int, string>}  $record
     * @param  array<string, true>  $explicitManagedReferences
     */
    protected function recordHasExplicitReference(array $record, array $explicitManagedReferences): bool
    {
        foreach ($record['managed_paths'] as $managedPath) {
            if (isset($explicitManagedReferences[$managedPath])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, array{id:int, managed_paths:array<int, string>, asset_key:?string, is_broken:bool, is_unassigned:bool, usage_count:int}>  $records
     * @return string[]
     */
    protected function sampleRelativePaths(array $records): array
    {
        return array_slice(array_values(array_filter(array_map(
            function (array $record): ?string {
                return $record['asset_key']
                    ?? $record['managed_paths'][0]
                    ?? null;
            },
            $records
        ))), 0, 5);
    }

    /**
     * @return string[]
     */
    protected function scanFilesystemPaths(): array
    {
        $paths = [];

        foreach ($this->cleanupDirectories as $relativeDirectory) {
            $absoluteDirectory = public_path(trim($relativeDirectory, '/'));
            if (! is_dir($absoluteDirectory)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($absoluteDirectory, RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterator as $fileInfo) {
                if (! $fileInfo->isFile()) {
                    continue;
                }

                $extension = strtolower($fileInfo->getExtension());
                if (! in_array($extension, $this->allowedImageExtensions, true)) {
                    continue;
                }

                $relativePath = $this->files->absoluteToRelativePath($fileInfo->getPathname());
                if (! $relativePath) {
                    continue;
                }

                $paths[$relativePath] = $relativePath;
            }
        }

        return array_values($paths);
    }

    protected function normalizeStoredPath(?string $path, ?string $fallbackFolderKey = null): ?string
    {
        $normalized = $this->files->normalizeRelativePath($path);
        if (! $normalized) {
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
            if ($this->isCleanupPath($candidate)) {
                return $candidate;
            }
        }

        return $candidates[0];
    }

    protected function detectFolderKey(?string $relativePath): ?string
    {
        if (! $relativePath || Str::startsWith($relativePath, ['http://', 'https://'])) {
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

    protected function isCleanupFolderKey(?string $folderKey): bool
    {
        return $folderKey !== null && array_key_exists($folderKey, $this->cleanupDirectories);
    }

    protected function isCleanupPath(?string $relativePath): bool
    {
        return $this->detectCleanupFolderKey($relativePath) !== null;
    }

    protected function detectCleanupFolderKey(?string $relativePath): ?string
    {
        if (! $relativePath || Str::startsWith($relativePath, ['http://', 'https://'])) {
            return null;
        }

        $normalized = trim($relativePath, '/');
        foreach ($this->cleanupDirectories as $key => $directory) {
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
            'brand' => 'brands',
            'banner' => 'banners',
            'profile' => 'accounts_avatars',
            default => null,
        };
    }

    protected function guessWebpVariant(?string $relativePath): ?string
    {
        if (! $relativePath || Str::endsWith($relativePath, ['.webp', '.avif', '.svg'])) {
            return null;
        }

        $info = pathinfo($relativePath);
        if (empty($info['dirname']) || empty($info['filename'])) {
            return null;
        }

        return trim($info['dirname'], '/') . '/' . $info['filename'] . '.webp';
    }
}
