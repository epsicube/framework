<?php

declare(strict_types=1);

namespace UniGale\Foundation\Bootstrap;

use Error;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\DatabaseServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\FilesystemServiceProvider;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Foundation\Bootstrap\RegisterProviders;
use Illuminate\View\ViewServiceProvider;
use UniGale\Foundation\Contracts\InjectBootstrappers;
use UniGale\Foundation\Facades\Modules;
use UniGale\Foundation\Registries\ModulesRegistry;
use UniGale\Foundation\UnigaleApplication;
use UniGale\Foundation\UnigaleServiceProvider;

class BootstrapUnigale
{
    public function bootstrap(Application $app): void
    {
        $app->register(UnigaleServiceProvider::class); // <- force registering self provider

        $app->afterBootstrapping(LoadConfiguration::class, function (UnigaleApplication $app) {
            $cleanups = [];
            // Ensure proper error handling is available (omitted without debug for performance)
            if ($app->hasDebugModeEnabled()) {
                $cleanups[] = $app->registerProviderWithCleanup(FilesystemServiceProvider::class);
                $cleanups[] = $app->registerProviderWithCleanup(ViewServiceProvider::class);
            }

            // Preload database services required for eloquent-dependent module activation
            $cleanups[] = $app->registerProviderWithCleanup(DatabaseServiceProvider::class);

            Model::setConnectionResolver($app['db']);
            Model::unsetEventDispatcher(); // <- prevent booting callback to persist across requests

            // Bootstrap all enabled modules that provide custom bootstrapper (without events)
            $registry = $app->make(Modules::$accessor);
            foreach ($registry->enabled() as $module) {
                if ($module instanceof InjectBootstrappers) {
                    foreach ($module->bootstrappers() as $bootstrapper) {
                        $app->make($bootstrapper)->bootstrap($app);
                    }
                }
            }

            // Remove injected services from container
            array_walk($cleanups, 'call_user_func');
        });

        // Register enabled modules as ServiceProvider in the application
        $app->afterBootstrapping(RegisterProviders::class, function (Application $app) {
            /** @var ModulesRegistry $registry */
            $registry = $app->make(Modules::$accessor);
            $registry->registerInApp($app);
        });
    }
}
