<?php

declare(strict_types=1);

namespace UniGale\Support\Facades;

use Illuminate\Support\Facades\Facade;
use UniGale\Foundation\Managers\ModulesManager;

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
