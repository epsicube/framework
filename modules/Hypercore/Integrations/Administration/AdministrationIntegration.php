<?php

declare(strict_types=1);

namespace EpsicubeModules\Hypercore\Integrations\Administration;

use EpsicubeModules\Administration\Administration;
use Filament\Panel;

/** TODO extends Integration
 * Support attributes LoadOn
 */
class AdministrationIntegration
{
    public static function handle(): void
    {
        Administration::configureUsing(function (Panel $admin): void {
            $admin->discoverResources(in: __DIR__.'/Resources', for: __NAMESPACE__.'\\Resources');
        });
    }
}
