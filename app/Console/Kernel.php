<?php

namespace App\Console;

use Illuminate\Console\Application as Artisan;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Symfony\Component\EventDispatcher\EventDispatcher;

class Kernel extends ConsoleKernel
{
    protected function shouldDiscoverCommands()
    {
        return true;
    }

    protected function getArtisan()
    {
        if (! is_null($this->artisan)) {
            return $this->artisan;
        }

        $this->artisan = new Artisan($this->app, $this->events, $this->app->version());

        $this->artisan->resolveCommands($this->commands);
        $this->artisan->setCommandLoader(
            new ContainerCommandLoader($this->app, $this->resolveCommandMap($this->artisan), $this->app)
        );

        if ($this->symfonyDispatcher instanceof EventDispatcher) {
            $this->artisan->setDispatcher($this->symfonyDispatcher);
            $this->artisan->setSignalsToDispatchEvent();
        }

        return $this->artisan;
    }

    protected function resolveCommandMap(Artisan $artisan): array
    {
        try {
            $reflection = new \ReflectionClass($artisan);

            if (! $reflection->hasProperty('commandMap')) {
                return [];
            }

            $commandMapProperty = $reflection->getProperty('commandMap');
            $commandMapProperty->setAccessible(true);

            $commandMap = $commandMapProperty->getValue($artisan);

            return is_array($commandMap) ? $commandMap : [];
        } catch (\ReflectionException $exception) {
            report($exception);

            return [];
        }
    }
}
