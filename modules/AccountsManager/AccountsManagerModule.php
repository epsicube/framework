<?php

declare(strict_types=1);

namespace UniGaleModules\AccountsManager;

use Composer\InstalledVersions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use UniGale\Foundation\Concerns\CoreModule;
use UniGale\Foundation\Contracts\HasIntegrations;
use UniGale\Foundation\IntegrationsManager;
use UniGaleModules\AccountsManager\Integrations\Administration\AdministrationIntegration;
use UniGaleModules\AccountsManager\Models\Account;

class AccountsManagerModule extends CoreModule implements HasIntegrations
{
    private ?string $hypercoreContext = null;

    public function coreIdentifier(): string
    {
        return 'accounts-manager';
    }

    public function name(): string
    {
        return __('Accounts Manager');
    }

    public function version(): string
    {
        return
            InstalledVersions::getPrettyVersion('unigale/framework')
            ?? InstalledVersions::getPrettyVersion('unigale/module-accounts-manager');
    }

    public function setHypercoreContext(string $context): void
    {
        $this->hypercoreContext = $context;
    }

    public function register(): void
    {
        $guardName = 'accounts';
        $this->app['config']->set("auth.guards.{$guardName}", [
            'driver'   => 'session', // <- todo custom
            'provider' => $guardName,
        ]);
        $this->app['config']->set("auth.providers.{$guardName}", [
            'driver' => 'eloquent',
            'model'  => Account::class,
        ]);
        $this->app['config']->set("auth.passwords.{$guardName}", [
            'provider' => $guardName,
            'table'    => 'account_password_reset_tokens',
            'expire'   => 60,
            'throttle' => 60,
        ]);
        // TODO custom sessions
        //        Auth::clearResolvedInstances();
        //        Password::clearResolvedInstances();
    }

    public function boot(): void
    {
        if ($this->hypercoreContext === null || $this->hypercoreContext === 'central') {
            $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        }
        if ($this->hypercoreContext === 'central') {
            $this->loadMigrationsFrom(__DIR__.'/database/migrations/hypercore');
        }
    }

    public function integrations(IntegrationsManager $integrations): void
    {
        $integrations->forModule(
            identifier: 'core::administration',
            whenEnabled: [AdministrationIntegration::class, 'handle']
        );
    }
}
