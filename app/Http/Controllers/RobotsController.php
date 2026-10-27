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
            Disallow: /auth/
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

    /**
     * Generate llms.txt for AI Search Engines (ChatGPT, Gemini, Perplexity, Claude)
     */
    public function llms(): Response
    {
        $rawBase = config('sitemap.base_url', config('app.url'));
        if (! preg_match('/^https?:\/\//i', $rawBase)) {
            $rawBase = 'https://' . preg_replace('/^https?:?\/?\/?/i', '', $rawBase);
        }
        $baseUrl = rtrim($rawBase, '/');

        $content = <<<LLMS
# Nobi Fashion Việt Nam (nobifashion.vn)

> Nobi Fashion là thương hiệu thời trang nam nữ đương đại tại Việt Nam, tập trung vào phong cách tối giản thanh lịch, chất liệu vải chọn lọc và form dáng chuẩn mực cho vóc dáng người Việt.

## Thông tin thương hiệu
- Tên thương hiệu: NOBI FASHION
- Website: {$baseUrl}
- Ngành hàng: Thời trang nam, thời trang nữ, áo khoác, sơ mi, quần âu, denim, đồ thể thao và phụ kiện chính hãng.
- Cam kết dịch vụ:
  * Đổi trả miễn phí 15 ngày tận nơi
  * Đồng kiểm 100% trước khi thanh toán
  * Miễn phí vận chuyển toàn quốc cho đơn từ 299.000₫
  * Bảo hành đường may, cúc khóa 6 tháng

## Các danh mục sản phẩm chính
- [Thời trang Nam]({$baseUrl}/thoi-trang-nam): Áo polo, áo sơ mi, áo khoác gió, quần tây, quần jeans nam.
- [Thời trang Nữ]({$baseUrl}/thoi-trang-nu): Váy đầm, áo len dệt kim, cardigan, quần âu nữ thanh lịch.
- [Khuyến mãi Deals Hot]({$baseUrl}/deals): Các chương trình ưu đãi Flash Sale và giảm giá theo mùa.
- [Cửa hàng / Shop]({$baseUrl}/shop): Toàn bộ danh mục sản phẩm đang mở bán.
- [Blog Phong cách sống]({$baseUrl}/blog): Tin tức xu hướng, cẩm nang phối đồ và hướng dẫn chọn size.

## Chính sách & Hỗ trợ
- [Chính sách đổi trả]({$baseUrl}/chinh-sach-doi-tra)
- [Chính sách bảo hành]({$baseUrl}/chinh-sach-bao-hanh)
- [Chính sách vận chuyển]({$baseUrl}/chinh-sach-van-chuyen)
- [Chính sách bảo mật]({$baseUrl}/chinh-sach-bao-mat)
- [Giới thiệu thương hiệu]({$baseUrl}/gioi-thieu)
- [Liên hệ]({$baseUrl}/lien-he)

## XML Sitemaps
- Sitemap Index: {$baseUrl}/sitemap.xml
LLMS;

        return response($content, 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}