<?php

declare(strict_types=1);

namespace UniGaleModules\AccountsManager;

use Composer\InstalledVersions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\ServiceProvider;
use UniGale\Foundation\Contracts\HasIntegrations;
use UniGale\Foundation\Contracts\Module;
use UniGale\Foundation\IntegrationsManager;
use UniGale\Foundation\ModuleIdentity;
use UniGaleModules\AccountsManager\Integrations\Administration\AdministrationIntegration;
use UniGaleModules\AccountsManager\Models\Account;

class AccountsManagerModule extends ServiceProvider implements HasIntegrations, Module
{
    private ?string $hypercoreContext = null;

    public function identifier(): string
    {
        return 'core::accounts-manager';
    }

    public function identity(): ModuleIdentity
    {
        return ModuleIdentity::make(
            name: __('Accounts Manager'),
            version: InstalledVersions::getPrettyVersion('unigale/framework')
            ?? InstalledVersions::getPrettyVersion('unigale/module-accounts-manager'),
            author: 'Core Team',
        );
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
