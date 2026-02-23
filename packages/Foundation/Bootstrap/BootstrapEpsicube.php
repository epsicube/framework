<?php

declare(strict_types=1);

namespace Epsicube\Foundation\Bootstrap;

use Epsicube\Foundation\EpsicubeApplication;
use Epsicube\Foundation\Providers\EpsicubeServiceProvider;
use Epsicube\Support\Facades\Modules;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Bootstrap\RegisterProviders;

class BootstrapEpsicube
{
    public function bootstrap(Application $app): void
    {
        $app->register(EpsicubeServiceProvider::class); // <- force registering self provider

        $app->afterBootstrapping(RegisterProviders::class, function (EpsicubeApplication $app): void {
            $modulesManager = $app->make(Modules::$accessor);
            $modulesManager->bootstrap($app);
        });
    }
}
