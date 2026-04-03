<?php

declare(strict_types=1);

namespace Epsicube\Foundation\Bootstrap;

use Epsicube\Foundation\EpsicubeApplication;
use Epsicube\Foundation\Providers\EpsicubeServiceProvider;
use Epsicube\Support\Facades\Modules;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Bootstrap\RegisterProviders;
use Illuminate\Foundation\PackageManifest;

class BootstrapEpsicube
{
    public function bootstrap(Application $app): void
    {
        $app->register(EpsicubeServiceProvider::class); // <- force registering self provider

        // Override package manifest to enable service providers preventing
        $app->instance(PackageManifest::class, new EpsicubePackageManifest(
            new Filesystem, $app->basePath(), $app->getCachedPackagesPath()
        ));

        $app->beforeBootstrapping(RegisterProviders::class, function (EpsicubeApplication $app): void {
            $modulesManager = $app->make(Modules::$accessor);
            if (empty($providers = $modulesManager->getPreventedProviders())) {
                return;
            }

            /** @var EpsicubePackageManifest $manifest */
            $manifest = $app->make(PackageManifest::class);
            $manifest->addExclusions($providers);
        });

        $app->afterBootstrapping(RegisterProviders::class, function (EpsicubeApplication $app): void {
            $modulesManager = $app->make(Modules::$accessor);
            $modulesManager->bootstrap($app);
        });
    }
}
