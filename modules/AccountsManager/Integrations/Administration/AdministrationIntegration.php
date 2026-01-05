<?php

declare(strict_types=1);

namespace EpsicubeModules\AccountsManager\Integrations\Administration;

use EpsicubeModules\Administration\Administration;
use Filament\Http\Middleware\Authenticate;

class AdministrationIntegration
{
    public static function handle(): void
    {
        Administration::configureUsing(function (Administration $admin): void {

            // Show users panel
            $admin->discoverResources(in: __DIR__.'/Resources', for: __NAMESPACE__.'\\Resources');

            // Enable authentication
            $admin->login();
            $admin->passwordReset();
            $admin->registration();
            $admin->profile();
            $admin->authMiddleware([
                Authenticate::class,
            ]);
            $admin->authGuard('accounts');
            $admin->authPasswordBroker('accounts');

        });
    }
}
