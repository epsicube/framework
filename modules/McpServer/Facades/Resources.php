<?php

declare(strict_types=1);

namespace UniGaleModules\McpServer\Facades;

use Illuminate\Support\Facades\Facade;
use UniGaleModules\McpServer\Contracts\Resource;
use UniGaleModules\McpServer\Registries\ResourcesRegistry;

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
