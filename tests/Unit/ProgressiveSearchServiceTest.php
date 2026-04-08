<?php

namespace Tests\Unit;

use App\Services\Admin\ProgressiveSearchService;
use PHPUnit\Framework\TestCase;

class ProgressiveSearchServiceTest extends TestCase
{
    public function test_normalize_keyword_removes_extra_spaces_and_symbols(): void
    {
        $service = new ProgressiveSearchService();

        $normalized = $service->normalizeKeyword('  Áo   thun,   nữ!!!  oversize  ');

        $this->assertSame('áo thun nữ oversize', $normalized);
    }

    public function test_build_segments_returns_longest_contiguous_phrases_first(): void
    {
        $service = new ProgressiveSearchService();

        $segments = $service->buildSegments('áo thun nữ oversize', 10);

        $this->assertSame([
            'áo thun nữ oversize',
            'áo thun nữ',
            'thun nữ oversize',
            'áo thun',
            'thun nữ',
            'nữ oversize',
            'áo',
            'thun',
            'nữ',
            'oversize',
        ], $segments);
    }

    public function test_build_segments_skips_single_character_tokens(): void
    {
        $service = new ProgressiveSearchService();

        $segments = $service->buildSegments('áo a nữ', 10);

        $this->assertSame([
            'áo a nữ',
            'áo a',
            'a nữ',
            'áo',
            'nữ',
        ], $segments);
    }
}
