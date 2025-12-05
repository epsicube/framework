<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Integrations\Administration;

use EpsicubeModules\Administration\Administration;

class AdministrationIntegration
{
    public static function handle(): void
    {
        Administration::configureUsing(function (Administration $admin) {
            $admin->discoverResources(in: __DIR__.'/Resources', for: __NAMESPACE__.'\\Resources');
        });
    }
}
