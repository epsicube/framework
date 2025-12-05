<?php

declare(strict_types=1);

namespace Epsicube\Foundation\Providers;

use Carbon\Laravel\ServiceProvider;
use Epsicube\Foundation\Console\Commands\ModulesDisableCommand;
use Epsicube\Foundation\Console\Commands\ModulesEnableCommand;
use Epsicube\Foundation\Console\Commands\ModulesStatusCommand;
use Epsicube\Foundation\Console\Commands\OptionsListCommand;
use Epsicube\Foundation\Console\Commands\OptionsSetCommand;
use Epsicube\Foundation\Console\Commands\OptionsUnsetCommand;
use Epsicube\Foundation\Console\Commands\ReloadCommand;
use Epsicube\Foundation\Console\Commands\WorkCommand;
use Epsicube\Foundation\Managers\EpsicubeManager;
use Epsicube\Foundation\Managers\ModulesManager;
use Epsicube\Foundation\Managers\OptionsManager;
use Epsicube\Foundation\Utilities\DatabaseOptionStore;
use Epsicube\Foundation\Utilities\FilesystemActivationDriver;
use Epsicube\Foundation\Utilities\Manifest;
use Epsicube\Support\Contracts\HasOptions;
use Epsicube\Support\Contracts\Module;
use Epsicube\Support\Facades\Epsicube;
use Epsicube\Support\Facades\Modules;
use Epsicube\Support\Facades\Options;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Env;

/**
 * This service provider is initialized during the application bootstrap phase.
 * Modules are loaded only after all core providers have been fully registered.
 */
class EpsicubeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('foundation-epsicube', function () {
            return new EpsicubeManager;
        });

        $this->app->singleton('foundation-manifest', function () {
            return new Manifest(
                files: new Filesystem,
                vendorPath: Env::get('COMPOSER_VENDOR_DIR') ?? base_path('/vendor'),
                manifestPath: app()->bootstrapPath('cache/epsicube.php')
            );
        });

        $this->app->singleton('foundation-modules', function () {
            $registry = new ModulesManager(
                new FilesystemActivationDriver(new Filesystem, $this->app->bootstrapPath('modules.php')),
            );

            $manifestModules = array_map(function (string $moduleClass) {
                /** @var class-string<Module> $moduleClass */
                return $this->app->make($moduleClass, ['app' => $this->app]);
            }, $this->app->get(\Epsicube\Support\Facades\Manifest::$accessor)->config('modules'));

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

        // TODO merge manifest into Epsicube
        // Keep alias binding to allow remapping and access to initial without triggering callback
        $this->app->alias('foundation-epsicube', Epsicube::$accessor);
        $this->app->alias('foundation-manifest', \Epsicube\Support\Facades\Manifest::$accessor);
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
            ReloadCommand::class,
            WorkCommand::class,
        ]);
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->reloads('epsicube:reload', 'epsicube');
    }
}
