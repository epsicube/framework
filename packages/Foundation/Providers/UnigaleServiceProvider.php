<?php

declare(strict_types=1);

namespace UniGale\Foundation\Providers;

use Carbon\Laravel\ServiceProvider;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Env;
use UniGale\Foundation\Console\Commands\ModulesDisableCommand;
use UniGale\Foundation\Console\Commands\ModulesEnableCommand;
use UniGale\Foundation\Console\Commands\ModulesStatusCommand;
use UniGale\Foundation\Console\Commands\OptionsListCommand;
use UniGale\Foundation\Console\Commands\OptionsSetCommand;
use UniGale\Foundation\Console\Commands\OptionsUnsetCommand;
use UniGale\Foundation\Managers\ModulesManager;
use UniGale\Foundation\Managers\OptionsManager;
use UniGale\Foundation\Utilities\DatabaseOptionStore;
use UniGale\Foundation\Utilities\FilesystemActivationDriver;
use UniGale\Foundation\Utilities\UnigaleManifest;
use UniGale\Support\Contracts\HasOptions;
use UniGale\Support\Contracts\Module;
use UniGale\Support\Facades\Manifest;
use UniGale\Support\Facades\Modules;
use UniGale\Support\Facades\Options;

/**
 * This service provider is initialized during the application bootstrap phase.
 * Modules are loaded only after all core providers have been fully registered.
 */
class UnigaleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('foundation-manifest', function () {
            return new UnigaleManifest(
                files: new Filesystem,
                vendorPath: Env::get('COMPOSER_VENDOR_DIR') ?? base_path('/vendor'),
                manifestPath: app()->bootstrapPath('cache/unigale.php')
            );
        });

        $this->app->singleton('foundation-modules', function () {
            $registry = new ModulesManager(
                new FilesystemActivationDriver(new Filesystem, $this->app->bootstrapPath('modules.php')),
            );

            $manifestModules = array_map(function (string $moduleClass) {
                /** @var class-string<Module> $moduleClass */
                return $this->app->make($moduleClass, ['app' => $this->app]);
            }, $this->app->get(Manifest::$accessor)->config('modules'));

            $registry->register(...$manifestModules);

            return $registry;
        });

        $this->app->singleton('foundation-options', function () {
            $manager = new OptionsManager(new DatabaseOptionStore);

            foreach ($this->app->get(Modules::$accessor)->enabled() as $moduleIdentifier => $module) {
                if ($module instanceof HasOptions) {
                    $manager->registerDefinition($moduleIdentifier, $module->options());
                }
            }

            return $manager;
        });
        // Keep alias binding to allow remapping and access to initial without triggering callback
        $this->app->alias('foundation-manifest', Manifest::$accessor);
        $this->app->alias('foundation-options', Options::$accessor);
        $this->app->alias('foundation-modules', Modules::$accessor);
    }

    public function boot(): void
    {
        $this->commands([
            ModulesStatusCommand::class,
            ModulesEnableCommand::class,
            ModulesDisableCommand::class,
            OptionsListCommand::class,
            OptionsSetCommand::class,
            OptionsUnsetCommand::class,
        ]);
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
    }
}
