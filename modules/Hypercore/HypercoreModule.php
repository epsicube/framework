<?php

declare(strict_types=1);

namespace UniGaleModules\Hypercore;

use Composer\InstalledVersions;
use UniGale\Foundation\Concerns\CoreModule;
use UniGale\Foundation\Contracts\HasIntegrations;
use UniGale\Foundation\Contracts\InjectBootstrappers;
use UniGale\Foundation\IntegrationsManager;
use UniGaleModules\Hypercore\Console\CacheCommand;
use UniGaleModules\Hypercore\Console\ClearCommand;
use UniGaleModules\Hypercore\Foundation\Bootstrap\BootstrapHypercore;
use UniGaleModules\Hypercore\Integrations\Administration\AdministrationIntegration;

class HypercoreModule extends CoreModule implements HasIntegrations, InjectBootstrappers
{
    protected function coreIdentifier(): string
    {
        return 'hypercore';
    }

    public function name(): string
    {
        return __('Hyper-Core 🚀');
    }

    public function description(): ?string
    {
        return __('Turn it into a multi-app manager, enabling multi-tenant setups and effortless handling of multiple applications.');
    }

    public function version(): string
    {
        return InstalledVersions::getPrettyVersion('unigale/framework')
            ?? InstalledVersions::getPrettyVersion('unigale/module-hypercore');
    }

    public function bootstrappers(): array
    {
        return [BootstrapHypercore::class];
    }

    public function register(): void {}

    public function boot(): void
    {
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

    public function integrations(IntegrationsManager $integrations): void
    {
        $integrations->forModule(
            identifier: 'core::administration',
            whenEnabled: [AdministrationIntegration::class, 'handle'],
        );
    }
}
