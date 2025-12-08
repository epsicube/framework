<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Facades;

use EpsicubeModules\ExecutionPlatform\Contracts\Activity;
use EpsicubeModules\ExecutionPlatform\Registries\ActivitiesRegistry;
use Illuminate\Support\Facades\Facade;

class Activities extends Facade
{
    public static string $accessor = ActivitiesRegistry::class;

    protected static function getFacadeAccessor(): string
    {
        return static::$accessor;
    }

    public static function register(Activity ...$items): void
    {
        static::resolved(function (ActivitiesRegistry $registry) use ($items) {
            $registry->register(...$items);
        });
    }
}
