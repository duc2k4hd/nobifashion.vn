<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminOnly
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        // Không đăng nhập
        if (!$user) {
            return $this->logoutAndRedirect($request);
        }

        // Không phải admin
        if (!$user->isAdmin()) {
            return $this->logoutAndRedirect($request);
        }

        // Tài khoản không active
        if (!$user->isActive()) {
            return $this->logoutAndRedirect($request);
        }

        return $next($request);
    }

    /**
     * Xử lý logout + trả JSON hoặc redirect tùy request
     */
    private function logoutAndRedirect(Request $request)
    {
        auth('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return redirect()->route('admin.login')->with('error', 'Bạn không có quyền truy cập.');
    }
}
