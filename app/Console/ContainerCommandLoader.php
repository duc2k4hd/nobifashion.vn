<?php

namespace App\Console;

use Illuminate\Console\ContainerCommandLoader as BaseContainerCommandLoader;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class ContainerCommandLoader extends BaseContainerCommandLoader
{
    /**
     * The Laravel application instance.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $laravel;

    /**
     * Create a new command loader instance.
     *
     * @param  \Psr\Container\ContainerInterface  $container
     * @param  array  $commandMap
     * @param  \Illuminate\Contracts\Container\Container  $laravel
     */
    public function __construct($container, array $commandMap, $laravel)
    {
        parent::__construct($container, $commandMap);
        $this->laravel = $laravel;
    }

    /**
     * Resolve a command from the container.
     *
     * @param  string  $name
     * @return \Symfony\Component\Console\Command\Command
     *
     * @throws \Symfony\Component\Console\Exception\CommandNotFoundException
     */
    public function get(string $name): SymfonyCommand
    {
        $command = parent::get($name);

        // Set Laravel instance for Laravel commands
        if ($command instanceof Command && $this->laravel) {
            $command->setLaravel($this->laravel);
        }

        return $command;
    }
}
