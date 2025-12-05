<?php

declare(strict_types=1);

namespace Epsicube\Support\Facades;

use Epsicube\Foundation\Manager\EpsicubeManager;
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

    public static function addWorkCommand(string $key, string $command): void
    {
        static::resolved(function (EpsicubeManager $manager) use ($key, $command) {
            $manager->addWorkCommand($key, $command);
        });
    }
}
