<?php

declare(strict_types=1);

namespace Epsicube\Foundation\Actions;

use Epsicube\Foundation\EpsicubePackageManifest;
use Epsicube\Foundation\Providers\EpsicubeServiceProvider;
use Epsicube\Support\Facades\Modules;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Bootstrap\RegisterProviders;
use Illuminate\Foundation\PackageManifest;

class InjectEpsicube
{
    public static function configure(Application $app): void
    {
        $app->register(EpsicubeServiceProvider::class);

        $app->instance(PackageManifest::class, new EpsicubePackageManifest(
            new Filesystem, $app->basePath(), $app->getCachedPackagesPath()
        ));

        $app->beforeBootstrapping(RegisterProviders::class, function (Application $app): void {
            $modulesManager = $app->make(Modules::$accessor);
            if (empty($providers = $modulesManager->getPreventedProviders())) {
                return;
            }

            /** @var EpsicubePackageManifest $manifest */
            $manifest = $app->make(PackageManifest::class);
            $manifest->addExclusions($providers);
        });

        // Bootstrap module after all providers registered
        $app->booting(function (Application $app): void {
            $modulesManager = $app->make(Modules::$accessor);
            $modulesManager->bootstrap($app);
        });
    }
}
