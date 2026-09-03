<?php

use App\Console\Kernel;
use App\Http\Middleware\AdminOnly;
use Illuminate\Contracts\Console\Kernel as ConsoleKernelContract;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return tap(Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => AdminOnly::class,
        ]);

        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('admin*') ? route('admin.login') : route('client.auth.login'));
        $middleware->redirectUsersTo('/');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create(), function (Application $app): void {
        $app->singleton(ConsoleKernelContract::class, Kernel::class);
    });
