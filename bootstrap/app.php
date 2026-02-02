<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

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

        // TẠM THỜI COMMENT để tránh lỗi khi chạy artisan command
        // TODO: Tạo middleware class riêng để xử lý redirect này
        /*
        $middleware->redirectGuestsTo(function ($request) {
            // Tránh lỗi khi chạy artisan command
            if (!$request || !($request instanceof \Illuminate\Http\Request) || app()->runningInConsole()) {
                return null;
            }
            
            // Don't redirect if it's the login page
            if ($request->is('admin/login')) {
                return null; // Return null to allow access
            }
            
            if ($request->is('admin/*')) {
                try {
                return route('admin.login');
                } catch (\Throwable $e) {
                    return '/admin/login';
                }
            }
            
            try {
            return route('client.home.index');
            } catch (\Throwable $e) {
                return '/';
            }
        });
        */
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
