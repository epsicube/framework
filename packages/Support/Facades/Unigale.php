<?php

declare(strict_types=1);

namespace UniGale\Support\Facades;

use Illuminate\Support\Facades\Facade;
use UniGale\Foundation\Managers\UnigaleManager;

class Unigale extends Facade
{
    /**
     * keep reference to ensure ide helper works
     * TODO declare contracts or use static phpdoc
     */
    public static string $accessor = UnigaleManager::class;

    protected static function getFacadeAccessor(): string
    {
        return static::$accessor;
    }

    public static function addWorkCommand(string $key, string $command): void
    {
        static::resolved(function (UnigaleManager $manager) use ($key, $command) {
            $manager->addWorkCommand($key, $command);
        });
    }
}
