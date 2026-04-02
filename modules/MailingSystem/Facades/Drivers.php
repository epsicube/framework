<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Facades;

use EpsicubeModules\MailingSystem\Contracts\Driver;
use EpsicubeModules\MailingSystem\Registries\DriversRegistry;
use Illuminate\Support\Facades\Facade;

class Drivers extends Facade
{
    public static string $accessor = DriversRegistry::class;

    protected static function getFacadeAccessor(): string
    {
        return static::$accessor;
    }

    public static function register(Driver ...$items): void
    {
        static::resolved(function (DriversRegistry $registry) use ($items): void {
            $registry->register(...$items);
        });
    }
}
