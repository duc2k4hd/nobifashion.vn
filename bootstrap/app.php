<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminOnly::class,
        ]);

        // Redirect guests to admin login only for admin routes
        $middleware->redirectGuestsTo(function ($request) {
            Log::info('=== REDIRECT GUESTS DEBUG ===');
            Log::info('Request path: ' . $request->path());
            Log::info('Request full URL: ' . $request->fullUrl());
            Log::info('Request is admin/*: ' . ($request->is('admin/*') ? 'true' : 'false'));
            Log::info('Request is admin/login: ' . ($request->is('admin/login') ? 'true' : 'false'));
            Log::info('Auth check: ' . (auth('web')->check() ? 'true' : 'false'));
            
            // Don't redirect if it's the login page
            if ($request->is('admin/login')) {
                Log::info('Skipping redirect for admin/login');
                return null; // Return null to allow access
            }
            
            if ($request->is('admin/*')) {
                Log::info('Redirecting to admin.login');
                return route('admin.login');
            }
            Log::info('Redirecting to client.home.index');
            return route('client.home.index');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
