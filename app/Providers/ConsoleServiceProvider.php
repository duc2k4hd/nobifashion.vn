<?php

namespace App\Providers;

use Illuminate\Console\Command;
use Illuminate\Support\ServiceProvider;

class ConsoleServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->app->afterResolving(Command::class, function ($command, $app) {
            if (method_exists($command, 'setLaravel')) {
                $command->setLaravel($app);
            }
        });
    }
}
