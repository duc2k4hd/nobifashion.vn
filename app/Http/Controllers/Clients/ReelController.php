<?php

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use App\Models\Reel;
use App\Services\AnonymousIdentityService;
use App\Services\ReelService;
use App\Services\ReelsFeed\ReelsFeedOrchestrator;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReelController extends Controller
{
    public function __construct(
        protected ReelService $reelService,
        protected AnonymousIdentityService $identityService,
        protected ReelsFeedOrchestrator $orchestrator
    ) {}

    /**
     * Trang xem Reels chính (/reels)
     */
    public function index(Request $request): View
    {
        $userId = auth()->id();
        $clientIp = $request->ip();
        $anonUser = $request->attributes->get('anonymous_user') ?: $this->identityService->resolveUser($request);

        $category = $request->get('category', 'all');
        $search = trim((string) $request->get('search', ''));
        $cursor = $request->get('cursor');

        // Feed mặc định ('all' và không search): Áp dụng Reels Feed Recommendation Engine
        if (($category === 'all' || empty($category)) && empty($search)) {
            $batch = $this->orchestrator->getFeedBatch($anonUser, $userId, $clientIp, 10, $cursor);
            $initialReels = $batch['items'];
            $nextCursor = $batch['nextCursor'];
            $hasMore = $batch['hasMore'];
        } else {
            $feedResult = $this->reelService->getFeed([
                'limit' => 10,
                'category' => $category,
                'search' => $search,
                'cursor' => $cursor,
            ], $clientIp, $userId, $anonUser);

            $initialReels = $feedResult['data'];
            $nextCursor = $feedResult['pagination']['next_cursor'];
            $hasMore = $feedResult['pagination']['has_more'];
        }

        $activeReel = !empty($initialReels) ? $initialReels[0] : null;

        $seoTitle = 'Nobi Reels — Video Thời Trang & Xu Hướng Phối Đồ | Nobi Fashion';
        $seoDescription = 'Khám phá video ngắn thời trang Nobi Reels. Xem phối đồ OOTD, phong cách Thu Đông, mua sắm quần áo chính hãng trực tiếp trên video.';

        return view('clients.pages.reels.index', compact(
            'initialReels',
            'nextCursor',
            'hasMore',
            'activeReel',
            'anonUser',
            'seoTitle',
            'seoDescription'
        ));
    }

    /**
     * Trang chi tiết 1 video Reel (/reels/{slug}) - Tối ưu 100% SEO & VideoObject Schema
     */
    public function show(string $slug, Request $request): View
    {
        $reel = Reel::published()->where('slug', $slug)->with(['brand', 'products.primaryImage'])->firstOrFail();

        $userId = auth()->id();
        $clientIp = $request->ip();
        $anonUser = $request->attributes->get('anonymous_user') ?: $this->identityService->resolveUser($request);

        $activeReel = $reel->toFeedArray($clientIp, $userId);
        $activeReel['isLiked'] = $this->reelService->hasLiked($reel->id, $clientIp, $userId, $anonUser);
        $activeReel['isSaved'] = $this->reelService->hasSaved($reel->id, $clientIp, $userId);

        $batch = $this->orchestrator->getFeedBatch($anonUser, $userId, $clientIp, 10);
        $subsequentReels = array_values(array_filter($batch['items'], fn($r) => $r['id'] !== $reel->id));

        $initialReels = array_merge([$activeReel], $subsequentReels);
        $nextCursor = $batch['nextCursor'];
        $hasMore = $batch['hasMore'];

        $seoTitle = ($reel->seo_title ?: $reel->title) . ' | Nobi Reels';
        $seoDescription = $reel->seo_description ?: ($reel->caption ? mb_substr(strip_tags($reel->caption), 0, 160) : 'Video ngắn thời trang Nobi Reels');

        return view('clients.pages.reels.index', compact(
            'initialReels',
            'nextCursor',
            'hasMore',
            'activeReel',
            'anonUser',
            'seoTitle',
            'seoDescription'
        ));
    }
}
