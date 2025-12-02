<?php

declare(strict_types=1);

namespace UniGaleModules\McpServer\Facades;

use Illuminate\Support\Facades\Facade;
use UniGaleModules\McpServer\Contracts\Tool;
use UniGaleModules\McpServer\Registries\ToolsRegistry;

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
        static::resolved(function (ToolsRegistry $registry) use ($items) {
            $registry->register(...$items);
        });
    }
}
