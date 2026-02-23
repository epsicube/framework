<?php

declare(strict_types=1);

namespace Epsicube\Foundation\Bootstrap;

use Epsicube\Foundation\EpsicubeApplication;
use Epsicube\Foundation\Providers\EpsicubeServiceProvider;
use Epsicube\Support\Facades\Modules;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Bootstrap\RegisterFacades;
use Illuminate\Foundation\Bootstrap\RegisterProviders;
use Throwable;

class BootstrapEpsicube
{
    public function bootstrap(Application $app): void
    {
        $app->register(EpsicubeServiceProvider::class); // <- force registering self provider

        $app->afterBootstrapping(RegisterFacades::class, function (EpsicubeApplication $app): void {
            try {
                $modulesManager = $app->make(Modules::$accessor);
                $modulesManager->bootstrap($app);
            } catch (Throwable $e) {
                // Defer error throwing after ExceptionHandler was registered
                $app->afterBootstrapping(RegisterProviders::class, function () use ($e): void {
                    throw $e;
                });
            }
        });
    }
}
