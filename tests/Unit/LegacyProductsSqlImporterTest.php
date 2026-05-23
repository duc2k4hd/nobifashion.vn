<?php

namespace Tests\Unit;

use App\Services\Admin\LegacyProductsSqlImporter;
use PHPUnit\Framework\TestCase;

class LegacyProductsSqlImporterTest extends TestCase
{
    public function test_split_statements_keeps_semicolons_inside_sql_strings(): void
    {
        $service = new LegacyProductsSqlImporter();

        $sql = <<<'SQL'
INSERT INTO `products` (`id`, `sku`, `name`, `description`, `created_by`, `locked_by`, `category_ids_backup`) VALUES
(1, 'SKU-001', 'Ao 1', '<p>Noi dung co dau ; va, JSON ["a","b"]</p>', 1, 999, NULL);
INSERT INTO `profiles` (`id`, `full_name`) VALUES (1, 'Admin');
SQL;

        $statements = $service->splitStatements($sql);

        $this->assertCount(2, $statements);
        $this->assertTrue($service->isProductsInsert($statements[0]));
        $this->assertFalse($service->isProductsInsert($statements[1]));
    }

    public function test_parse_insert_statement_handles_multiple_rows(): void
    {
        $service = new LegacyProductsSqlImporter();

        $statement = <<<'SQL'
INSERT INTO `products` (`id`, `sku`, `price`, `category_ids`) VALUES
(1, 'SKU-001', 1000.00, '["2","49"]'),
(2, 'SKU-002', NULL, '["53","54"]');
SQL;

        [$columns, $tuples] = $service->parseInsertStatement($statement);

        $this->assertSame(['id', 'sku', 'price', 'category_ids'], $columns);
        $this->assertCount(2, $tuples);
        $this->assertSame(['1', "'SKU-001'", '1000.00', '\'["2","49"]\''], $tuples[0]);
        $this->assertSame(['2', "'SKU-002'", 'NULL', '\'["53","54"]\''], $tuples[1]);
    }

    public function test_is_products_insert_accepts_utf8_bom(): void
    {
        $service = new LegacyProductsSqlImporter();

        $statement = "\xEF\xBB\xBFINSERT INTO `products` (`id`) VALUES (1);";

        $this->assertTrue($service->isProductsInsert($statement));
    }

    public function test_is_products_insert_accepts_leading_mysql_comments(): void
    {
        $service = new LegacyProductsSqlImporter();

        $statement = <<<'SQL'
-- --------------------------------------------------------
-- Đang đổ dữ liệu cho bảng `products`
INSERT INTO `products` (`id`) VALUES (1);
SQL;

        $this->assertTrue($service->isProductsInsert($statement));
    }

    public function test_normalize_row_remaps_missing_accounts_and_drops_unknown_columns(): void
    {
        $service = new LegacyProductsSqlImporter();

        $result = $service->normalizeRow(
            ['id', 'sku', 'name', 'created_by', 'locked_by', 'category_ids_backup'],
            ['515', "'SKU-515'", "'Ao Tia Set'", '1', '999', '\'["53","54"]\''],
            ['id', 'sku', 'name', 'created_by', 'locked_by'],
            [101],
            101
        );

        $this->assertSame([
            'id' => 515,
            'sku' => 'SKU-515',
            'name' => 'Ao Tia Set',
            'created_by' => 101,
            'locked_by' => null,
        ], $result['row']);
        $this->assertSame(1, $result['remapped_created_by']);
        $this->assertSame(1, $result['nulled_locked_by']);
    }

    public function test_normalize_row_converts_legacy_json_strings_to_valid_json(): void
    {
        $service = new LegacyProductsSqlImporter();

        $result = $service->normalizeRow(
            ['category_ids', 'meta_keywords', 'tag_ids', 'created_by'],
            [
                '\'[\\"2\\",\\"49\\",\\"53\\",\\"54\\"]\'',
                '\'[\\"\\\\u00e1o s\\\\u01a1 mi nam\\",\\"s\\\\u01a1 mi nam\\"]\'',
                '\'[56,787,326]\'',
                '101',
            ],
            ['category_ids', 'meta_keywords', 'tag_ids', 'created_by'],
            [101],
            101
        );

        $this->assertSame('["2","49","53","54"]', $result['row']['category_ids']);
        $this->assertSame('["áo sơ mi nam","sơ mi nam"]', $result['row']['meta_keywords']);
        $this->assertSame('[56,787,326]', $result['row']['tag_ids']);
        $this->assertSame(101, $result['row']['created_by']);
    }
}
