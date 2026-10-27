<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Content Security Policy (CSP) to prevent XSS
        $response->headers->set('Content-Security-Policy',
            "default-src 'self'; ".

            // Scripts
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' ".
                "https://cdnjs.cloudflare.com ".
                "https://code.jquery.com ".
                "https://unpkg.com ".
                "https://cdn.jsdelivr.net ".
                "https://images.dmca.com ".
                "https://cdn.tailwindcss.com ".
                "https://cdn.tiny.cloud ".
                "https://cdn.ckeditor.com ".
                "https://*.ckeditor.com ".
                "https://proxy-event.ckeditor.com ".
                "https://www.googletagmanager.com ".
                "https://www.google-analytics.com; ".

            // Styles
            "style-src 'self' 'unsafe-inline' ".    
                "https://fonts.googleapis.com ".
                "https://cdnjs.cloudflare.com ".
                "https://unpkg.com ".
                "https://cdn.jsdelivr.net ".
                "https://cdn.ckeditor.com ".
                "https://*.ckeditor.com ".
                "https://cdn.tiny.cloud ".
                "https://cdn.tailwindcss.com; ".

            // Fonts
            "font-src 'self' data: https://fonts.gstatic.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://cdn.ckeditor.com https:; ".

            // Images
            "img-src 'self' data: https: blob:; ".

            // Ajax/Fetch
            "connect-src 'self' ".
                "https://api.ghn.vn ".
                "https://dev-online-gateway.ghn.vn ".
                "https://online-gateway.ghn.vn ".
                "https://cdn.jsdelivr.net ".
                "https://unpkg.com ".
                "https://cdn.ckeditor.com ".
                "https://*.ckeditor.com ".
                "https://proxy-event.ckeditor.com ".
                "https://www.google-analytics.com ".
                "https://region1.google-analytics.com ".
                "https://www.googletagmanager.com ".
                "https://stats.g.doubleclick.net ".
                "https://api-merchant.payos.vn ".
                "https://pay.payos.vn ".
                "https://cdn.tiny.cloud; ".

            // Web Workers & Blob (cho CKEditor, media plugins)
            "worker-src 'self' blob:; ".
            "child-src 'self' blob:; ".

            // Frame embed (Youtube, PayOS)
            "frame-src 'self' https://pay.payos.vn https://www.youtube.com https://youtube.com; ".

            // No iframe embed
            "frame-ancestors 'none'; ".

            // Form submits
            "form-action 'self' https://pay.payos.vn; ".

            // Base URI
            "base-uri 'self';"
        );


        // Prevent MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Prevent clickjacking - nhưng không áp dụng cho redirect đến PayOS
        // Kiểm tra nếu đây là redirect đến PayOS thì không set X-Frame-Options
        $location = $response->headers->get('Location');
        if (!$location || !str_contains($location, 'pay.payos.vn')) {
        $response->headers->set('X-Frame-Options', 'DENY');
        }

        // XSS Protection
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Referrer Policy
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions Policy
        $response->headers->set('Permissions-Policy', 
            'geolocation=(), microphone=(), camera=(), payment=(), usb=(), magnetometer=(), gyroscope=(), accelerometer=()'
        );

        // Strict Transport Security (HTTPS only)
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        return $response;
    }
}