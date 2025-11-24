<?php

declare(strict_types=1);

namespace UniGale\Foundation\Facades;

use Illuminate\Support\Facades\Facade;
use UniGale\Foundation\Registries\ModulesRegistry;

class Modules extends Facade
{
    public static string $accessor = ModulesRegistry::class;

    protected static function getFacadeAccessor(): string
    {
        return static::$accessor;
    }
}
