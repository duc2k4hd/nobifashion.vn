<?php

namespace App\Services\ReelsFeed;

use App\Models\AnonymousUser;
use App\Models\Reel;
use App\Models\ReelFeedCycle;
use App\Models\ReelLike;
use App\Models\ReelSave;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ReelsFeedOrchestrator
{
    protected FeedSnapshotService $snapshotService;

    public function __construct(FeedSnapshotService $snapshotService)
    {
        $this->snapshotService = $snapshotService;
    }

    /**
     * Lấy batch video tiếp theo cho người dùng theo thuật toán Dual-Engine & Full Video Coverage
     *
     * @param AnonymousUser $anonUser Người dùng ẩn danh
     * @param int|null $userId ID tài khoản nếu đã đăng nhập
     * @param string|null $clientIp Địa chỉ IP
     * @param int $limit Số lượng video mỗi trang (mặc định 10)
     * @param string|null $cursor Cursor nhận từ request trước
     * @return array Kết quả gồm items, nextCursor, hasMore, cycleId, snapshotVersion
     */
    public function getFeedBatch(
        AnonymousUser $anonUser,
        ?int $userId = null,
        ?string $clientIp = null,
        int $limit = 10,
        ?string $cursor = null
    ): array {
        $limit = max(1, min($limit, 20));

        // 1. Phân giải hoặc lấy Cycle hiện tại của User
        $cycle = $this->resolveUserCycle($anonUser, $userId, $cursor);

        $sequence = $cycle->sequence ?? [];
        $totalVideos = count($sequence);

        if ($totalVideos === 0) {
            return [
                'items' => [],
                'nextCursor' => null,
                'hasMore' => false,
                'cycleId' => $cycle->id,
                'snapshotVersion' => $cycle->snapshot_version,
                'totalVideos' => 0,
                'currentIndex' => 0,
            ];
        }

        // Nếu có cursor -> Phân trang tiếp tục từ con trỏ của cursor
        // Nếu không có cursor (load trang đầu / reload) -> Tiếp tục chính xác từ tiến độ đã xem (current_index)
        if ($cursor) {
            $cursorData = $this->parseCursor($cursor);
            $currentIndex = (isset($cursorData['index'])) ? (int) $cursorData['index'] : (int) $cycle->current_index;
        } else {
            $currentIndex = (int) $cycle->current_index;
        }

        // 2. Lấy danh sách ID cho batch này từ sequence
        $targetIds = array_slice($sequence, $currentIndex, $limit);
        $fetchedCount = count($targetIds);

        if ($fetchedCount === 0) {
            // Đã xem hết vòng phân phối -> Hoàn thành chu kỳ cũ và tự động sinh chu kỳ mới (Seed mới, Full Coverage)
            $this->completeCycle($cycle);
            $cycle = $this->createNewCycle($anonUser, $userId);
            $sequence = $cycle->sequence ?? [];
            $totalVideos = count($sequence);
            $currentIndex = 0;
            $targetIds = array_slice($sequence, $currentIndex, $limit);
            $fetchedCount = count($targetIds);
        }

        // 3. Truy vấn nạp chi tiết các Reel từ Database (Indexed Primary Key lookup)
        $reels = $this->fetchReelsByIds($targetIds, $clientIp, $userId, $anonUser);

        // Cập nhật vị trí con trỏ của cycle
        $newIndex = $currentIndex + $fetchedCount;
        $cycle->current_index = max((int) $cycle->current_index, $newIndex);
        $cycle->total_delivered = max((int) $cycle->total_delivered, $newIndex);
        $hasMore = $newIndex < $totalVideos;

        if (!$hasMore) {
            $cycle->status = 'completed';
            $cycle->completed_at = now();
        }
        $cycle->save();

        // 4. Tạo signed cursor an toàn cho lần gọi tiếp theo
        $nextCursor = $hasMore ? $this->generateCursorForIndex($cycle, $newIndex) : null;

        return [
            'items' => $reels,
            'nextCursor' => $nextCursor,
            'hasMore' => $hasMore,
            'cycleId' => $cycle->id,
            'snapshotVersion' => $cycle->snapshot_version,
            'totalVideos' => $totalVideos,
            'currentIndex' => $newIndex,
        ];
    }

    /**
     * Phân giải FeedCycle của người dùng:
     * - Nếu có cursor hợp lệ -> Tiếp tục cycle đó
     * - Nếu chưa có -> Tìm cycle 'active' chưa xem xong
     * - Nếu không có hoặc đã xem hết -> Tạo cycle mới với sequence ngẫu nhiên riêng biệt
     */
    public function resolveUserCycle(AnonymousUser $anonUser, ?int $userId = null, ?string $cursor = null): ReelFeedCycle
    {
        // 1. Thử giải mã cursor nếu có
        if ($cursor) {
            $cursorData = $this->parseCursor($cursor);
            if ($cursorData && isset($cursorData['cycle_id'])) {
                $cycle = ReelFeedCycle::find($cursorData['cycle_id']);
                if ($cycle && $cycle->anonymous_user_id === $anonUser->id && $cycle->status === 'active') {
                    return $cycle;
                }
            }
        }

        // 2. Tìm Cycle active hiện tại của User trong Database
        $activeCycle = ReelFeedCycle::active()
            ->where('anonymous_user_id', $anonUser->id)
            ->latest('id')
            ->first();

        if ($activeCycle) {
            $seq = $activeCycle->sequence ?? [];
            if ($activeCycle->current_index < count($seq)) {
                // Tiếp tục vòng chưa xem xong
                return $activeCycle;
            } else {
                // Đã xem hết vòng -> Hoàn thành
                $this->completeCycle($activeCycle);
            }
        }

        // 3. Tạo FeedCycle hoàn toàn mới cho User (Vòng xem mới)
        return $this->createNewCycle($anonUser, $userId);
    }

    /**
     * Tạo một FeedCycle mới: Sinh chuỗi hoán vị toàn diện 100% video (Full Coverage)
     */
    public function createNewCycle(AnonymousUser $anonUser, ?int $userId = null): ReelFeedCycle
    {
        $snapshot = $this->snapshotService->getCurrentSnapshot();

        // Sinh seed ngẫu nhiên duy nhất cho user trong cycle này
        $seed = abs((int) crc32($anonUser->id . '_' . microtime(true) . '_' . random_int(10000, 999999)));

        // Xây dựng chuỗi phân phối kết hợp 2 Engine không trùng lặp và phủ toàn bộ video
        $sequence = $this->buildFullCoverageSequence(
            $snapshot->trending_ids ?? [],
            $snapshot->newest_ids ?? [],
            $seed
        );

        return ReelFeedCycle::create([
            'anonymous_user_id' => $anonUser->id,
            'user_id' => $userId,
            'snapshot_version' => $snapshot->version,
            'random_seed' => $seed,
            'current_index' => 0,
            'total_delivered' => 0,
            'total_videos' => count($sequence),
            'sequence' => $sequence,
            'status' => 'active',
            'started_at' => now(),
        ]);
    }

    /**
     * THUẬT TOÁN KỐT LÕI: Chunk-based Seeded Shuffle & Interleaved Permutation
     * - Kết hợp 60% Trending (Engine A) và 40% Newest (Engine B)
     * - Bảo đảm 100% KHÔNG TRÙNG LẶP video
     * - Bảo đảm 100% TOÀN BỘ VIDEO HỢP LỆ ĐỀU XUẤT HIỆN (Full Video Coverage)
     */
    public function buildFullCoverageSequence(array $trendingIds, array $newestIds, int $seed): array
    {
        // Tập hợp tất cả ID hợp lệ
        $allValidMap = [];
        foreach ($trendingIds as $id) $allValidMap[$id] = true;
        foreach ($newestIds as $id) $allValidMap[$id] = true;
        $totalExpected = count($allValidMap);

        if ($totalExpected === 0) {
            return [];
        }

        // 1. Áp dụng Bounded Chunk-based Seeded Shuffle cho từng Engine
        // Giúp xáo trộn trong nhóm video tương đồng mà không làm mất hoàn toàn trật tự xếp hạng
        $shuffledTrending = $this->chunkedSeededShuffle($trendingIds, $seed, 5);
        $shuffledNewest = $this->chunkedSeededShuffle($newestIds, $seed + 9999, 5);

        $sequence = [];
        $used = [];

        $tIdx = 0;
        $nIdx = 0;
        $tCount = count($shuffledTrending);
        $nCount = count($shuffledNewest);

        // 2. Trộn xen kẽ theo tỷ lệ mục tiêu 60% Trending / 40% Newest
        // Chu kỳ mỗi block 5 video: 3 Trending + 2 Newest
        while ($tIdx < $tCount || $nIdx < $nCount) {
            // Lấy 3 từ Trending
            $takenTrending = 0;
            while ($takenTrending < 3 && $tIdx < $tCount) {
                $id = $shuffledTrending[$tIdx++];
                if (!isset($used[$id])) {
                    $used[$id] = true;
                    $sequence[] = $id;
                    $takenTrending++;
                }
            }

            // Lấy 2 từ Newest
            $takenNewest = 0;
            while ($takenNewest < 2 && $nIdx < $nCount) {
                $id = $shuffledNewest[$nIdx++];
                if (!isset($used[$id])) {
                    $used[$id] = true;
                    $sequence[] = $id;
                    $takenNewest++;
                }
            }

            // Nếu cả 2 đều không lấy thêm được (do hết ứng viên chưa dùng) -> Thoát
            if ($takenTrending === 0 && $takenNewest === 0 && ($tIdx >= $tCount && $nIdx >= $nCount)) {
                break;
            }
        }

        // 3. ĐẢM BẢO FULL COVERAGE: Bổ sung bất kỳ video nào còn sót lại
        $remaining = [];
        foreach ($allValidMap as $id => $val) {
            if (!isset($used[$id])) {
                $remaining[] = $id;
            }
        }

        if (!empty($remaining)) {
            // Xáo trộn nhẹ phần còn lại theo seed
            $shuffledRemaining = $this->seededShuffle($remaining, $seed + 7777);
            foreach ($shuffledRemaining as $id) {
                $sequence[] = $id;
            }
        }

        return $sequence;
    }

    /**
     * Bounded Chunk Shuffle: Chia mảng thành từng chunk nhỏ (ví dụ 4-6 phần tử)
     * và hoán vị ngẫu nhiên theo seed trong từng chunk đó
     */
    protected function chunkedSeededShuffle(array $items, int $seed, int $chunkSize = 5): array
    {
        $result = [];
        $chunks = array_chunk($items, $chunkSize);

        foreach ($chunks as $idx => $chunk) {
            $chunkSeed = abs((int) ($seed + ($idx * 31)));
            $shuffled = $this->seededShuffle($chunk, $chunkSeed);
            foreach ($shuffled as $item) {
                $result[] = $item;
            }
        }

        return $result;
    }

    /**
     * Deterministic Fisher-Yates Shuffle với Linear Congruential Generator (PRNG)
     * Đảm bảo cùng seed -> cùng kết quả; khác seed -> khác kết quả
     */
    public function seededShuffle(array $items, int $seed): array
    {
        $count = count($items);
        if ($count <= 1) {
            return $items;
        }

        $state = $seed;
        $shuffled = array_values($items);

        for ($i = $count - 1; $i > 0; $i--) {
            // LCG PRNG: X_{n+1} = (a * X_n + c) mod m
            $state = (1103515245 * $state + 12345) & 0x7FFFFFFF;
            $j = $state % ($i + 1);

            // Swap
            $tmp = $shuffled[$i];
            $shuffled[$i] = $shuffled[$j];
            $shuffled[$j] = $tmp;
        }

        return $shuffled;
    }

    /**
     * Truy vấn nạp chi tiết các Reel theo danh sách IDs và giữ nguyên thứ tự
     */
    protected function fetchReelsByIds(array $ids, ?string $clientIp, ?int $userId, AnonymousUser $anonUser): array
    {
        if (empty($ids)) return [];

        $reels = Reel::query()
            ->published()
            ->whereIn('id', $ids)
            ->with([
                'brand:id,name,slug,logo',
                'products' => function ($q) {
                    $q->select(['products.id', 'products.name', 'products.slug', 'products.price', 'products.sale_price', 'products.short_description'])
                      ->with(['primaryImage:id,product_id,url,alt,title']);
                }
            ])
            ->get()
            ->keyBy('id');

        // Lấy danh sách ID đã Like & Saved của User trong batch này
        $validIds = $reels->keys()->all();
        $likedIds = [];
        $savedIds = [];

        if (!empty($validIds)) {
            $likedQuery = ReelLike::whereIn('reel_id', $validIds);
            if ($userId && $anonUser) {
                $likedQuery->where(function ($q) use ($userId, $anonUser) {
                    $q->where('anonymous_user_id', $anonUser->id)
                      ->orWhere('user_id', $userId);
                });
            } elseif ($anonUser) {
                $likedQuery->where('anonymous_user_id', $anonUser->id);
            } elseif ($userId) {
                $likedQuery->where('user_id', $userId);
            } elseif ($clientIp) {
                $likedQuery->where('ip_address', $clientIp);
            }
            $likedIds = $likedQuery->pluck('reel_id')->flip()->all();

            $saveQuery = ReelSave::whereIn('reel_id', $validIds);
            if ($userId) {
                $savedIds = $saveQuery->where('user_id', $userId)->pluck('reel_id')->flip()->all();
            } elseif ($clientIp) {
                $savedIds = $saveQuery->where('ip_address', $clientIp)->pluck('reel_id')->flip()->all();
            }
        }

        // Giữ nguyên đúng thứ tự phân phối của $ids
        $result = [];
        foreach ($ids as $id) {
            if ($reels->has($id)) {
                $reel = $reels->get($id);
                $feedItem = $reel->toFeedArray($clientIp, $userId);
                $feedItem['isLiked'] = isset($likedIds[$id]);
                $feedItem['isSaved'] = isset($savedIds[$id]);
                $result[] = $feedItem;
            }
        }

        return $result;
    }

    protected function completeCycle(ReelFeedCycle $cycle): void
    {
        $cycle->status = 'completed';
        $cycle->completed_at = now();
        $cycle->save();
    }

    protected function generateCursor(ReelFeedCycle $cycle): string
    {
        return $this->generateCursorForIndex($cycle, (int) $cycle->current_index);
    }

    protected function generateCursorForIndex(ReelFeedCycle $cycle, int $index): string
    {
        $payload = [
            'cycle_id' => $cycle->id,
            'index' => $index,
            'v' => $cycle->snapshot_version,
            't' => time(),
        ];
        $json = json_encode($payload);
        $sig = substr(hash_hmac('sha256', $json, config('app.key', 'nobi-reels-secret')), 0, 16);
        return base64_encode($json . '.' . $sig);
    }

    protected function parseCursor(string $cursor): ?array
    {
        try {
            $decoded = base64_decode($cursor, true);
            if (!$decoded || !str_contains($decoded, '.')) return null;
            [$json, $sig] = explode('.', $decoded, 2);
            $expectedSig = substr(hash_hmac('sha256', $json, config('app.key', 'nobi-reels-secret')), 0, 16);
            if (!hash_equals($expectedSig, $sig)) return null;
            return json_decode($json, true);
        } catch (\Throwable $e) {
            return null;
        }
    }
}

