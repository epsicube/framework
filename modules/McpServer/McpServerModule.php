<?php

declare(strict_types=1);

namespace UniGaleModules\McpServer;

use Carbon\Laravel\ServiceProvider;
use Composer\InstalledVersions;
use Illuminate\Support\Str;
use Laravel\Mcp\Facades\Mcp;
use UniGale\Support\Contracts\HasOptions;
use UniGale\Support\Contracts\Module;
use UniGale\Support\ModuleIdentity;
use UniGale\Support\OptionsDefinition;
use UniGaleModules\McpServer\Console\Commands\ServeCommand;
use UniGaleModules\McpServer\Facades\Tools;
use UniGaleModules\McpServer\Mcp\Servers\McpServer;
use UniGaleModules\McpServer\Registries\ToolsRegistry;

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
            version: InstalledVersions::getPrettyVersion('unigale/framework')
            ?? InstalledVersions::getPrettyVersion('unigale/module-mcp-server'),
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

        $this->app->booted(function () {
            Mcp::web('/mcp', McpServer::class);
            Mcp::local('unigale', McpServer::class);
        });
    }

    public function boot(): void {

    }
}
