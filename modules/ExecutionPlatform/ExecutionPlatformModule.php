<?php

declare(strict_types=1);

namespace UniGaleModules\ExecutionPlatform;

use Composer\InstalledVersions;
use UniGale\Foundation\Concerns\CoreModule;
use UniGale\Foundation\Contracts\HasIntegrations;
use UniGale\Foundation\IntegrationsManager;
use UniGaleModules\ExecutionPlatform\Console\Commands\ListCommand;
use UniGaleModules\ExecutionPlatform\Facades\Activities;
use UniGaleModules\ExecutionPlatform\Facades\Workflows;
use UniGaleModules\ExecutionPlatform\Integrations\Administration\AdministrationIntegration;
use UniGaleModules\ExecutionPlatform\Registries\ActivitiesRegistry;
use UniGaleModules\ExecutionPlatform\Registries\WorkflowsRegistry;

class ExecutionPlatformModule extends CoreModule implements HasIntegrations
{
    protected function coreIdentifier(): string
    {
        return 'execution-platform';
    }

    public function name(): string
    {
        return __('Execution Platform');
    }

    public function description(): ?string
    {
        return __('Provides support for asynchronous workflows and activities, enabling modules to extend these capabilities');
    }

    public function version(): string
    {
        return InstalledVersions::getPrettyVersion('unigale/framework')
            ?? InstalledVersions::getPrettyVersion('unigale/module-execution-platform');
    }

    public function register(): void
    {
        $this->app->singleton(Workflows::$accessor, WorkflowsRegistry::class);
        $this->app->singleton(Activities::$accessor, ActivitiesRegistry::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        $this->commands([ListCommand::class]);
    }

    public function integrations(IntegrationsManager $integrations): void
    {
        $integrations->forModule(
            identifier: 'core::administration',
            whenEnabled: [AdministrationIntegration::class, 'handle']
        );
    }
}
