<?php

declare(strict_types=1);

namespace EpsicubeModules\Hypercore;

use Carbon\Laravel\ServiceProvider;
use Composer\InstalledVersions;
use Epsicube\Support\Contracts\HasIntegrations;
use Epsicube\Support\Contracts\InjectBootstrappers;
use Epsicube\Support\Contracts\Module;
use Epsicube\Support\Integrations;
use Epsicube\Support\ModuleIdentity;
use EpsicubeModules\Hypercore\Console\CacheCommand;
use EpsicubeModules\Hypercore\Console\ClearCommand;
use EpsicubeModules\Hypercore\Foundation\Bootstrap\BootstrapHypercore;
use EpsicubeModules\Hypercore\Integrations\Administration\AdministrationIntegration;

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
            version: InstalledVersions::getPrettyVersion('epsicube/framework')
                 ?? InstalledVersions::getPrettyVersion('epsicube/module-hypercore'),
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
            optimize: 'epsicube-tenants:cache',
            clear: 'epsicube-tenants:clear',
            key: 'epsicube-tenants'
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
