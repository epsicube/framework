<?php

declare(strict_types=1);

namespace EpsicubeModules\McpServer;

use Carbon\Laravel\ServiceProvider;
use Composer\InstalledVersions;
use Epsicube\Schemas\Enums\StringFormat;
use Epsicube\Schemas\Properties\StringProperty;
use Epsicube\Schemas\Schema;
use Epsicube\Support\Contracts\HasOptions;
use Epsicube\Support\Contracts\Module;
use Epsicube\Support\ModuleIdentity;
use EpsicubeModules\McpServer\Facades\Resources;
use EpsicubeModules\McpServer\Facades\Tools;
use EpsicubeModules\McpServer\Mcp\Servers\McpServer;
use EpsicubeModules\McpServer\Registries\ResourcesRegistry;
use EpsicubeModules\McpServer\Registries\ToolsRegistry;
use Laravel\Mcp\Facades\Mcp;

class McpServerModule extends ServiceProvider implements HasOptions, Module
{
    public function identifier(): string
    {
        return 'core::mcp-server';
    }

    public function identity(): ModuleIdentity
    {
        return ModuleIdentity::make(
            name: __('MCP Server'),
            version: InstalledVersions::getPrettyVersion('epsicube/framework')
            ?? InstalledVersions::getPrettyVersion('epsicube/module-mcp-server'),
            author: 'Core Team',
            description: __('Integrates MCP capabilities into the application, allowing modules to declare and expose MCP resources.')
        );
    }

    public function options(Schema $schema): void
    {
        $schema->append([
            'name' => StringProperty::make()
                ->title('Server name')
                ->optional()
                ->default(fn () => __(':app_name internal MCP Server', ['app_name' => config('app.name')])),
            'version' => StringProperty::make()
                ->title('Server version')
                ->optional()
                ->default(fn () => $this->identity()->version),
            'instructions' => StringProperty::make()
                ->title('Server instructions')
                ->format(StringFormat::MARKDOWN)
                ->optional()
                ->default(fn () => file_get_contents(__DIR__.'/resources/stubs/INSTRUCTIONS.md')),
        ]);
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
