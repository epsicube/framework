<?php

declare(strict_types=1);

namespace UniGaleModules\ExecutionPlatform;

use Carbon\Laravel\ServiceProvider;
use Composer\InstalledVersions;
use UniGale\Support\Contracts\HasIntegrations;
use UniGale\Support\Contracts\Module;
use UniGale\Support\Integrations;
use UniGale\Support\ModuleIdentity;
use UniGaleModules\ExecutionPlatform\Console\Commands\ListCommand;
use UniGaleModules\ExecutionPlatform\Facades\Activities;
use UniGaleModules\ExecutionPlatform\Facades\Workflows;
use UniGaleModules\ExecutionPlatform\Integrations\Administration\AdministrationIntegration;
use UniGaleModules\ExecutionPlatform\Registries\ActivitiesRegistry;
use UniGaleModules\ExecutionPlatform\Registries\WorkflowsRegistry;

class ExecutionPlatformModule extends ServiceProvider implements HasIntegrations, Module
{
    public function identifier(): string
    {
        return 'core::execution-platform';
    }

    public function identity(): ModuleIdentity
    {
        return ModuleIdentity::make(
            name: __('Execution Platform'),
            version: InstalledVersions::getPrettyVersion('unigale/framework')
            ?? InstalledVersions::getPrettyVersion('unigale/module-execution-platform'),
            author: 'Core Team',
            description: __('Provides support for asynchronous workflows and activities, enabling modules to extend these capabilities')
        );
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

    public function integrations(): Integrations
    {
        return Integrations::make()->forModule(
            identifier: 'core::administration',
            whenEnabled: [AdministrationIntegration::class, 'handle']
        );
    }
}
