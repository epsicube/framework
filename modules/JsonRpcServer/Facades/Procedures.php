<?php

declare(strict_types=1);

namespace EpsicubeModules\JsonRpcServer\Facades;

use EpsicubeModules\JsonRpcServer\Concerns\Procedure;
use EpsicubeModules\JsonRpcServer\Registries\ProceduresRegistry;
use Illuminate\Support\Facades\Facade;

class Procedures extends Facade
{
    /**
     * keep reference to ensure ide helper works
     * TODO declare contracts or use static phpdoc
     */
    public static string $accessor = ProceduresRegistry::class;

    protected static function getFacadeAccessor(): string
    {
        return static::$accessor;
    }

    public static function register(Procedure ...$items): void
    {
        static::resolved(function (ProceduresRegistry $registry) use ($items): void {
            $registry->register(...$items);
        });
    }
}
