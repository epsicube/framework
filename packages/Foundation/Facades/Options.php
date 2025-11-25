<?php

declare(strict_types=1);

namespace UniGale\Foundation\Facades;

use Illuminate\Support\Facades\Facade;
use UniGale\Foundation\Options\OptionsManager;

class Options extends Facade
{
    public static string $accessor = OptionsManager::class;

    protected static function getFacadeAccessor(): string
    {
        return static::$accessor;
    }
}
