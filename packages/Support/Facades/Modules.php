<?php

declare(strict_types=1);

namespace Epsicube\Support\Facades;

use Epsicube\Foundation\Managers\ModulesManager;
use Illuminate\Support\Facades\Facade;

class Modules extends Facade
{
    /**
     * keep reference to ensure ide helper works
     * TODO declare contracts or use static phpdoc
     */
    public static string $accessor = ModulesManager::class;

    protected static function getFacadeAccessor(): string
    {
        return static::$accessor;
    }
}
