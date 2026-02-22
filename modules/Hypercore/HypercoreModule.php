<?php

declare(strict_types=1);

namespace EpsicubeModules\Hypercore;

use Carbon\Laravel\ServiceProvider;
use Composer\InstalledVersions;
use Epsicube\Support\Contracts\InjectBootstrappers;
use Epsicube\Support\Contracts\IsModule;
use Epsicube\Support\Modules\Identity;
use Epsicube\Support\Modules\Module;
use Epsicube\Support\Modules\Support;
use Epsicube\Support\Modules\Supports;
use EpsicubeModules\Hypercore\Console\CacheCommand;
use EpsicubeModules\Hypercore\Console\ClearCommand;
use EpsicubeModules\Hypercore\Foundation\Bootstrap\BootstrapHypercore;
use EpsicubeModules\Hypercore\Integrations\Administration\AdministrationIntegration;

class HypercoreModule extends ServiceProvider implements InjectBootstrappers, IsModule
{
    public function module(): Module
    {
        return Module::make(
            identifier: 'core::hypercore',
            version: InstalledVersions::getVersion('epsicube/framework')
            ?? InstalledVersions::getVersion('epsicube/module-hypercore')
        )
            ->providers(static::class)
            ->identity(fn (Identity $identity) => $identity
                ->name(__('Execution Platform'))
                ->author('Core Team')
                ->description(__('Turn it into a multi-app manager, enabling multi-tenant setups and effortless handling of multiple applications.'))
            )
            ->supports(fn (Supports $supports) => $supports->add(
                Support::forModule('core::administration', AdministrationIntegration::handle(...)),
            ));
    }

    public function bootstrappers(): array
    {
        return [BootstrapHypercore::class];
    }

    public function boot(): void
    {
        // TODO DISABLE CORE MIGRATIONS
        // MOVE HYPERCORE INTO FOUNDATION INSTEAD OF MODULE
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        $this->commands([CacheCommand::class, ClearCommand::class]);
        $this->optimizes(optimize: 'epsicube-tenants:cache', clear: 'epsicube-tenants:clear', key: 'epsicube-tenants');
    }
}
