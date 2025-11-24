<?php

declare(strict_types=1);

namespace UniGale\Foundation;

use Carbon\Laravel\ServiceProvider;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Env;
use UniGale\Foundation\Activation\FilesystemActivationDriver;
use UniGale\Foundation\Concerns\Module;
use UniGale\Foundation\Console\Commands\ModulesDisableCommand;
use UniGale\Foundation\Console\Commands\ModulesEnableCommand;
use UniGale\Foundation\Console\Commands\ModulesListCommand;
use UniGale\Foundation\Facades\Modules;
use UniGale\Foundation\Registries\ModulesRegistry;

/**
 * This service provider is initialized during the application bootstrap phase.
 * Modules are loaded only after all core providers have been fully registered.
 *
 * The UnigaleManifest::class and Modules::$accessor bindings are authoritative
 * and cannot be overridden by any other provider. If you need to introduce
 * an alternative mechanism, use a custom application bootstrapper.
 */
class UnigaleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('unigale-manifest', function () {
            return new UnigaleManifest(
                files: new Filesystem,
                vendorPath: Env::get('COMPOSER_VENDOR_DIR') ?? base_path('/vendor'),
                manifestPath: app()->bootstrapPath('cache/unigale.php')
            );
        });

        $this->app->alias('unigale-manifest', UnigaleManifest::class);

        $this->app->singleton('modules', function () {
            $registry = new ModulesRegistry(
                new FilesystemActivationDriver(new Filesystem, $this->app->bootstrapPath('modules.php'))
            );

            $manifestModules = array_map(function (string $moduleClass) {
                /** @var class-string<Module> $moduleClass */
                return $moduleClass::make();
            }, $this->app->get(UnigaleManifest::class)->modules());

            $registry->register(...$manifestModules);

            return $registry;
        });

        // Keep alias binding to allow remapping and access to initial without triggering callback
        $this->app->alias('modules', Modules::$accessor);
    }

    public function boot(): void
    {
        $this->commands([
            ModulesListCommand::class,
            ModulesEnableCommand::class,
            ModulesDisableCommand::class,
        ]);
    }
}
