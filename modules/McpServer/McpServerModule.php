<?php

declare(strict_types=1);

namespace EpsicubeModules\McpServer;

use Carbon\Laravel\ServiceProvider;
use Composer\InstalledVersions;
use Epsicube\Support\Contracts\HasOptions;
use Epsicube\Support\Contracts\Module;
use Epsicube\Support\ModuleIdentity;
use Epsicube\Support\OptionsDefinition;
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

    public function options(): OptionsDefinition
    {
        return OptionsDefinition::make()->add(
            key: 'name',
            type: 'string',
            default: fn () => __(':app_name internal MCP Server', ['app_name' => config('app.name')]),
        )->add(
            key: 'version',
            type: 'string',
            default: fn () => $this->identity()->version
        )->add(
            key: 'instructions',
            type: 'string',
            default: fn () => __(<<<'markdown'
            This server allows you to:

            - Interact with **business processes**.
            - Retrieve and manage **internal resources**.
            - Execute a variety of **internal actions**.

            Use this server to:

            1. Streamline operations.
            2. Automate tasks efficiently.
            3. Ensure seamless communication within the system.
            markdown)
        );
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

        $this->app->booted(function () {
            Mcp::web('/mcp', McpServer::class);
            Mcp::local('epsicube', McpServer::class);
        });
    }

    public function boot(): void {}
}
