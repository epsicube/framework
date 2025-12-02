<?php

declare(strict_types=1);

namespace UniGaleModules\ExecutionPlatform\Facades;

use Illuminate\Support\Facades\Facade;
use UniGaleModules\ExecutionPlatform\Contracts\Activity;
use UniGaleModules\ExecutionPlatform\Registries\ActivitiesRegistry;

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
