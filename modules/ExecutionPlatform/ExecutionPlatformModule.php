<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform;

use Carbon\Laravel\ServiceProvider;
use Composer\InstalledVersions;
use Epsicube\Support\Contracts\HasIntegrations;
use Epsicube\Support\Contracts\Module;
use Epsicube\Support\Integrations;
use Epsicube\Support\ModuleIdentity;
use EpsicubeModules\ExecutionPlatform\Console\Commands\ActivitiesListCommand;
use EpsicubeModules\ExecutionPlatform\Console\Commands\ActivitiesRunCommand;
use EpsicubeModules\ExecutionPlatform\Console\Commands\WorkflowsListCommand;
use EpsicubeModules\ExecutionPlatform\Facades\Activities;
use EpsicubeModules\ExecutionPlatform\Facades\Workflows;
use EpsicubeModules\ExecutionPlatform\Integrations\Administration\AdministrationIntegration;
use EpsicubeModules\ExecutionPlatform\Integrations\McpServer\McpServerIntegration;
use EpsicubeModules\ExecutionPlatform\Registries\ActivitiesRegistry;
use EpsicubeModules\ExecutionPlatform\Registries\WorkflowsRegistry;

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
            version: InstalledVersions::getPrettyVersion('epsicube/framework')
            ?? InstalledVersions::getPrettyVersion('epsicube/module-execution-platform'),
            author: 'Core Team',
            description: __('Provides support for asynchronous workflows and activities, enabling modules to extend these capabilities')
        );
    }

    public function register(): void
    {
        $this->app->singleton(Workflows::$accessor, function () {
            return new WorkflowsRegistry;
        });
        $this->app->singleton(Activities::$accessor, function () {
            return new ActivitiesRegistry;
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        $this->commands([
            WorkflowsListCommand::class,
            ActivitiesRunCommand::class,
            ActivitiesListCommand::class,
        ]);
    }

    public function integrations(): Integrations
    {
        return Integrations::make()->forModule(
            identifier: 'core::administration',
            whenEnabled: [AdministrationIntegration::class, 'handle']
        )->forModule(
            identifier: 'core::mcp-server',
            whenEnabled: [McpServerIntegration::class, 'handle']
        );
    }
}
