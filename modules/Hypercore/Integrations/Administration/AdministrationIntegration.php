<?php

declare(strict_types=1);

namespace EpsicubeModules\Hypercore\Integrations\Administration;

use EpsicubeModules\Administration\Administration;

/** TODO extends Integration
 * Support attributes LoadOn
 */
class AdministrationIntegration
{
    public static function handle(): void
    {
        Administration::configureUsing(function (Administration $admin) {
            $admin->discoverResources(in: __DIR__.'/Resources', for: __NAMESPACE__.'\\Resources');
        });
    }
}
