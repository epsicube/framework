<?php

declare(strict_types=1);

namespace EpsicubeModules\McpServer\Facades;

use EpsicubeModules\McpServer\Contracts\Tool;
use EpsicubeModules\McpServer\Registries\ToolsRegistry;
use Illuminate\Support\Facades\Facade;

class Tools extends Facade
{
    /**
     * keep reference to ensure ide helper works
     * TODO declare contracts or use static phpdoc
     */
    public static string $accessor = ToolsRegistry::class;

    protected static function getFacadeAccessor(): string
    {
        return static::$accessor;
    }

    public static function register(Tool ...$items): void
    {
        static::resolved(function (ToolsRegistry $registry) use ($items): void {
            $registry->register(...$items);
        });
    }
}
