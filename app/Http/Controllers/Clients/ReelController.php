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
     * Trang SEO riêng cho video Reel (/reel/{slug}) - Tối ưu 100% SEO, Open Graph & VideoObject Schema
     */
    public function show(string $slug, Request $request)
    {
        $reel = Reel::published()
            ->where('slug', $slug)
            ->with(['brand', 'products.primaryImage', 'products.brand'])
            ->first();

        // Fallback: nếu người dùng truy cập bằng ID số thay vì slug, chuyển hướng 301 chuẩn SEO
        if (!$reel && is_numeric($slug)) {
            $reelById = Reel::published()->where('id', (int) $slug)->first();
            if ($reelById && $reelById->slug) {
                return redirect()->route('client.reel.show', ['slug' => $reelById->slug], 301);
            }
        }

        if (!$reel) {
            abort(404, 'Video không tồn tại hoặc đã bị gỡ');
        }

        $userId = auth()->id();
        $clientIp = $request->ip();
        $anonUser = $request->attributes->get('anonymous_user') ?: $this->identityService->resolveUser($request);

        // Ghi nhận lượt xem video
        $this->reelService->recordView($reel, $request);

        $feedReel = $reel->toFeedArray($clientIp, $userId);

        // Lấy danh sách video tiếp theo để người xem có thể cuộn lướt tiếp mượt mà như TikTok/Facebook Reels
        $batch = $this->orchestrator->getFeedBatch($anonUser, $userId, $clientIp, 10, null);
        $otherReels = array_values(array_filter($batch['items'], fn($item) => ($item['id'] ?? null) != $reel->id));
        $initialReels = array_merge([$feedReel], $otherReels);
        $nextCursor = $batch['nextCursor'];
        $hasMore = $batch['hasMore'];
        $activeReel = $feedReel;

        $seoTitle = ($reel->seo_title ?: $reel->title) . ' | Nobi Reels';
        $seoDescription = $reel->seo_description ?: ($reel->caption ? mb_substr(strip_tags($reel->caption), 0, 160) : 'Xem video ngắn thời trang ' . $reel->title . ' tại Nobi Reels.');
        $canonicalUrl = url('/reel/' . $reel->slug);

        return view('clients.pages.reels.index', compact(
            'reel',
            'initialReels',
            'nextCursor',
            'hasMore',
            'activeReel',
            'anonUser',
            'seoTitle',
            'seoDescription',
            'canonicalUrl'
        ));
    }
}
