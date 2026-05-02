<?php

declare(strict_types=1);

namespace Epsicube\Tests\Modules\JsonRpcServer\Fixtures;

use Epsicube\Support\Contracts\IsModule;
use Epsicube\Support\Modules\Dependencies;
use Epsicube\Support\Modules\Module;
use EpsicubeModules\JsonRpcServer\Facades\Procedures;
use Illuminate\Support\ServiceProvider;
use Sajya\Server\ServerServiceProvider;

final class RegisterProcedureModule extends ServiceProvider implements IsModule
{
    public function module(): Module
    {
        return Module::make('tests::json-rpc-procedure-provider', '1.0.0')
            ->dependencies(fn (Dependencies $dependencies) => $dependencies->module('core::json-rpc-server'))
            ->providers(self::class);
    }

    public function register(): void
    {
        $this->app->register(ServerServiceProvider::class);
        Procedures::register(new TestProcedure);
    }

    public function boot(): void {}
}
