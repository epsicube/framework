<?php

declare(strict_types=1);

namespace UniGaleModules\ExecutionPlatform\Facades;

use Illuminate\Support\Facades\Facade;

class Activities extends Facade
{
    public static string $accessor = 'activities';

    protected static function getFacadeAccessor(): string
    {
        return static::$accessor;
    }
}
