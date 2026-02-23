<?php

declare(strict_types=1);

namespace EpsicubeModules\JsonRpcServer;

use Carbon\Laravel\ServiceProvider;
use Composer\InstalledVersions;
use Epsicube\Schemas\Properties\StringProperty;
use Epsicube\Schemas\Schema;
use Epsicube\Support\Contracts\IsModule;
use Epsicube\Support\Facades\Options;
use Epsicube\Support\Modules\Identity;
use Epsicube\Support\Modules\Module;
use EpsicubeModules\JsonRpcServer\Facades\Procedures;
use EpsicubeModules\JsonRpcServer\Registries\ProceduresRegistry;
use Illuminate\Contracts\Foundation\CachesRoutes;
use Illuminate\Support\Facades\Route;

class JsonRpcServerModule extends ServiceProvider implements IsModule
{
    public function module(): Module
    {
        return Module::make(
            identifier: 'core::json-rpc-server',
            version: InstalledVersions::getVersion('epsicube/framework')
            ?? InstalledVersions::getVersion('epsicube/module-json-rpc-server')
        )
            ->providers(static::class)
            ->identity(fn (Identity $identity) => $identity
                ->name(__('JSON-RPC Server'))
                ->author('Core Team')
                ->description(__('Provides a JSON-RPC server implementation, enabling modules to expose remote procedures through a standardized, transport-agnostic API.'))
            )
            ->options(fn (Schema $schema) => $schema->append([
                'endpoint' => StringProperty::make()
                    ->title(__('Endpoint'))
                    ->description(__('Defines the URL path used to expose the JSON-RPC API.'))
                    ->optional()
                    ->default('/rpc/v1/endpoint'),
            ]));
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
                Options::get($this->module()->identifier, 'endpoint'),
                array_values(array_map('get_class', Procedures::all()))
            )->name('rpc.endpoint');
        });
    }

    public function boot(): void {}
}
