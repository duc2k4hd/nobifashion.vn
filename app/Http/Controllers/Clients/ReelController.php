<?php

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use App\Models\Reel;
use App\Services\ReelService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReelController extends Controller
{
    public function __construct(
        protected ReelService $reelService
    ) {}

    /**
     * Trang xem Reels chính (/reels)
     */
    public function index(Request $request): View
    {
        $userId = auth()->id();
        $clientIp = $request->ip();

        $feedResult = $this->reelService->getFeed([
            'limit' => 10,
            'category' => $request->get('category', 'all'),
            'search' => $request->get('search', ''),
        ], $clientIp, $userId);

        $initialReels = $feedResult['data'];
        $nextCursor = $feedResult['pagination']['next_cursor'];
        $hasMore = $feedResult['pagination']['has_more'];
        $activeReel = !empty($initialReels) ? $initialReels[0] : null;

        $seoTitle = 'Nobi Reels — Video Thời Trang & Xu Hướng Phối Đồ | Nobi Fashion';
        $seoDescription = 'Khám phá video ngắn thời trang Nobi Reels. Xem phối đồ OOTD, phong cách Thu Đông, mua sắm quần áo chính hãng trực tiếp trên video.';

        return view('clients.pages.reels.index', compact(
            'initialReels',
            'nextCursor',
            'hasMore',
            'activeReel',
            'seoTitle',
            'seoDescription'
        ));
    }

    /**
     * Trang chi tiết 1 video Reel (/reels/{slug}) - Tối ưu 100% SEO & VideoObject Schema
     */
    public function show(string $slug, Request $request): View
    {
        $reel = Reel::published()
            ->where('slug', $slug)
            ->with(['products.primaryImage'])
            ->first();

        if (!$reel) {
            abort(404, 'Không tìm thấy video Reel!');
        }

        $userId = auth()->id();
        $clientIp = $request->ip();

        // Ghi nhận view
        $this->reelService->recordView($reel, $request);

        // Lấy thêm 9 video tiếp theo để tiếp tục cuộn mượt mà
        $feedResult = $this->reelService->getFeed([
            'limit' => 9,
            'cursor' => $reel->id,
        ], $clientIp, $userId);

        $activeFeedItem = $reel->toFeedArray($clientIp, $userId);
        $initialReels = array_merge([$activeFeedItem], $feedResult['data']);
        $nextCursor = $feedResult['pagination']['next_cursor'];
        $hasMore = $feedResult['pagination']['has_more'];

        $seoTitle = ($reel->seo_title ?: $reel->title) . ' | Nobi Fashion Reels';
        $seoDescription = $reel->seo_description ?: ($reel->caption ?: 'Xem video thời trang ' . $reel->title . ' tại Nobi Fashion Reels.');

        return view('clients.pages.reels.index', [
            'initialReels' => $initialReels,
            'nextCursor' => $nextCursor,
            'hasMore' => $hasMore,
            'activeReel' => $activeFeedItem,
            'reelModel' => $reel,
            'seoTitle' => $seoTitle,
            'seoDescription' => $seoDescription,
        ]);
    }
}

