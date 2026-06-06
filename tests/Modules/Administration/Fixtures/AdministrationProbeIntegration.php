<?php

declare(strict_types=1);

namespace Epsicube\Tests\Modules\Administration\Fixtures;

use EpsicubeModules\Administration\Administration;
use Filament\Panel;

final class AdministrationProbeIntegration
{
    public static function handle(): void
    {
        Administration::configureUsing(function (Panel $panel): void {
            $panel->discoverResources(
                in: __DIR__.'/AdministrationProbe/Resources',
                for: 'Epsicube\\Tests\\Modules\\Administration\\Fixtures\\AdministrationProbe\\Resources',
            );
        });
    }
}
