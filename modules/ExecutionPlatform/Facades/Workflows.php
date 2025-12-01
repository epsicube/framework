<?php

declare(strict_types=1);

namespace UniGaleModules\ExecutionPlatform\Facades;

use Illuminate\Support\Facades\Facade;
use UniGaleModules\ExecutionPlatform\Contracts\Workflow;
use UniGaleModules\ExecutionPlatform\Registries\WorkflowsRegistry;

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
