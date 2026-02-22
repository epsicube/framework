<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform;

use Carbon\Laravel\ServiceProvider;
use Composer\InstalledVersions;
use Epsicube\Support\Contracts\IsModule;
use Epsicube\Support\Modules\Identity;
use Epsicube\Support\Modules\Module;
use Epsicube\Support\Modules\Support;
use Epsicube\Support\Modules\Supports;
use EpsicubeModules\ExecutionPlatform\Console\Commands\ActivitiesListCommand;
use EpsicubeModules\ExecutionPlatform\Console\Commands\ActivitiesRunCommand;
use EpsicubeModules\ExecutionPlatform\Console\Commands\WorkflowsListCommand;
use EpsicubeModules\ExecutionPlatform\Facades\Activities;
use EpsicubeModules\ExecutionPlatform\Facades\Workflows;
use EpsicubeModules\ExecutionPlatform\Integrations\Administration\AdministrationIntegration;
use EpsicubeModules\ExecutionPlatform\Integrations\JsonRpcServer\JsonRpcServerIntegration;
use EpsicubeModules\ExecutionPlatform\Integrations\McpServer\McpServerIntegration;
use EpsicubeModules\ExecutionPlatform\Registries\ActivitiesRegistry;
use EpsicubeModules\ExecutionPlatform\Registries\WorkflowsRegistry;

class ExecutionPlatformModule extends ServiceProvider implements IsModule
{
    public function module(): Module
    {
        return Module::make(
            identifier: 'core::execution-platform',
            version: InstalledVersions::getVersion('epsicube/framework')
                ?? InstalledVersions::getVersion('epsicube/module-execution-platform')
        )
            ->providers(static::class)
            ->identity(fn (Identity $identity) => $identity
                ->name(__('Execution Platform'))
                ->author('Core Team')
                ->description(__('Provides support for asynchronous workflows and activities, enabling modules to extend these capabilities'))
            )
            ->supports(fn (Supports $supports) => $supports->add(
                Support::forModule('core::administration', AdministrationIntegration::handle(...)),
                Support::forModule('core::mcp-server', McpServerIntegration::handle(...)),
                Support::forModule('core::json-rpc-server', JsonRpcServerIntegration::handle(...)),
            ));
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
}
