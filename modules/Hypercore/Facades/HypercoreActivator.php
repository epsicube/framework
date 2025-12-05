<?php

declare(strict_types=1);

namespace EpsicubeModules\Hypercore\Facades;

use EpsicubeModules\Hypercore\Models\Tenant;

class HypercoreActivator
{
    public static function centralConnectionName(): string
    {
        return 'central';
    }

    public static function tenantConnectionName(): string
    {
        return 'tenant';
    }

    public static function tenant(): Tenant
    {
        return $_SERVER['hypercore::tenant'];
    }
}
