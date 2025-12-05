<?php

declare(strict_types=1);

namespace Epsicube\Support\Facades;

use Epsicube\Foundation\Utilities\EpsicubeManifest;
use Illuminate\Support\Facades\Facade;

class Manifest extends Facade
{
    /**
     * keep reference to ensure ide helper works
     * TODO declare contracts or use static phpdoc
     */
    public static string $accessor = EpsicubeManifest::class;

    protected static function getFacadeAccessor(): string
    {
        return static::$accessor;
    }
}
