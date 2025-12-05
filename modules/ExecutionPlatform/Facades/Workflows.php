<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Facades;

use EpsicubeModules\ExecutionPlatform\Contracts\Workflow;
use EpsicubeModules\ExecutionPlatform\Registries\WorkflowsRegistry;
use Illuminate\Support\Facades\Facade;

class Workflows extends Facade
{
    public static string $accessor = WorkflowsRegistry::class;

    protected static function getFacadeAccessor(): string
    {
        return static::$accessor;
    }

    public static function register(Workflow ...$items): void
    {
        static::resolved(function (WorkflowsRegistry $registry) use ($items) {
            $registry->register(...$items);
        });
    }
}
