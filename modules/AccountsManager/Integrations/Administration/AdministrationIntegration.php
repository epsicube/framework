<?php

declare(strict_types=1);

namespace UniGaleModules\AccountsManager\Integrations\Administration;

use Filament\Http\Middleware\Authenticate;
use UniGaleModules\Administration\Administration;

class AdministrationIntegration
{
    public static function handle(): void
    {
        Administration::configureUsing(function (Administration $admin) {

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
