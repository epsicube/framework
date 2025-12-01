<?php

declare(strict_types=1);

namespace UniGaleModules\Hypercore;

use Carbon\Laravel\ServiceProvider;
use Composer\InstalledVersions;
use UniGale\Support\Contracts\HasIntegrations;
use UniGale\Support\Contracts\InjectBootstrappers;
use UniGale\Support\Contracts\Module;
use UniGale\Support\Integrations;
use UniGale\Support\ModuleIdentity;
use UniGaleModules\Hypercore\Console\CacheCommand;
use UniGaleModules\Hypercore\Console\ClearCommand;
use UniGaleModules\Hypercore\Foundation\Bootstrap\BootstrapHypercore;
use UniGaleModules\Hypercore\Integrations\Administration\AdministrationIntegration;

class HypercoreModule extends ServiceProvider implements HasIntegrations, InjectBootstrappers, Module
{
    public function identifier(): string
    {
        return 'core::hypercore';
    }

    public function identity(): ModuleIdentity
    {
        return ModuleIdentity::make(
            name: __('Hyper-Core 🚀'),
            version: InstalledVersions::getPrettyVersion('unigale/framework')
                 ?? InstalledVersions::getPrettyVersion('unigale/module-hypercore'),
            author: 'Core Team',
            description: __('Turn it into a multi-app manager, enabling multi-tenant setups and effortless handling of multiple applications.'),
        );
    }

    public function bootstrappers(): array
    {
        return [BootstrapHypercore::class];
    }

    public function register(): void {}

    public function boot(): void
    {
        // TODO DISABLE CORE MIGRATIONS
        // MOVE HYPERCORE INTO FOUNDATION INSTEAD OF MODULE
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        $this->commands([
            CacheCommand::class,
            ClearCommand::class,
        ]);

        $this->optimizes(
            optimize: 'unigale-tenants:cache',
            clear: 'unigale-tenants:clear',
            key: 'unigale-tenants'
        );
    }

    public function integrations(): Integrations
    {
        return Integrations::make()->forModule(
            identifier: 'core::administration',
            whenEnabled: [AdministrationIntegration::class, 'handle'],
        );
    }
}
