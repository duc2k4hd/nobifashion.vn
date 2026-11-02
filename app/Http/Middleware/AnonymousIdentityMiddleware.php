<?php

namespace App\Http\Middleware;

use App\Services\AnonymousIdentityService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AnonymousIdentityMiddleware
{
    public function __construct(
        protected AnonymousIdentityService $identityService
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $newRawToken = null;
        $anonUser = $this->identityService->resolveUser($request, $newRawToken);

        // Gắn vào request attributes để Controller/Service dễ dàng truy cập
        $request->attributes->set('anonymous_user', $anonUser);

        $response = $next($request);

        // Nếu mới sinh token, gắn Cookie vào Response
        if ($newRawToken && method_exists($response, 'withCookie')) {
            $cookie = $this->identityService->makeIdentityCookie($newRawToken, $request);
            $response->withCookie($cookie);
        }

        return $response;
    }
}

