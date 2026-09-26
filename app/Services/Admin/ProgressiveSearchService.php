<?php

namespace App\Services\Admin;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ProgressiveSearchService
{
    /**
     * Escape các ký tự đặc biệt của LIKE SQL (%, _, \)
     */
    protected function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

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
        $rawKeyword = trim(preg_replace('/\s+/u', ' ', (string) $keyword));

        if ($rawKeyword === '') {
            return [
                'query' => $query,
                'mode' => null,
                'keyword' => null,
                'segments' => [],
            ];
        }

        $allColumns = array_merge($primaryColumns, $secondaryColumns);
        $lowerRaw = mb_strtolower($rawKeyword, 'UTF-8');

        // =========================================================================
        // TẦNG 1: CỤM TỪ ĐẦY ĐỦ (FULL PHRASE) - HỖ TRỢ KÝ TỰ ĐẶC BIỆT
        // =========================================================================

        // 1.1 Khớp chính xác cụm từ nguyên bản (giữ nguyên ký tự đặc biệt như :, ,, -, &, +, [], (), v.v.)
        $escapedRaw = $this->escapeLike($lowerRaw);
        $strictQuery = clone $query;
        $this->applyPhraseWhere($strictQuery, $escapedRaw, $allColumns);

        if ($strictQuery->exists()) {
            $this->applyPhraseWhere($query, $escapedRaw, $allColumns);
            $this->applyPhraseOrdering($query, $escapedRaw, $primaryColumns, $secondaryColumns);

            return [
                'query' => $query,
                'mode' => 'exact_phrase',
                'keyword' => $rawKeyword,
                'segments' => [$rawKeyword],
            ];
        }

        // 1.2 Khớp các từ theo đúng thứ tự (khoảng cách giữa các từ có thể là ký tự đặc biệt bất kỳ hoặc khoảng trắng)
        $words = $this->extractWords($lowerRaw);

        if (count($words) >= 2) {
            $orderedPattern = '%' . implode('%', array_map([$this, 'escapeLike'], $words)) . '%';
            $orderedQuery = clone $query;
            $this->applyPatternWhere($orderedQuery, $orderedPattern, $allColumns);

            if ($orderedQuery->exists()) {
                $this->applyPatternWhere($query, $orderedPattern, $allColumns);
                $this->applyPhraseOrdering($query, $escapedRaw, $primaryColumns, $secondaryColumns);

                return [
                    'query' => $query,
                    'mode' => 'exact_phrase',
                    'keyword' => $rawKeyword,
                    'segments' => [$rawKeyword],
                ];
            }
        }

        // =========================================================================
        // TẦNG 2 & 3: CỤM TỪ TÁCH NGẮN HƠN (SUB-PHRASES) VÀ CUỐI CÙNG LÀ TỪNG CHỮ
        // =========================================================================
        
        $subPhrases = $this->buildSubPhrases($words);
        $singleWords = array_values(array_unique(array_filter($words, fn ($w) => mb_strlen($w, 'UTF-8') >= 2)));

        // Nếu không có từ nào hợp lệ, fallback tìm chuỗi nguyên bản
        if (empty($subPhrases) && empty($singleWords)) {
            $this->applyPhraseWhere($query, $escapedRaw, $allColumns);
            return [
                'query' => $query,
                'mode' => 'exact_phrase',
                'keyword' => $rawKeyword,
                'segments' => [$rawKeyword],
            ];
        }

        // Áp dụng điều kiện lọc: Khớp bất kỳ từ đơn lẻ nào
        $this->applyProgressiveWhere($query, $singleWords, $allColumns);

        // Áp dụng xếp hạng theo mức độ liên quan: Cụm dài > Cụm ngắn > Nhiều từ > Ít từ
        $this->applyProgressiveOrdering($query, $subPhrases, $singleWords, $primaryColumns, $secondaryColumns);

        // Danh sách segments đại diện để hiển thị trên giao diện (lấy các cụm dài nhất đến ngắn)
        $displaySegments = array_slice(array_merge($subPhrases, $singleWords), 0, 15);

        return [
            'query' => $query,
            'mode' => 'progressive',
            'keyword' => $rawKeyword,
            'segments' => $displaySegments,
        ];
    }

    /**
     * Tách chuỗi thành các từ đơn lẻ (hỗ trợ Unicode tiếng Việt và số)
     *
     * @return array<int, string>
     */
    public function extractWords(string $text): array
    {
        preg_match_all('/[\p{L}\p{N}]+/u', $text, $matches);
        return $matches[0] ?? [];
    }

    /**
     * Tạo các cụm từ con liên tiếp từ dài xuống ngắn (N-1 từ xuống 2 từ)
     *
     * @param  array<int, string>  $words
     * @return array<int, string>
     */
    public function buildSubPhrases(array $words, int $maxPhrases = 30): array
    {
        $totalWords = count($words);
        if ($totalWords < 2) {
            return [];
        }

        $phrases = [];
        $seen = [];

        // Duyệt độ dài từ (total - 1) xuống đến 2 từ
        $maxLen = min($totalWords - 1, 6);
        for ($len = $maxLen; $len >= 2; $len--) {
            for ($i = 0; $i <= $totalWords - $len; $i++) {
                $phrase = implode(' ', array_slice($words, $i, $len));
                if (!isset($seen[$phrase])) {
                    $seen[$phrase] = true;
                    $phrases[] = $phrase;
                    if (count($phrases) >= $maxPhrases) {
                        return $phrases;
                    }
                }
            }
        }

        return $phrases;
    }

    /**
     * Giữ lại hàm chuẩn hóa tương thích ngược
     */
    public function normalizeKeyword(?string $keyword): string
    {
        $keyword = Str::lower(Str::squish((string) $keyword));
        if ($keyword === '') {
            return '';
        }

        $words = $this->extractWords($keyword);
        return trim(implode(' ', $words));
    }

    /**
     * Giữ lại hàm tương thích ngược
     *
     * @return array<int, string>
     */
    public function buildSegments(string $keyword, int $maxSegments = 15): array
    {
        $words = $this->extractWords(mb_strtolower($keyword, 'UTF-8'));
        $subPhrases = $this->buildSubPhrases($words, $maxSegments);
        $singleWords = array_values(array_unique(array_filter($words, fn ($w) => mb_strlen($w, 'UTF-8') >= 2)));

        return array_slice(array_merge($subPhrases, $singleWords), 0, $maxSegments);
    }

    /**
     * @param  array<int, string>  $columns
     */
    protected function applyPhraseWhere(Builder $query, string $escapedKeyword, array $columns): void
    {
        $pattern = '%' . $escapedKeyword . '%';
        $this->applyPatternWhere($query, $pattern, $columns);
    }

    /**
     * @param  array<int, string>  $columns
     */
    protected function applyPatternWhere(Builder $query, string $pattern, array $columns): void
    {
        $query->where(function (Builder $where) use ($columns, $pattern) {
            foreach ($columns as $index => $column) {
                $method = $index === 0 ? 'whereRaw' : 'orWhereRaw';
                $where->{$method}($this->likeExpression($column), [$pattern]);
            }
        });
    }

    /**
     * Áp dụng WHERE lọc theo các từ khóa đơn lẻ (siêu nhanh và bao hàm toàn bộ cụm từ)
     *
     * @param  array<int, string>  $singleWords
     * @param  array<int, string>  $columns
     */
    protected function applyProgressiveWhere(Builder $query, array $singleWords, array $columns): void
    {
        $query->where(function (Builder $where) use ($singleWords, $columns) {
            $isFirst = true;

            foreach ($singleWords as $word) {
                $pattern = '%' . $this->escapeLike($word) . '%';
                foreach ($columns as $column) {
                    $method = $isFirst ? 'whereRaw' : 'orWhereRaw';
                    $where->{$method}($this->likeExpression($column), [$pattern]);
                    $isFirst = false;
                }
            }
        });
    }

    /**
     * Xếp hạng khi khớp Exact Phrase
     *
     * @param  array<int, string>  $primaryColumns
     * @param  array<int, string>  $secondaryColumns
     */
    protected function applyPhraseOrdering(Builder $query, string $escapedKeyword, array $primaryColumns, array $secondaryColumns): void
    {
        [$scoreSql, $bindings] = $this->buildPhraseScoreSql($escapedKeyword, $primaryColumns, $secondaryColumns);
        $query->orderByRaw($scoreSql . ' DESC');
        foreach ($bindings as $b) {
            $query->addBinding($b, 'order');
        }
    }

    /**
     * Xếp hạng khi ở chế độ Progressive (Cụm dài > Cụm ngắn > Nhiều từ > Ít từ)
     *
     * @param  array<int, string>  $subPhrases
     * @param  array<int, string>  $singleWords
     * @param  array<int, string>  $primaryColumns
     * @param  array<int, string>  $secondaryColumns
     */
    protected function applyProgressiveOrdering(
        Builder $query,
        array $subPhrases,
        array $singleWords,
        array $primaryColumns,
        array $secondaryColumns
    ): void {
        $conditions = [];
        $bindings = [];

        // 1. Điểm cho cụm từ con: Cụm càng dài điểm càng cao
        foreach ($subPhrases as $phrase) {
            $wordCount = substr_count($phrase, ' ') + 1;
            $primaryWeight = $wordCount * 120; // 5 từ = 600đ, 4 từ = 480đ, 3 từ = 360đ, 2 từ = 240đ
            $secondaryWeight = (int) ($primaryWeight * 0.45);
            $pattern = '%' . $this->escapeLike($phrase) . '%';

            foreach ($primaryColumns as $column) {
                $conditions[] = 'CASE WHEN ' . $this->likeExpression($column) . ' THEN ' . $primaryWeight . ' ELSE 0 END';
                $bindings[] = $pattern;
            }

            foreach ($secondaryColumns as $column) {
                $conditions[] = 'CASE WHEN ' . $this->likeExpression($column) . ' THEN ' . $secondaryWeight . ' ELSE 0 END';
                $bindings[] = $pattern;
            }
        }

        // 2. Điểm cho từng từ đơn lẻ: Khớp càng nhiều từ thì tổng điểm càng cao
        foreach ($singleWords as $word) {
            $primaryWeight = 30 + min(25, mb_strlen($word, 'UTF-8') * 3);
            $secondaryWeight = 15;
            $pattern = '%' . $this->escapeLike($word) . '%';

            foreach ($primaryColumns as $column) {
                $conditions[] = 'CASE WHEN ' . $this->likeExpression($column) . ' THEN ' . $primaryWeight . ' ELSE 0 END';
                $bindings[] = $pattern;
            }

            foreach ($secondaryColumns as $column) {
                $conditions[] = 'CASE WHEN ' . $this->likeExpression($column) . ' THEN ' . $secondaryWeight . ' ELSE 0 END';
                $bindings[] = $pattern;
            }
        }

        if (!empty($conditions)) {
            $scoreSql = '(' . implode(' + ', $conditions) . ') DESC';
            $query->orderByRaw($scoreSql);
            foreach ($bindings as $b) {
                $query->addBinding($b, 'order');
            }
        }
    }

    /**
     * @param  array<int, string>  $primaryColumns
     * @param  array<int, string>  $secondaryColumns
     * @return array{0: string, 1: array<int, string>}
     */
    protected function buildPhraseScoreSql(
        string $escapedKeyword,
        array $primaryColumns,
        array $secondaryColumns
    ): array {
        $conditions = [];
        $bindings = [];

        $exact = $escapedKeyword;
        $prefix = $escapedKeyword . '%';
        $contains = '%' . $escapedKeyword . '%';

        foreach ($primaryColumns as $column) {
            $conditions[] = 'CASE WHEN ' . $this->comparisonExpression($column, '=') . ' THEN 1800 ELSE 0 END';
            $bindings[] = $exact;

            $conditions[] = 'CASE WHEN ' . $this->comparisonExpression($column, 'LIKE') . ' THEN 1250 ELSE 0 END';
            $bindings[] = $prefix;

            $conditions[] = 'CASE WHEN ' . $this->comparisonExpression($column, 'LIKE') . ' THEN 900 ELSE 0 END';
            $bindings[] = $contains;
        }

        foreach ($secondaryColumns as $column) {
            $conditions[] = 'CASE WHEN ' . $this->comparisonExpression($column, '=') . ' THEN 700 ELSE 0 END';
            $bindings[] = $exact;

            $conditions[] = 'CASE WHEN ' . $this->comparisonExpression($column, 'LIKE') . ' THEN 500 ELSE 0 END';
            $bindings[] = $prefix;

            $conditions[] = 'CASE WHEN ' . $this->comparisonExpression($column, 'LIKE') . ' THEN 320 ELSE 0 END';
            $bindings[] = $contains;
        }

        return ['(' . implode(' + ', $conditions) . ')', $bindings];
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
