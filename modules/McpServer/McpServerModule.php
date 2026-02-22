<?php

declare(strict_types=1);

namespace EpsicubeModules\McpServer;

use Carbon\Laravel\ServiceProvider;
use Composer\InstalledVersions;
use Epsicube\Schemas\Enums\StringFormat;
use Epsicube\Schemas\Properties\StringProperty;
use Epsicube\Schemas\Schema;
use Epsicube\Support\Contracts\IsModule;
use Epsicube\Support\Modules\Identity;
use Epsicube\Support\Modules\Module;
use EpsicubeModules\McpServer\Facades\Resources;
use EpsicubeModules\McpServer\Facades\Tools;
use EpsicubeModules\McpServer\Mcp\Servers\McpServer;
use EpsicubeModules\McpServer\Registries\ResourcesRegistry;
use EpsicubeModules\McpServer\Registries\ToolsRegistry;
use Laravel\Mcp\Facades\Mcp;

class McpServerModule extends ServiceProvider implements IsModule
{
    public function module(): Module
    {
        return Module::make(
            identifier: 'core::mcp-server',
            version: InstalledVersions::getVersion('epsicube/framework')
                ?? InstalledVersions::getVersion('epsicube/module-mcp-server')
        )
            ->providers(static::class)
            ->identity(fn (Identity $identity) => $identity
                ->name(__('MCP Server'))
                ->author('Core Team')
                ->description(__('Integrates MCP capabilities into the application, allowing modules to declare and expose MCP resources.'))
            )
            ->options(fn (Schema $schema) => $schema->append([
                'name' => StringProperty::make()
                    ->title('Server name')
                    ->optional()
                    ->default(fn () => __(':app_name internal MCP Server', ['app_name' => config('app.name')])),
                'version' => StringProperty::make()
                    ->title('Server version')
                    ->optional()
                    ->default(fn () => $this->module()->version),
                'instructions' => StringProperty::make()
                    ->title('Server instructions')
                    ->format(StringFormat::MARKDOWN)
                    ->optional()
                    ->default(fn () => file_get_contents(__DIR__.'/resources/stubs/INSTRUCTIONS.md')),
            ]));
    }

    public function register(): void
    {
        $this->app->singleton('mcp-tools', function () {
            return new ToolsRegistry;
        });
        $this->app->alias('mcp-tools', Tools::$accessor);

        $this->app->singleton('mcp-resources', function () {
            return new ResourcesRegistry;
        });
        $this->app->alias('mcp-resources', Resources::$accessor);

        $this->app->booted(function (): void {
            Mcp::web('/mcp', McpServer::class);
            Mcp::local('epsicube', McpServer::class);
        });
    }

    public function boot(): void {}
}
