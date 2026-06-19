<?php

declare(strict_types=1);

namespace Epsicube\Foundation\Actions;

use Epsicube\Foundation\EpsicubePackageManifest;
use Epsicube\Foundation\Providers\EpsicubeServiceProvider;
use Epsicube\Support\Exceptions\BootstrapEpsicubeException;
use Epsicube\Support\Facades\Modules;
use Epsicube\Support\Facades\Options;
use Illuminate\Console\Events\CommandStarting;
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
            $manifest->addExclusions(...$providers);
        });

        // Bootstrap module after all providers registered
        $app->afterBootstrapping(RegisterProviders::class, function (Application $app): void {
            if (! Options::isFunctional()) {
                if ($app->runningInConsole() && ! $app->runningUnitTests()) {
                    self::registerConsoleWarning($app);

                    return;
                }

                if (! $app->runningInConsole() || $app->runningUnitTests()) {
                    throw BootstrapEpsicubeException::dueToNonFunctionalOptionsStore();
                }
            }

            $app->make(Modules::$accessor)->bootstrap($app);
        });
    }

    private static function registerConsoleWarning(Application $app): void
    {
        $app['events']->listen(CommandStarting::class, function (CommandStarting $event): void {
            $event->output->writeln(sprintf(
                '<fg=red;options=bold>WARNING</> %s',
                BootstrapEpsicubeException::dueToNonFunctionalOptionsStore()->getMessage()
            ));
        });
    }
}
