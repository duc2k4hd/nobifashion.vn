<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class LegacyProductsSqlImporter
{
    /**
     * Các cột JSON cần chuẩn hóa trước khi insert/upsert.
     *
     * @var string[]
     */
    protected array $jsonColumns = [
        'category_ids',
        'tag_ids',
        'meta_keywords',
        'category_ids_backup',
    ];

    /**
     * @return array{
     *     file:string,
     *     dry_run:bool,
     *     total_statements:int,
     *     product_statements:int,
     *     imported_rows:int,
     *     dropped_columns:array<int, string>,
     *     remapped_created_by:int,
     *     nulled_locked_by:int,
     *     fallback_created_by:int
     * }
     */
    public function importFile(string $path, array $options = []): array
    {
        if (! File::exists($path)) {
            throw new RuntimeException("Không tìm thấy file SQL: {$path}");
        }

        $sql = File::get($path);

        if (trim($sql) === '') {
            throw new RuntimeException("File SQL rỗng: {$path}");
        }

        $statements = $this->splitStatements($sql);
        $targetColumns = Schema::getColumnListing('products');
        $validAccountIds = DB::table('accounts')->pluck('id')->map(fn ($id) => (int) $id)->all();

        $fallbackCreatedBy = isset($options['fallback_created_by'])
            ? (int) $options['fallback_created_by']
            : $this->resolveFallbackCreatedBy($validAccountIds);

        if (! in_array($fallbackCreatedBy, $validAccountIds, true)) {
            throw new RuntimeException("fallback_created_by={$fallbackCreatedBy} không tồn tại trong bảng accounts.");
        }

        $batchSize = max(1, (int) ($options['batch_size'] ?? 200));
        $dryRun = (bool) ($options['dry_run'] ?? false);

        $summary = [
            'file' => $path,
            'dry_run' => $dryRun,
            'total_statements' => count($statements),
            'product_statements' => 0,
            'imported_rows' => 0,
            'dropped_columns' => [],
            'remapped_created_by' => 0,
            'nulled_locked_by' => 0,
            'fallback_created_by' => $fallbackCreatedBy,
        ];

        $runner = function () use (
            $statements,
            $targetColumns,
            $validAccountIds,
            $fallbackCreatedBy,
            $batchSize,
            $dryRun,
            &$summary
        ): void {
            foreach ($statements as $statement) {
                if (! $this->isProductsInsert($statement)) {
                    continue;
                }

                $summary['product_statements']++;

                [$sourceColumns, $tuples] = $this->parseInsertStatement($statement);
                $insertColumns = array_values(array_filter(
                    $sourceColumns,
                    fn (string $column) => in_array($column, $targetColumns, true)
                ));

                $droppedColumns = array_values(array_diff($sourceColumns, $insertColumns));
                if ($droppedColumns !== []) {
                    $summary['dropped_columns'] = array_values(array_unique(array_merge(
                        $summary['dropped_columns'],
                        $droppedColumns
                    )));
                }

                $rows = [];
                foreach ($tuples as $tuple) {
                    if (count($tuple) !== count($sourceColumns)) {
                        throw new RuntimeException(
                            'Không thể parse đầy đủ một tuple products trong file SQL. '.
                            'Số cột và số giá trị không khớp.'
                        );
                    }

                    $normalized = $this->normalizeRow(
                        $sourceColumns,
                        $tuple,
                        $insertColumns,
                        $validAccountIds,
                        $fallbackCreatedBy
                    );

                    $summary['remapped_created_by'] += $normalized['remapped_created_by'];
                    $summary['nulled_locked_by'] += $normalized['nulled_locked_by'];
                    $rows[] = $normalized['row'];
                }

                $summary['imported_rows'] += count($rows);

                if ($dryRun || $rows === []) {
                    continue;
                }

                $updateColumns = array_values(array_filter(
                    $insertColumns,
                    fn (string $column) => $column !== 'id'
                ));

                foreach (array_chunk($rows, $batchSize) as $chunk) {
                    DB::table('products')->upsert($chunk, ['id'], $updateColumns);
                }
            }
        };

        if ($dryRun) {
            $runner();
        } else {
            DB::transaction($runner);
        }

        return $summary;
    }

    /**
     * @return string[]
     */
    public function splitStatements(string $sql): array
    {
        $statements = [];
        $buffer = '';
        $inString = false;
        $length = strlen($sql);

        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
            $next = $i + 1 < $length ? $sql[$i + 1] : null;

            $buffer .= $char;

            if ($char === "'" && ! $this->isEscaped($sql, $i)) {
                if ($inString && $next === "'") {
                    $buffer .= $next;
                    $i++;
                    continue;
                }

                $inString = ! $inString;
                continue;
            }

            if ($char === ';' && ! $inString) {
                $statement = trim($buffer);
                if ($statement !== '') {
                    $statements[] = $statement;
                }
                $buffer = '';
            }
        }

        $tail = trim($buffer);
        if ($tail !== '') {
            $statements[] = $tail;
        }

        return $statements;
    }

    public function isProductsInsert(string $statement): bool
    {
        $statement = $this->normalizeStatement($statement);

        return (bool) preg_match('/^\s*INSERT\s+INTO\s+`?products`?\b/i', $statement);
    }

    /**
     * @return array{0: array<int, string>, 1: array<int, array<int, string>>}
     */
    public function parseInsertStatement(string $statement): array
    {
        $statement = $this->normalizeStatement($statement);
        $statement = rtrim(trim($statement), ';');

        if (! preg_match('/^\s*INSERT\s+INTO\s+`?products`?\s*\((.*?)\)\s*VALUES\s*(.+)$/is', $statement, $matches)) {
            throw new RuntimeException('Không parse được câu lệnh INSERT INTO products.');
        }

        $columns = $this->parseColumns($matches[1]);
        $tuples = [];

        foreach ($this->parseTuples($matches[2]) as $tupleContent) {
            $tuples[] = $this->parseTupleValues($tupleContent);
        }

        return [$columns, $tuples];
    }

    /**
     * @return string[]
     */
    public function parseColumns(string $columnsPart): array
    {
        return array_values(array_map(
            fn (string $column) => trim($column, " \t\n\r\0\x0B`"),
            array_filter(array_map('trim', explode(',', $columnsPart)), fn (string $column) => $column !== '')
        ));
    }

    /**
     * @return string[]
     */
    public function parseTuples(string $valuesPart): array
    {
        $tuples = [];
        $buffer = '';
        $level = 0;
        $inString = false;
        $length = strlen($valuesPart);

        for ($i = 0; $i < $length; $i++) {
            $char = $valuesPart[$i];
            $next = $i + 1 < $length ? $valuesPart[$i + 1] : null;

            if ($char === "'" && ! $this->isEscaped($valuesPart, $i)) {
                if ($inString && $next === "'") {
                    $buffer .= $char.$next;
                    $i++;
                    continue;
                }

                $inString = ! $inString;
            }

            if (! $inString && $char === '(') {
                if ($level === 0) {
                    $buffer = '';
                } else {
                    $buffer .= $char;
                }
                $level++;
                continue;
            }

            if (! $inString && $char === ')') {
                $level--;
                if ($level === 0) {
                    $tuples[] = $buffer;
                    $buffer = '';
                    continue;
                }
            }

            if ($level > 0) {
                $buffer .= $char;
            }
        }

        return $tuples;
    }

    /**
     * @return string[]
     */
    public function parseTupleValues(string $tupleContent): array
    {
        $values = [];
        $buffer = '';
        $inString = false;
        $length = strlen($tupleContent);

        for ($i = 0; $i < $length; $i++) {
            $char = $tupleContent[$i];
            $next = $i + 1 < $length ? $tupleContent[$i + 1] : null;

            if ($char === "'" && ! $this->isEscaped($tupleContent, $i)) {
                if ($inString && $next === "'") {
                    $buffer .= $char.$next;
                    $i++;
                    continue;
                }

                $inString = ! $inString;
                $buffer .= $char;
                continue;
            }

            if ($char === ',' && ! $inString) {
                $values[] = trim($buffer);
                $buffer = '';
                continue;
            }

            $buffer .= $char;
        }

        $values[] = trim($buffer);

        return $values;
    }

    /**
     * @return array{row: array<string, mixed>, remapped_created_by: int, nulled_locked_by: int}
     */
    public function normalizeRow(
        array $sourceColumns,
        array $tupleValues,
        array $insertColumns,
        array $validAccountIds,
        int $fallbackCreatedBy
    ): array {
        $row = [];
        $remappedCreatedBy = 0;
        $nulledLockedBy = 0;

        $insertColumnsLookup = array_flip($insertColumns);

        foreach ($sourceColumns as $index => $column) {
            if (! isset($insertColumnsLookup[$column])) {
                continue;
            }

            $value = $this->convertSqlLiteral($tupleValues[$index] ?? 'NULL');

            if (in_array($column, $this->jsonColumns, true)) {
                $value = $this->normalizeJsonColumnValue($value, $column);
            }

            if ($column === 'created_by' && ! in_array((int) $value, $validAccountIds, true)) {
                $value = $fallbackCreatedBy;
                $remappedCreatedBy++;
            }

            if ($column === 'locked_by' && $value !== null && ! in_array((int) $value, $validAccountIds, true)) {
                $value = null;
                $nulledLockedBy++;
            }

            $row[$column] = $value;
        }

        return [
            'row' => $row,
            'remapped_created_by' => $remappedCreatedBy,
            'nulled_locked_by' => $nulledLockedBy,
        ];
    }

    public function convertSqlLiteral(string $token): mixed
    {
        $token = trim($token);

        if ($token === '' || strcasecmp($token, 'NULL') === 0) {
            return null;
        }

        if ($token[0] === "'" && substr($token, -1) === "'") {
            return $this->decodeSqlStringLiteral(substr($token, 1, -1));
        }

        if (preg_match('/^-?\d+$/', $token) === 1) {
            return (int) $token;
        }

        if (is_numeric($token)) {
            return (float) $token;
        }

        return $token;
    }

    public function decodeSqlStringLiteral(string $value): string
    {
        $value = str_replace("''", "'", $value);
        $value = str_replace('\\\\', '\\', $value);
        $value = str_replace('\\"', '"', $value);
        $value = str_replace("\\'", "'", $value);
        $value = str_replace('\\0', "\0", $value);
        $value = str_replace('\\n', "\n", $value);
        $value = str_replace('\\r', "\r", $value);
        $value = str_replace('\\t', "\t", $value);

        return $value;
    }

    /**
     * @param  int[]  $validAccountIds
     */
    protected function resolveFallbackCreatedBy(array $validAccountIds): int
    {
        if ($validAccountIds === []) {
            throw new RuntimeException('Bảng accounts đang rỗng, không thể import products có created_by.');
        }

        if (in_array(101, $validAccountIds, true)) {
            return 101;
        }

        return (int) $validAccountIds[0];
    }

    protected function isEscaped(string $text, int $position): bool
    {
        $backslashes = 0;

        for ($i = $position - 1; $i >= 0; $i--) {
            if ($text[$i] !== '\\') {
                break;
            }
            $backslashes++;
        }

        return $backslashes % 2 === 1;
    }

    protected function stripUtf8Bom(string $value): string
    {
        return preg_replace('/^\xEF\xBB\xBF/', '', $value) ?? $value;
    }

    protected function normalizeStatement(string $statement): string
    {
        $statement = $this->stripUtf8Bom($statement);
        $statement = preg_replace('/^\s*(?:--[^\r\n]*(?:\r?\n|$)\s*)+/u', '', $statement) ?? $statement;
        $statement = preg_replace('/^\s*\/\*.*?\*\/\s*/su', '', $statement) ?? $statement;

        return ltrim($statement);
    }

    protected function normalizeJsonColumnValue(mixed $value, string $column): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_array($value)) {
            return $this->encodeJson($value, $column);
        }

        if (! is_string($value)) {
            return $this->encodeJson($value, $column);
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $this->encodeJson($decoded, $column);
        }

        $candidate = str_replace('\\"', '"', $value);
        $candidate = preg_replace('/\\\\u([0-9a-fA-F]{4})/', '\\u$1', $candidate) ?? $candidate;
        $candidate = preg_replace('/\\\\\/([^\s])/', '/$1', $candidate) ?? $candidate;

        $decoded = json_decode($candidate, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $this->encodeJson($decoded, $column);
        }

        throw new RuntimeException("Cột {$column} không phải JSON hợp lệ sau khi chuẩn hóa.");
    }

    protected function encodeJson(mixed $value, string $column): string
    {
        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($encoded === false) {
            throw new RuntimeException("Không thể encode JSON cho cột {$column}.");
        }

        return $encoded;
    }
}
