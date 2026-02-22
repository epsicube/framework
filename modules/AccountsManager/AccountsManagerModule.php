<?php

declare(strict_types=1);

namespace EpsicubeModules\AccountsManager;

use Composer\InstalledVersions;
use Epsicube\Support\Contracts\IsModule;
use Epsicube\Support\Modules\Identity;
use Epsicube\Support\Modules\Module;
use Epsicube\Support\Modules\Support;
use Epsicube\Support\Modules\Supports;
use EpsicubeModules\AccountsManager\Integrations\Administration\AdministrationIntegration;
use EpsicubeModules\AccountsManager\Models\Account;
use Illuminate\Support\ServiceProvider;

class AccountsManagerModule extends ServiceProvider implements IsModule
{
    private ?string $hypercoreContext = null;

    public function identifier(): string
    {
        return 'core::accounts-manager';
    }

    public function module(): Module
    {
        return Module::make(
            identifier: 'core::accounts-manager',
            version: InstalledVersions::getVersion('epsicube/framework')
                ?? InstalledVersions::getVersion('epsicube/module-accounts-manager')
        )->providers(static::class)
            ->identity(fn (Identity $identity) => $identity
                ->name(__('Accounts Manager'))
                ->author('Core Team')
            )
            ->supports(fn (Supports $supports) => $supports->add(
                Support::forModule('core::administration', AdministrationIntegration::handle(...))
            ));
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
}
