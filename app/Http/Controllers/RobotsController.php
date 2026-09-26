<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class RobotsController extends Controller
{
    /**
     * Generate robots.txt
     */
    public function index(): Response
    {
        // Lấy base URL từ config sitemap, fallback sang APP_URL
        $rawBase = config('sitemap.base_url', config('app.url'));

        // Đảm bảo URL luôn có http:// hoặc https://
        if (! preg_match('/^https?:\/\//i', $rawBase)) {
            $rawBase = 'https://' . preg_replace('/^https?:?\/?\/?/i', '', $rawBase);
        }

        // Loại bỏ dấu "/" cuối URL
        $baseUrl = rtrim($rawBase, '/');

        // Sitemap chính
        $sitemapUrl = $baseUrl . '/sitemap.xml';

        $content = <<<ROBOTS
            # ============================================================
            # robots.txt - Nobi Fashion Việt Nam
            # {$baseUrl}/
            # ============================================================
            
            User-agent: *
            
            # ------------------------------------------------------------
            # ADMIN / BACK OFFICE
            # ------------------------------------------------------------
            Disallow: /admin
            Disallow: /admin/
            
            # ------------------------------------------------------------
            # AUTHENTICATION / ACCOUNT
            # ------------------------------------------------------------
            Disallow: /auth
            Disallow: /auth/
            Disallow: /profile
            Disallow: /profile/
            Disallow: /security/
            
            # ------------------------------------------------------------
            # CART / CHECKOUT / PAYMENT
            # ------------------------------------------------------------
            Disallow: /cart
            Disallow: /cart/
            Disallow: /check-out
            Disallow: /check-out/
            Disallow: /checkout/
            Disallow: /payment/
            
            # ------------------------------------------------------------
            # ORDER / CUSTOMER DATA
            # ------------------------------------------------------------
            Disallow: /order
            Disallow: /order/
            
            # ------------------------------------------------------------
            # FAVORITES / VOUCHERS
            # ------------------------------------------------------------
            Disallow: /favorites/
            Disallow: /voucher/
            
            # ------------------------------------------------------------
            # INTERNAL APIs / AJAX
            # ------------------------------------------------------------
            Disallow: /api
            Disallow: /api/
            Disallow: /chat/
            Disallow: /comments
            
            # ------------------------------------------------------------
            # FORMS / ACTION ENDPOINTS
            # ------------------------------------------------------------
            Disallow: /contact/phone
            Disallow: /product/phone-request
            
            # ------------------------------------------------------------
            # NEWSLETTER / TOKEN URLS
            # ------------------------------------------------------------
            Disallow: /newsletter/
            
            # ------------------------------------------------------------
            # INTERNAL SEARCH
            # Không để Google crawl các URL kết quả tìm kiếm nội bộ
            # ------------------------------------------------------------
            Disallow: /shop/search
            Disallow: /blog/search
            Disallow: /blog/api/
            
            # ------------------------------------------------------------
            # NON-SEO TAG ENDPOINT
            # Giữ /tags/{slug} được crawl nhưng chặn endpoint kỹ thuật
            # ------------------------------------------------------------
            Disallow: /tags/entity/
            
            # ------------------------------------------------------------
            # PUBLIC SEO CONTENT
            # ------------------------------------------------------------
            Allow: /
            Allow: /san-pham/
            Allow: /shop
            Allow: /blog
            Allow: /blog/
            Allow: /tags/
            Allow: /deals
            Allow: /gioi-thieu
            Allow: /lien-he
            Allow: /sitemap
            Allow: /sitemap.xml
            Allow: /sitemap-index.xml
            Allow: /sitemap-posts.xml
            Allow: /sitemap-products.xml
            Allow: /sitemap-categories.xml
            Allow: /sitemap-tags.xml
            Allow: /sitemap-pages.xml
            Allow: /sitemap-images.xml
            
            # ------------------------------------------------------------
            # SITEMAP
            # ------------------------------------------------------------
            Sitemap: {$sitemapUrl}

        ROBOTS;

        return response($content, 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}