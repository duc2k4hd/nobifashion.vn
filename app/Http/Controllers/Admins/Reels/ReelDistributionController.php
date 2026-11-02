<?php

namespace App\Http\Controllers\Admins\Reels;

use App\Http\Controllers\Controller;
use App\Models\AnonymousUser;
use App\Models\Reel;
use App\Models\ReelFeedCycle;
use App\Models\ReelRankingSnapshot;
use App\Services\ReelsAdmin\ReelsAdminService;
use App\Services\ReelsFeed\FeedSnapshotService;
use App\Services\ReelsFeed\ReelsFeedOrchestrator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReelDistributionController extends Controller
{
    protected ReelsAdminService $adminService;
    protected FeedSnapshotService $snapshotService;
    protected ReelsFeedOrchestrator $orchestrator;

    public function __construct(
        ReelsAdminService $adminService,
        FeedSnapshotService $snapshotService,
        ReelsFeedOrchestrator $orchestrator
    ) {
        $this->adminService = $adminService;
        $this->snapshotService = $snapshotService;
        $this->orchestrator = $orchestrator;
    }

    /**
     * Trung tâm phân phối video (Dual Engine, Snapshots, Cycles, Simulator)
     */
    public function index(): View
    {
        // 1. Snapshot hiện tại
        $currentSnapshot = $this->snapshotService->getCurrentSnapshot();

        // 2. Danh sách các snapshot gần đây
        $snapshots = ReelRankingSnapshot::orderByDesc('version')->take(10)->get();

        // 3. Engine A (Trending & Popularity) - Top ứng viên
        $topViewed = Reel::published()->orderByDesc('view_count')->take(5)->get();
        $topLiked = Reel::published()->orderByDesc('like_count')->take(5)->get();
        $topCommented = Reel::published()->orderByDesc('comment_count')->take(5)->get();

        // 4. Engine B (Newest & Discovery) - Top ứng viên mới nhất
        $topNewest = Reel::published()->orderByDesc('published_at')->take(5)->get();

        // 5. Thống kê vòng phân phối (Feed Cycles)
        $totalCycles = ReelFeedCycle::count();
        $activeCycles = ReelFeedCycle::where('status', 'active')->count();
        $completedCycles = ReelFeedCycle::where('status', 'completed')->count();
        $avgDelivered = round((float) ReelFeedCycle::avg('total_delivered'), 1);

        return view('admins.reels.distribution.index', compact(
            'currentSnapshot',
            'snapshots',
            'topViewed',
            'topLiked',
            'topCommented',
            'topNewest',
            'totalCycles',
            'activeCycles',
            'completedCycles',
            'avgDelivered'
        ));
    }

    /**
     * Tái tạo (Rebuild) Ranking Snapshot mới
     */
    public function rebuildSnapshot(): RedirectResponse
    {
        try {
            $newSnapshot = $this->snapshotService->generateNewSnapshot();

            $this->adminService->logAudit('rebuild_feed_snapshot', [
                'new_version' => $newSnapshot->version,
                'total_videos' => $newSnapshot->total_videos,
            ]);

            return back()->with('success', "Đã xây dựng lại Ranking Snapshot v{$newSnapshot->version} thành công với {$newSnapshot->total_videos} video đủ điều kiện.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Lỗi khi tạo snapshot: ' . $e->getMessage());
        }
    }

    /**
     * Công cụ Feed Simulator: Thử nghiệm phân phối ngẫu nhiên có kiểm soát cho một người dùng mô phỏng
     */
    public function simulate(Request $request): JsonResponse
    {
        $limit = max(5, min(50, (int) $request->input('limit', 20)));
        $seed = $request->filled('seed') ? (int) $request->input('seed') : random_int(100000, 999999);

        $snapshot = $this->snapshotService->getCurrentSnapshot();
        $trendingIds = $snapshot->trending_ids ?? [];
        $newestIds = $snapshot->newest_ids ?? [];

        // Chạy thuật toán xáo trộn theo seed tương tự FullCoverageFeedCycle
        mt_srand($seed);
        $shuffledTrending = $trendingIds;
        shuffle($shuffledTrending);

        $shuffledNewest = $newestIds;
        shuffle($shuffledNewest);

        // Phối hợp 60% trending / 40% newest
        $simulatedSequence = [];
        $seen = [];
        $tIndex = 0;
        $nIndex = 0;

        $tCount = count($shuffledTrending);
        $nCount = count($shuffledNewest);

        while ($tIndex < $tCount || $nIndex < $nCount) {
            // Lấy 3 trending
            for ($i = 0; $i < 3 && $tIndex < $tCount; $i++) {
                $id = $shuffledTrending[$tIndex++];
                if (!isset($seen[$id])) {
                    $seen[$id] = true;
                    $simulatedSequence[] = ['id' => $id, 'engine' => 'Trending (60%)'];
                }
            }
            // Lấy 2 newest
            for ($i = 0; $i < 2 && $nIndex < $nCount; $i++) {
                $id = $shuffledNewest[$nIndex++];
                if (!isset($seen[$id])) {
                    $seen[$id] = true;
                    $simulatedSequence[] = ['id' => $id, 'engine' => 'Newest (40%)'];
                }
            }
        }

        $totalSimulated = count($simulatedSequence);
        $slice = array_slice($simulatedSequence, 0, $limit);
        $sliceIds = array_column($slice, 'id');

        // Lấy thông tin video để hiển thị chi tiết
        $reels = Reel::whereIn('id', $sliceIds)->get()->keyBy('id');

        $resultItems = [];
        foreach ($slice as $pos => $item) {
            $reel = $reels->get($item['id']);
            $resultItems[] = [
                'position' => $pos + 1,
                'id' => $item['id'],
                'engine' => $item['engine'],
                'title' => $reel ? $reel->title : 'N/A',
                'view_count' => $reel ? $reel->view_count : 0,
                'like_count' => $reel ? $reel->like_count : 0,
                'comment_count' => $reel ? $reel->comment_count : 0,
                'published_at' => $reel && $reel->published_at ? $reel->published_at->format('d/m/Y H:i') : 'N/A',
            ];
        }

        // Kiểm tra tính toàn vẹn: Không trùng lặp
        $uniqueCount = count(array_unique(array_column($simulatedSequence, 'id')));
        $hasDuplicates = $uniqueCount !== $totalSimulated;

        return response()->json([
            'success' => true,
            'snapshot_version' => $snapshot->version,
            'seed_used' => $seed,
            'total_videos_in_cycle' => $totalSimulated,
            'has_duplicates' => $hasDuplicates,
            'is_full_coverage' => $totalSimulated === $snapshot->total_videos,
            'limit' => $limit,
            'items' => $resultItems,
        ]);
    }
}

