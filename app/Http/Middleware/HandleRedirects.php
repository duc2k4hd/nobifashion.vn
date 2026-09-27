<?php

namespace App\Http\Middleware;

use App\Services\RedirectService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleRedirects
{
    public function __construct(
        protected RedirectService $redirectService
    ) {}

    /**
     * Tự động kiểm tra và chuyển hướng 301/302 cho các URL cũ
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Chỉ can thiệp các request GET hoặc HEAD
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return $next($request);
        }

        // 2. Tuyệt đối không can thiệp các route hệ thống, admin, api, debug, health check
        if ($request->is('admin*', 'api*', 'up', '_debugbar*', 'sanctum/*')) {
            return $next($request);
        }

        // 3. Bỏ qua các file tĩnh và tài nguyên web (css, js, ảnh, fonts...)
        $path = $request->path();
        if (preg_match('/\.(?:css|js|jpe?g|png|gif|webp|svg|ico|woff2?|ttf|eot|otf|map|txt|xml|json)$/i', $path)) {
            return $next($request);
        }

        // 4. Tìm kiếm chuyển hướng 301/302 hợp lệ
        $redirect = $this->redirectService->resolveRequest($request);

        if ($redirect) {
            return redirect()->to($redirect['url'], $redirect['status_code'] ?? 301);
        }

        return $next($request);
    }
}
