<?php

declare(strict_types=1);

namespace UniGaleModules\ExecutionPlatform\Facades;

use Illuminate\Support\Facades\Facade;

class Workflows extends Facade
{
    public static string $accessor = 'workflows';

    protected static function getFacadeAccessor(): string
    {
        return static::$accessor;
    }
}
