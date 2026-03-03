<?php

namespace App\Console\Commands;

use App\Models\MediaLibraryFile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class IndexMediaLibrary extends Command
{
    protected $signature = 'media:index
                            {context=all : Context cần index: product, post, hoặc all}
                            {--fresh : Xóa toàn bộ DB trước khi index lại}
                            {--batch=100 : Số record mỗi batch INSERT}';

    protected $description = 'Scan và index toàn bộ ảnh trong thư mục media vào database';

    protected array $contextMap = [
        'post'    => 'posts',
        'product' => 'clothes',
    ];

    protected array $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'avif'];

    public function handle(): int
    {
        $contextArg = $this->argument('context');
        $fresh      = $this->option('fresh');

        $contexts = $contextArg === 'all'
            ? ['post', 'product']
            : [$contextArg];

        foreach ($contexts as $context) {
            if (!isset($this->contextMap[$context])) {
                $this->error("Context không hợp lệ: $context (dùng: post, product, all)");
                return 1;
            }
        }

        if ($fresh) {
            if ($contextArg === 'all') {
                MediaLibraryFile::truncate();
                $this->info('Đã xóa toàn bộ dữ liệu cũ.');
            } else {
                MediaLibraryFile::whereIn('context', $contexts)->delete();
                $this->info('Đã xóa dữ liệu cũ của context: ' . implode(', ', $contexts));
            }
        }

        foreach ($contexts as $context) {
            $this->indexContext($context);
        }

        return 0;
    }

    private function indexContext(string $context): void
    {
        $folder   = $this->contextMap[$context];
        $basePath = public_path('clients/assets/img/' . $folder);

        if (!is_dir($basePath)) {
            $this->warn("Thư mục không tồn tại: $basePath");
            return;
        }

        $this->info("Đang scan [{$context}]: $basePath");

        $batchSize  = (int) $this->option('batch');
        $batch      = [];
        $inserted   = 0;
        $skipped    = 0;
        $total      = 0;
        $now        = now()->toDateTimeString();
        $publicPath = public_path();

        // Lazy iteration — KHÔNG dùng iterator_to_array, không load vào RAM
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($basePath, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            /** @var \SplFileInfo $file */
            $total++;
            $ext = strtolower($file->getExtension());

            if (!in_array($ext, $this->allowedExtensions)) {
                $skipped++;
                continue;
            }

            $absolute = str_replace('\\', '/', $file->getRealPath());
            $relative = ltrim(str_replace(str_replace('\\', '/', $publicPath), '', $absolute), '/');

            $batch[] = [
                'name'             => $file->getFilename(),
                'path'             => $relative,
                'url'              => 'https://' . request()->getHost() . '/' . $relative,
                'extension'        => $ext,
                'mime_type'        => null, // bỏ qua mime_content_type() cho tốc độ
                'context'          => $context,
                'size'             => $file->getSize(),
                'width'            => null,  // bỏ qua getimagesize() cho tốc độ
                'height'           => null,
                'file_modified_at' => date('Y-m-d H:i:s', $file->getMTime()),
                'created_at'       => $now,
                'updated_at'       => $now,
            ];

            $inserted++;

            if (count($batch) >= $batchSize) {
                $this->insertBatch($batch);
                $batch = [];

                // Hiện tiến độ mà không cần biết tổng số
                if ($inserted % 1000 === 0) {
                    $this->line("  → Đã index $inserted files...");
                }
            }
        }

        // Flush batch còn lại
        if (!empty($batch)) {
            $this->insertBatch($batch);
        }

        $this->info("✅ [{$context}] Hoàn tất: $inserted ảnh đã index, $skipped bỏ qua (tổng scan: $total).");
    }

    /**
     * INSERT IGNORE để bỏ qua trùng (dựa trên UNIQUE KEY `path`)
     * Không cần load toàn bộ path từ DB để check trước
     */
    private function insertBatch(array $rows): void
    {
        if (empty($rows)) {
            return;
        }

        try {
            // INSERT IGNORE: bỏ qua hàng trùng path, không throw exception
            $columns = array_keys($rows[0]);
            $placeholders = implode(', ', array_fill(0, count($columns), '?'));
            $columnList   = implode(', ', array_map(fn($c) => "`$c`", $columns));

            $values  = [];
            $bindings = [];
            foreach ($rows as $row) {
                $values[]   = "($placeholders)";
                $bindings   = array_merge($bindings, array_values($row));
            }

            $sql = "INSERT IGNORE INTO `media_library_files` ($columnList) VALUES " . implode(', ', $values);
            DB::statement($sql, $bindings);

        } catch (\Throwable $e) {
            $this->warn("  ⚠️ Batch insert lỗi: " . $e->getMessage() . " — bỏ qua batch này.");
        }
    }
}