<?php

declare(strict_types=1);

namespace Epsicube\Support\Facades;

use Epsicube\Foundation\Managers\EpsicubeManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Facade;

class Epsicube extends Facade
{
    /**
     * keep reference to ensure ide helper works
     * TODO declare contracts or use static phpdoc
     */
    public static string $accessor = EpsicubeManager::class;

    protected static function getFacadeAccessor(): string
    {
        return static::$accessor;
    }

    /**
     * @param  string|class-string<Command>  $command
     */
    public static function addWorkCommand(string $key, string $command): void
    {
        static::resolved(function (EpsicubeManager $manager) use ($key, $command): void {
            $manager->addWorkCommand($key, $command);
        });
    }

    public static function optimizes(string $key, ?string $optimizeCmd = null, ?string $clearCmd = null): void
    {
        static::resolved(function (EpsicubeManager $manager) use ($key, $optimizeCmd, $clearCmd): void {
            if ($optimizeCmd) {
                $manager->addOptimizeCommand($key, $optimizeCmd);
            }
            if ($clearCmd) {
                $manager->addClearCommand($key, $optimizeCmd);
            }
        });
    }
}
