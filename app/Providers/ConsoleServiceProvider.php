<?php

namespace App\Providers;

use App\Console\ContainerCommandLoader;
use Illuminate\Console\Application as Artisan;
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
        // Override setContainerCommandLoader method to use our custom ContainerCommandLoader
        Artisan::starting(function ($artisan) {
            $reflection = new \ReflectionClass($artisan);
            $laravelProperty = $reflection->getProperty('laravel');
            $laravelProperty->setAccessible(true);
            $laravel = $laravelProperty->getValue($artisan);
            
            $commandMapProperty = $reflection->getProperty('commandMap');
            $commandMapProperty->setAccessible(true);
            $commandMap = $commandMapProperty->getValue($artisan);
            
            // Use our custom ContainerCommandLoader that sets Laravel instance
            $artisan->setCommandLoader(new ContainerCommandLoader($laravel, $commandMap, $laravel));
        });
        
        // Also set Laravel instance for any commands resolved from container
        $this->app->afterResolving(Command::class, function ($command, $app) {
            if (method_exists($command, 'setLaravel')) {
                $command->setLaravel($app);
            }
        });
    }
}
