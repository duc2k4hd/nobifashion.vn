<?php

namespace App\Services\Admin;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ProgressiveSearchService
{
    /**
     * @param  array<int, string>  $primaryColumns
     * @param  array<int, string>  $secondaryColumns
     * @return array{
     *     query: Builder,
     *     mode: 'exact_phrase'|'progressive'|null,
     *     keyword: string|null,
     *     segments: array<int, string>
     * }
     */
    public function apply(Builder $query, ?string $keyword, array $primaryColumns, array $secondaryColumns = []): array
    {
        $normalizedKeyword = $this->normalizeKeyword($keyword);

        if ($normalizedKeyword === '') {
            return [
                'query' => $query,
                'mode' => null,
                'keyword' => null,
                'segments' => [],
            ];
        }

        $strictQuery = clone $query;
        $this->applyPhraseWhere($strictQuery, $normalizedKeyword, [...$primaryColumns, ...$secondaryColumns]);

        if ($strictQuery->exists()) {
            $this->applyPhraseWhere($query, $normalizedKeyword, [...$primaryColumns, ...$secondaryColumns]);
            $this->applyPhraseOrdering($query, $normalizedKeyword, $primaryColumns, $secondaryColumns);

            return [
                'query' => $query,
                'mode' => 'exact_phrase',
                'keyword' => $normalizedKeyword,
                'segments' => [$normalizedKeyword],
            ];
        }

        $segments = $this->buildSegments($normalizedKeyword);

        if ($segments === []) {
            $this->applyPhraseWhere($query, $normalizedKeyword, [...$primaryColumns, ...$secondaryColumns]);
            $this->applyPhraseOrdering($query, $normalizedKeyword, $primaryColumns, $secondaryColumns);

            return [
                'query' => $query,
                'mode' => 'exact_phrase',
                'keyword' => $normalizedKeyword,
                'segments' => [$normalizedKeyword],
            ];
        }

        $this->applySegmentsWhere($query, $segments, [...$primaryColumns, ...$secondaryColumns]);
        $this->applySegmentOrdering($query, $normalizedKeyword, $segments, $primaryColumns, $secondaryColumns);

        return [
            'query' => $query,
            'mode' => 'progressive',
            'keyword' => $normalizedKeyword,
            'segments' => $segments,
        ];
    }

    public function normalizeKeyword(?string $keyword): string
    {
        $keyword = Str::lower(Str::squish((string) $keyword));

        if ($keyword === '') {
            return '';
        }

        preg_match_all('/[\p{L}\p{N}]+/u', $keyword, $matches);

        return trim(implode(' ', $matches[0] ?? []));
    }

    /**
     * Tạo các cụm tìm kiếm liên tiếp từ dài xuống ngắn để fallback gần đúng.
     *
     * @return array<int, string>
     */
    public function buildSegments(string $keyword, int $maxSegments = 15): array
    {
        $normalizedKeyword = $this->normalizeKeyword($keyword);
        $tokens = preg_split('/\s+/u', $normalizedKeyword, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($tokens === []) {
            return [];
        }

        $segments = [];
        $seen = [];
        $tokenCount = count($tokens);

        for ($size = $tokenCount; $size >= 1; $size--) {
            for ($offset = 0; $offset <= $tokenCount - $size; $offset++) {
                $segment = implode(' ', array_slice($tokens, $offset, $size));

                if ($size === 1 && mb_strlen($segment) < 2) {
                    continue;
                }

                if (isset($seen[$segment])) {
                    continue;
                }

                $segments[] = $segment;
                $seen[$segment] = true;

                if (count($segments) >= $maxSegments) {
                    return $segments;
                }
            }
        }

        return $segments;
    }

    /**
     * @param  array<int, string>  $columns
     */
    protected function applyPhraseWhere(Builder $query, string $keyword, array $columns): void
    {
        $pattern = '%' . $keyword . '%';

        $query->where(function (Builder $where) use ($columns, $pattern) {
            foreach ($columns as $index => $column) {
                $method = $index === 0 ? 'whereRaw' : 'orWhereRaw';
                $where->{$method}($this->likeExpression($column), [$pattern]);
            }
        });
    }

    /**
     * @param  array<int, string>  $columns
     */
    protected function applySegmentsWhere(Builder $query, array $segments, array $columns): void
    {
        $query->where(function (Builder $where) use ($segments, $columns) {
            $firstCondition = true;

            foreach ($segments as $segment) {
                $pattern = '%' . $segment . '%';

                foreach ($columns as $column) {
                    $method = $firstCondition ? 'whereRaw' : 'orWhereRaw';
                    $where->{$method}($this->likeExpression($column), [$pattern]);
                    $firstCondition = false;
                }
            }
        });
    }

    /**
     * @param  array<int, string>  $primaryColumns
     * @param  array<int, string>  $secondaryColumns
     */
    protected function applyPhraseOrdering(Builder $query, string $keyword, array $primaryColumns, array $secondaryColumns): void
    {
        [$scoreSql, $bindings] = $this->buildPhraseScoreSql($keyword, $primaryColumns, $secondaryColumns);
        $query->orderByRaw($scoreSql . ' DESC', $bindings);
    }

    /**
     * @param  array<int, string>  $primaryColumns
     * @param  array<int, string>  $secondaryColumns
     * @param  array<int, string>  $segments
     */
    protected function applySegmentOrdering(
        Builder $query,
        string $keyword,
        array $segments,
        array $primaryColumns,
        array $secondaryColumns
    ): void {
        [$phraseScoreSql, $phraseBindings] = $this->buildPhraseScoreSql(
            $keyword,
            $primaryColumns,
            $secondaryColumns,
            [
                'primary_exact' => 800,
                'primary_prefix' => 540,
                'primary_contains' => 320,
                'secondary_exact' => 360,
                'secondary_prefix' => 260,
                'secondary_contains' => 180,
            ]
        );

        [$segmentScoreSql, $segmentBindings] = $this->buildSegmentScoreSql($segments, $primaryColumns, $secondaryColumns);

        $query->orderByRaw(
            '(' . $phraseScoreSql . ' + ' . $segmentScoreSql . ') DESC',
            [...$phraseBindings, ...$segmentBindings]
        );
    }

    /**
     * @param  array<int, string>  $primaryColumns
     * @param  array<int, string>  $secondaryColumns
     * @return array{0: string, 1: array<int, string>}
     */
    protected function buildPhraseScoreSql(
        string $keyword,
        array $primaryColumns,
        array $secondaryColumns,
        array $weights = []
    ): array {
        $weights = array_merge([
            'primary_exact' => 1800,
            'primary_prefix' => 1250,
            'primary_contains' => 900,
            'secondary_exact' => 700,
            'secondary_prefix' => 500,
            'secondary_contains' => 320,
        ], $weights);

        $conditions = [];
        $bindings = [];

        $exact = $keyword;
        $prefix = $keyword . '%';
        $contains = '%' . $keyword . '%';

        foreach ($primaryColumns as $column) {
            $conditions[] = 'CASE WHEN ' . $this->comparisonExpression($column, '=') . ' THEN ' . (int) $weights['primary_exact'] . ' ELSE 0 END';
            $bindings[] = $exact;

            $conditions[] = 'CASE WHEN ' . $this->comparisonExpression($column, 'LIKE') . ' THEN ' . (int) $weights['primary_prefix'] . ' ELSE 0 END';
            $bindings[] = $prefix;

            $conditions[] = 'CASE WHEN ' . $this->comparisonExpression($column, 'LIKE') . ' THEN ' . (int) $weights['primary_contains'] . ' ELSE 0 END';
            $bindings[] = $contains;
        }

        foreach ($secondaryColumns as $column) {
            $conditions[] = 'CASE WHEN ' . $this->comparisonExpression($column, '=') . ' THEN ' . (int) $weights['secondary_exact'] . ' ELSE 0 END';
            $bindings[] = $exact;

            $conditions[] = 'CASE WHEN ' . $this->comparisonExpression($column, 'LIKE') . ' THEN ' . (int) $weights['secondary_prefix'] . ' ELSE 0 END';
            $bindings[] = $prefix;

            $conditions[] = 'CASE WHEN ' . $this->comparisonExpression($column, 'LIKE') . ' THEN ' . (int) $weights['secondary_contains'] . ' ELSE 0 END';
            $bindings[] = $contains;
        }

        return [implode(' + ', $conditions), $bindings];
    }

    /**
     * @param  array<int, string>  $segments
     * @param  array<int, string>  $primaryColumns
     * @param  array<int, string>  $secondaryColumns
     * @return array{0: string, 1: array<int, string>}
     */
    protected function buildSegmentScoreSql(array $segments, array $primaryColumns, array $secondaryColumns): array
    {
        $conditions = [];
        $bindings = [];

        foreach ($segments as $index => $segment) {
            $wordCount = substr_count($segment, ' ') + 1;
            $baseWeight = max(60, ($wordCount * 140) - ($index * 8));
            $primaryWeight = $baseWeight + min(120, mb_strlen($segment) * 3);
            $secondaryWeight = (int) floor($primaryWeight * 0.45);
            $pattern = '%' . $segment . '%';

            foreach ($primaryColumns as $column) {
                $conditions[] = 'CASE WHEN ' . $this->comparisonExpression($column, 'LIKE') . ' THEN ' . $primaryWeight . ' ELSE 0 END';
                $bindings[] = $pattern;
            }

            foreach ($secondaryColumns as $column) {
                $conditions[] = 'CASE WHEN ' . $this->comparisonExpression($column, 'LIKE') . ' THEN ' . $secondaryWeight . ' ELSE 0 END';
                $bindings[] = $pattern;
            }
        }

        return [implode(' + ', $conditions), $bindings];
    }

    protected function likeExpression(string $column): string
    {
        return 'LOWER(COALESCE(' . $column . ", '')) LIKE ?";
    }

    protected function comparisonExpression(string $column, string $operator): string
    {
        return 'LOWER(COALESCE(' . $column . ", '')) " . $operator . ' ?';
    }
}
