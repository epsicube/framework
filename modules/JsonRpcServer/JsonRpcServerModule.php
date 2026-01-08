<?php

declare(strict_types=1);

namespace EpsicubeModules\JsonRpcServer;

use Carbon\Laravel\ServiceProvider;
use Composer\InstalledVersions;
use Epsicube\Schemas\Properties\StringProperty;
use Epsicube\Schemas\Schema;
use Epsicube\Support\Contracts\HasOptions;
use Epsicube\Support\Contracts\Module;
use Epsicube\Support\Facades\Options;
use Epsicube\Support\ModuleIdentity;
use EpsicubeModules\JsonRpcServer\Facades\Procedures;
use EpsicubeModules\JsonRpcServer\Registries\ProceduresRegistry;
use Illuminate\Contracts\Foundation\CachesRoutes;
use Illuminate\Support\Facades\Route;

class JsonRpcServerModule extends ServiceProvider implements HasOptions, Module
{
    public function identifier(): string
    {
        return 'core::json-rpc-server';
    }

    public function identity(): ModuleIdentity
    {
        return ModuleIdentity::make(
            name: __('JSON-RPC Server'),
            version: InstalledVersions::getPrettyVersion('epsicube/framework')
            ?? InstalledVersions::getPrettyVersion('epsicube/module-json-rpc-server'),
            author: 'Core Team',
            description: __('Provides a JSON-RPC server implementation, enabling modules to expose remote procedures through a standardized, transport-agnostic API.')
        );
    }

    public function options(Schema $schema): void
    {
        $schema->append([
            'endpoint' => StringProperty::make()
                ->title(__('Endpoint'))
                ->description(__('Defines the URL path used to expose the JSON-RPC API.'))
                ->optional()
                ->default('/rpc/v1/endpoint'),
        ]);
    }

    public function register(): void
    {
        $this->app->singleton('jsonrpc-procedures', function () {
            return new ProceduresRegistry;
        });
        $this->app->alias('jsonrpc-procedures', Procedures::$accessor);

        $this->app->booted(function (): void {
            if ($this->app instanceof CachesRoutes && $this->app->routesAreCached()) {
                return;
            }

            Route::rpc(
                Options::get($this->identifier(), 'endpoint'),
                array_values(array_map('get_class', Procedures::all()))
            )->name('rpc.endpoint');
        });
    }

    public function boot(): void {}
}
