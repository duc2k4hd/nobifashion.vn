<?php

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Profile;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthorController extends Controller
{
    /**
     * Sinh URL trang tác giả chuẩn theo nickname trong bảng profiles:
     * - Chỉ tác giả thuộc role admin hoặc staff VÀ có nickname trong profiles mới có URL.
     * - Không có nickname -> Trả về null (không sinh link tác giả).
     */
    public static function getAuthorUrl(?Account $author = null): ?string
    {
        if (! $author) {
            return null;
        }

        // Bắt buộc phải là admin hoặc staff
        if (! in_array($author->role, [Account::ROLE_ADMIN, Account::ROLE_STAFF])) {
            return null;
        }

        // Bắt buộc phải có nickname trong profiles
        $nickname = trim((string) ($author->profile?->nickname ?? ''));
        if ($nickname === '') {
            return null;
        }

        return route('client.author.show', Str::slug($nickname));
    }

    /**
     * Truy cập /author -> 301 Redirect về trang của tác giả hợp lệ đầu tiên có nickname
     */
    public function index(): RedirectResponse|Response
    {
        $firstAuthor = Account::whereIn('role', [Account::ROLE_ADMIN, Account::ROLE_STAFF])
            ->whereHas('profile', function ($q) {
                $q->whereNotNull('nickname')->where('nickname', '!=', '');
            })
            ->with('profile')
            ->orderByRaw("FIELD(role, 'admin', 'staff')")
            ->first();

        if (! $firstAuthor || empty($firstAuthor->profile?->nickname)) {
            return response()->view('clients.pages.errors.404', ['error' => 'Tác giả không tồn tại!'], 404);
        }

        return redirect()->route('client.author.show', Str::slug($firstAuthor->profile->nickname), 301);
    }

    /**
     * Hiển thị trang tác giả:
     * - Chỉ tồn tại cho admin và staff CÓ NICKNAME trong bảng profiles.
     * - Slug trên URL bắt buộc phải khớp với nickname trong profiles (so khớp slug hoặc chính xác).
     * - Không có nickname hoặc slug không khớp -> Trả về trang lỗi 404 có sẵn.
     */
    public function show(Request $request, string $slug): View|Response
    {
        $cleanSlug = trim($slug);
        $decodedSlug = urldecode($cleanSlug);

        // 1. Chỉ tìm trong danh sách tài khoản admin/staff BẮT BUỘC CÓ NICKNAME trong profiles
        $candidateAuthors = Account::whereIn('role', [Account::ROLE_ADMIN, Account::ROLE_STAFF])
            ->whereHas('profile', function ($q) {
                $q->whereNotNull('nickname')->where('nickname', '!=', '');
            })
            ->with(['profile'])
            ->get();

        // 2. So khớp slug với nickname trong profiles (không hardcode bất kỳ vai trò hay tên nào)
        $author = $candidateAuthors->first(function ($acc) use ($cleanSlug, $decodedSlug) {
            $nick = trim((string) ($acc->profile?->nickname ?? ''));
            if ($nick === '') {
                return false;
            }

            return Str::slug($nick) === $cleanSlug || $nick === $cleanSlug || $nick === $decodedSlug;
        });

        // Nếu không tìm thấy tác giả nào có nickname khớp -> Trả về trang 404 có sẵn
        if (! $author) {
            return response()->view('clients.pages.errors.404', ['error' => 'Tác giả không tồn tại!'], 404);
        }

        $profile = $author->profile;
        $fullName = $profile?->full_name ?? $author->displayName() ?? 'Tác giả Nobi';
        $nickname = $profile->nickname;
        $location = $profile?->location ?? 'Hải Phòng, Việt Nam';
        $isAdmin = ($author->role === Account::ROLE_ADMIN);

        // 2. Avatar và Cover
        $avatarUrl = null;
        if (! empty($profile?->avatar)) {
            $rawAvatar = ltrim($profile->avatar, '/');
            if (str_starts_with($rawAvatar, 'http://') || str_starts_with($rawAvatar, 'https://')) {
                $avatarUrl = $rawAvatar;
            } else {
                $avatarUrl = asset('clients/assets/img/' . (str_starts_with($rawAvatar, 'users/') ? $rawAvatar : 'users/' . $rawAvatar));
            }
        }
        if (! $avatarUrl) {
            $avatarUrl = 'https://ui-avatars.com/api/?name='.urlencode($fullName).'&background=0F172A&color=ffffff&bold=true&size=200';
        }

        $coverUrl = null;
        if (! empty($profile?->sub_avatar)) {
            $rawCover = ltrim($profile->sub_avatar, '/');
            $coverUrl = (str_starts_with($rawCover, 'http://') || str_starts_with($rawCover, 'https://'))
                ? $rawCover
                : asset('clients/assets/img/' . (str_starts_with($rawCover, 'users/') ? $rawCover : 'users/' . $rawCover));
        }

        // 3. Phân biệt Content & Thông tin chuyên môn giữa Admin và Staff (Chuẩn E-E-A-T)
        if ($isAdmin) {
            $roleBadge = '👑 Nhà sáng lập & Tổng biên tập';
            $roleTitle = 'Founder & Editor-in-Chief';
            $bio = 'Nhà sáng lập kiêm Tổng biên tập tại Nobi Fashion. Với hơn 6 năm kinh nghiệm nghiên cứu xu hướng và văn hóa mặc của giới trẻ, anh định hình phong cách thời trang đương đại kết hợp giữa tính tối giản (Minimalism) và tinh thần đường phố (Streetwear) phóng khoáng. Tại Nobi Fashion, anh chịu trách nhiệm kiểm duyệt cao nhất cho mọi nội dung, cam kết mang đến những chia sẻ chân thực, hữu ích và truyền cảm hứng mặc đẹp bền vững cho bạn đọc.';
            $quote = 'Thời trang không đơn thuần là những gì bạn khoác lên người, mà là cách bạn định hình phong cách sống và khẳng định cá tính riêng mà không cần cất lời.';
            $responsibilities = [
                'Định hướng chiến lược nội dung & xu hướng phong cách',
                'Kiểm duyệt 100% bài viết theo tiêu chuẩn E-E-A-T',
                'Phát triển phong cách thời trang tối giản & bền vững',
                'Truyền cảm hứng mặc đẹp và tự tin cho thế hệ trẻ',
            ];
            $expertiseTags = [
                'Tổng biên tập', 'Chiến lược phong cách', 'Minimalism', 'Streetwear Icon', 'Triết lý thời trang', 'Thẩm định chất liệu',
            ];
        } else {
            $roleBadge = '✨ Biên tập viên & Senior Stylist';
            $roleTitle = 'Fashion Stylist & Editorial Specialist';
            $bio = 'Chuyên viên Định hình phong cách và Biên tập viên nội dung thời trang tại Nobi Fashion. Tốt nghiệp chuyên ngành thiết kế & stylist với niềm đam mê bất tận về nghệ thuật phối đồ (Mix & Match). Trực tiếp trải nghiệm chất liệu, săn đón các trào lưu mới nhất của Gen Z và thời trang công sở trẻ, mang lại những cẩm nang phối đồ dễ ứng dụng, giúp bạn đọc tự tin tỏa sáng trong mọi hoàn cảnh.';
            $quote = 'Đừng chạy theo xu hướng một cách mù quáng, hãy để xu hướng tôn vinh nét riêng và sự tự tin thoải mái nhất trong con người bạn.';
            $responsibilities = [
                'Nghiên cứu & Hướng dẫn phối đồ thực chiến hàng ngày',
                'Trực tiếp thử nghiệm & Đánh giá form dáng, chất liệu',
                'Cập nhật xu hướng thời trang thịnh hành Gen Z & Smart Casual',
                'Giải đáp thắc mắc & Tư vấn phối đồ riêng cho bạn đọc',
            ];
            $expertiseTags = [
                'Senior Stylist', 'Tips Phối Đồ', 'Trải nghiệm thực tế', 'Streetwear Gen Z', 'Smart Casual', 'Capsule Wardrobe',
            ];
        }

        // 4. Danh sách ID tác giả liên quan để lấy bài viết
        $authorIds = [$author->id];
        if ($isAdmin || $author->id == 101) {
            $authorIds[] = 1; // Bao gồm các bài viết khởi tạo ban đầu
        }

        $currentPage = max(1, (int) $request->get('page', 1));

        // 5. Cache & Tối ưu dữ liệu Showcase, Top view và Thống kê (tránh quét lại DB nhiều lần)
        $cacheKey = "author_showcase_v1_{$author->id}";
        $showcaseData = Cache::remember($cacheKey, 600, function () use ($authorIds) {
            // Gom nhóm thống kê categories + tổng bài + tổng view bằng 1 query duy nhất
            $catStats = Post::published()
                ->whereIn('created_by', $authorIds)
                ->selectRaw('category_id, COUNT(*) as post_count, SUM(views) as total_views')
                ->groupBy('category_id')
                ->orderByDesc('post_count')
                ->get();

            $totalPosts = (int) $catStats->sum('post_count');
            $totalViews = (int) $catStats->sum('total_views');
            $catCounts = $catStats->pluck('post_count', 'category_id');
            $catIds = $catStats->pluck('category_id')->filter()->values()->all();

            $categories = PostCategory::whereIn('id', $catIds)
                ->select('id', 'name', 'slug')
                ->get()
                ->keyBy('id');

            // Top 8 bài viết xem nhiều nhất của tác giả
            $topViewPosts = Post::published()
                ->whereIn('created_by', $authorIds)
                ->select('id', 'title', 'slug', 'thumbnail', 'thumbnail_alt_text', 'excerpt', 'category_id', 'published_at', 'views')
                ->with(['category:id,name,slug'])
                ->orderByDesc('views')
                ->take(8)
                ->get();

            // Chuyên đề theo danh mục (tối đa 4 danh mục tiêu biểu nhất, tái sử dụng bài trong topViewPosts nếu có)
            $categoryShowcase = [];
            foreach ($categories->take(4) as $catId => $cat) {
                $matchedInTop = $topViewPosts->where('category_id', $catId);
                if ($matchedInTop->count() >= min(6, $catCounts[$catId] ?? 0)) {
                    $catPosts = $matchedInTop->take(6)->values();
                } else {
                    $catPosts = Post::published()
                        ->whereIn('created_by', $authorIds)
                        ->where('category_id', $catId)
                        ->select('id', 'title', 'slug', 'thumbnail', 'thumbnail_alt_text', 'excerpt', 'category_id', 'published_at', 'views')
                        ->with(['category:id,name,slug'])
                        ->orderByDesc('views')
                        ->take(6)
                        ->get();
                }

                if ($catPosts->isNotEmpty()) {
                    $categoryShowcase[] = [
                        'category' => $cat,
                        'posts' => $catPosts,
                        'total' => $catCounts[$catId] ?? $catPosts->count(),
                    ];
                }
            }

            return [
                'totalPosts' => $totalPosts,
                'totalViews' => $totalViews,
                'topViewPosts' => $topViewPosts,
                'categoryShowcase' => $categoryShowcase,
            ];
        });

        $totalPosts = $showcaseData['totalPosts'] ?? 0;
        $totalViews = $showcaseData['totalViews'] ?? 0;
        // Ở trang 1 hiển thị đầy đủ, ở trang > 1 chỉ giữ lại dữ liệu nếu cần hoặc hiển thị danh sách bài viết
        $topViewPosts = ($currentPage === 1) ? ($showcaseData['topViewPosts'] ?? collect()) : collect();
        $categoryShowcase = ($currentPage === 1) ? ($showcaseData['categoryShowcase'] ?? []) : [];

        // 6. Phân trang 12 bài viết (chỉ query đúng 12 bài cho trang hiện tại, tái sử dụng totalPosts)
        $postsItems = Post::published()
            ->whereIn('created_by', $authorIds)
            ->select('id', 'title', 'slug', 'thumbnail', 'thumbnail_alt_text', 'excerpt', 'category_id', 'published_at', 'views')
            ->with(['category:id,name,slug'])
            ->orderByDesc('published_at')
            ->forPage($currentPage, 12)
            ->get();

        $posts = new LengthAwarePaginator(
            $postsItems,
            $totalPosts,
            12,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // 7. Cài đặt SEO & Schema
        $settings = \Illuminate\Support\Facades\View::shared('settings') ?? Setting::first();
        $siteName = $settings->site_name ?? 'Nobi Fashion';
        $siteUrl = config('app.url', url('/'));

        $seoTitle = "Tác giả {$fullName} ({$roleTitle}) - {$siteName}";
        $seoDescription = "Hồ sơ tác giả {$fullName} - {$roleBadge} tại {$siteName}. Tuyển tập bài viết đọc nhiều nhất, cẩm nang phối đồ xu hướng và góc nhìn phong cách thời trang.";
        $canonicalUrl = route('client.author.show', $cleanSlug);

        // Schema ProfilePage & Person
        $schemaData = [
            '@context' => 'https://schema.org',
            '@type' => 'ProfilePage',
            'mainEntity' => [
                '@type' => 'Person',
                'name' => $fullName,
                'alternateName' => $nickname,
                'url' => $canonicalUrl,
                'image' => $avatarUrl,
                'description' => $bio,
                'jobTitle' => $roleTitle,
                'worksFor' => [
                    '@type' => 'Organization',
                    'name' => $siteName,
                    'url' => $siteUrl,
                ],
                'sameAs' => [
                    $siteUrl,
                ],
            ],
        ];

        return view('clients.author.index', compact(
            'author',
            'profile',
            'fullName',
            'nickname',
            'location',
            'avatarUrl',
            'coverUrl',
            'isAdmin',
            'roleBadge',
            'roleTitle',
            'bio',
            'quote',
            'responsibilities',
            'expertiseTags',
            'topViewPosts',
            'categoryShowcase',
            'posts',
            'totalPosts',
            'totalViews',
            'cleanSlug',
            'seoTitle',
            'seoDescription',
            'canonicalUrl',
            'schemaData',
            'settings'
        ));
    }
}
