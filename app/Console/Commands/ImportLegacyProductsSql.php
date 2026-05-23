<?php

namespace App\Console\Commands;

use App\Services\Admin\LegacyProductsSqlImporter;
use Illuminate\Console\Command;
use Throwable;

class ImportLegacyProductsSql extends Command
{
    protected $signature = 'products:import-legacy-sql
        {path : Đường dẫn file SQL dump cũ}
        {--dry-run : Chỉ phân tích, không ghi vào database}
        {--fallback-created-by= : Account ID dùng thay cho created_by cũ không còn tồn tại}
        {--batch=200 : Số dòng upsert mỗi batch}';

    protected $description = 'Import dump SQL cũ vào bảng products hiện tại, chỉ lấy INSERT INTO products và tự map schema.';

    public function handle(LegacyProductsSqlImporter $importer): int
    {
        $path = $this->resolvePath((string) $this->argument('path'));

        try {
            $summary = $importer->importFile($path, [
                'dry_run' => (bool) $this->option('dry-run'),
                'fallback_created_by' => $this->option('fallback-created-by'),
                'batch_size' => (int) $this->option('batch'),
            ]);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->table(
            ['Mục', 'Giá trị'],
            [
                ['File', $summary['file']],
                ['Dry run', $summary['dry_run'] ? 'yes' : 'no'],
                ['Tổng statement', (string) $summary['total_statements']],
                ['Statement products', (string) $summary['product_statements']],
                ['Số rows import/upsert', (string) $summary['imported_rows']],
                ['fallback_created_by', (string) $summary['fallback_created_by']],
                ['created_by remap', (string) $summary['remapped_created_by']],
                ['locked_by -> null', (string) $summary['nulled_locked_by']],
                ['Cột bị bỏ', $summary['dropped_columns'] === [] ? '(không có)' : implode(', ', $summary['dropped_columns'])],
            ]
        );

        if ($summary['product_statements'] === 0) {
            $this->warn('Không tìm thấy INSERT INTO products nào trong file.');

            return self::SUCCESS;
        }

        if ($summary['dry_run']) {
            $this->info('Đã phân tích xong file SQL. Không có thay đổi nào được ghi vào database.');
        } else {
            $this->info('Import products hoàn tất.');
        }

        return self::SUCCESS;
    }

    protected function resolvePath(string $path): string
    {
        if ($path === '') {
            throw new RuntimeException('Bạn cần truyền đường dẫn file SQL.');
        }

        if (is_file($path)) {
            return realpath($path) ?: $path;
        }

        $basePath = base_path($path);
        if (is_file($basePath)) {
            return realpath($basePath) ?: $basePath;
        }

        $storagePath = storage_path($path);
        if (is_file($storagePath)) {
            return realpath($storagePath) ?: $storagePath;
        }

        return $path;
    }
}
