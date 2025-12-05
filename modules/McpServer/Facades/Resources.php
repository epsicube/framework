<?php

declare(strict_types=1);

namespace EpsicubeModules\McpServer\Facades;

use EpsicubeModules\McpServer\Contracts\Resource;
use EpsicubeModules\McpServer\Registries\ResourcesRegistry;
use Illuminate\Support\Facades\Facade;

class Resources extends Facade
{
    /**
     * keep reference to ensure ide helper works
     * TODO declare contracts or use static phpdoc
     */
    public static string $accessor = ResourcesRegistry::class;

    protected static function getFacadeAccessor(): string
    {
        return static::$accessor;
    }

    public static function register(Resource ...$items): void
    {
        static::resolved(function (ResourcesRegistry $registry) use ($items) {
            $registry->register(...$items);
        });
    }
}
