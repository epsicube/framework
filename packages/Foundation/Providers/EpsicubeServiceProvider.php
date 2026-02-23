<?php

declare(strict_types=1);

namespace Epsicube\Foundation\Providers;

use Carbon\Laravel\ServiceProvider;
use Composer\InstalledVersions;
use Epsicube\Foundation\Console\Commands\CacheCommand;
use Epsicube\Foundation\Console\Commands\ClearCommand;
use Epsicube\Foundation\Console\Commands\InstallCommand;
use Epsicube\Foundation\Console\Commands\MakeModuleCommand;
use Epsicube\Foundation\Console\Commands\ModulesDisableCommand;
use Epsicube\Foundation\Console\Commands\ModulesEnableCommand;
use Epsicube\Foundation\Console\Commands\ModuleShowCommand;
use Epsicube\Foundation\Console\Commands\ModulesStatusCommand;
use Epsicube\Foundation\Console\Commands\OptionsListCommand;
use Epsicube\Foundation\Console\Commands\OptionsSetCommand;
use Epsicube\Foundation\Console\Commands\OptionsUnsetCommand;
use Epsicube\Foundation\Console\Commands\ReloadCommand;
use Epsicube\Foundation\Console\Commands\TerminateCommand;
use Epsicube\Foundation\Console\Commands\WorkCommand;
use Epsicube\Foundation\Listeners\FoundationSubscriber;
use Epsicube\Foundation\Managers\EpsicubeManager;
use Epsicube\Foundation\Managers\ModulesManager;
use Epsicube\Foundation\Managers\OptionsManager;
use Epsicube\Foundation\Proxy\NumberTranslator;
use Epsicube\Foundation\Utilities\DatabaseOptionStore;
use Epsicube\Foundation\Utilities\FilesystemActivationDriver;
use Epsicube\Foundation\Utilities\Manifest;
use Epsicube\Support\Facades\Epsicube;
use Epsicube\Support\Facades\Modules;
use Epsicube\Support\Facades\Options;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Event;
use RuntimeException;
use Throwable;

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
                new FilesystemActivationDriver(new Filesystem, $this->app->bootstrapPath('modules-activation.php')),
            );

            // Load modules from manifest (composer)
            try {
                $manifestModules = $this->app->get(\Epsicube\Support\Facades\Manifest::$accessor)->config('modules');
            } catch (Throwable $e) {
                throw new RuntimeException('Failed to load modules from composer manifest.', $e->getCode(), $e);
            }

            foreach ($manifestModules as $module) {
                try {
                    $registry->register($this->app->make($module, ['app' => $this->app]));
                } catch (Throwable $e) {
                    $identifier = is_string($module) ? $module : get_debug_type($module);
                    $wrappedError = new RuntimeException("Failed to register module '{$identifier}' from composer manifest.", $e->getCode(), $e);
                    if ($this->app->isLocal()) {
                        throw $wrappedError;
                    }

                    report($wrappedError);
                }
            }

            // Load modules from bootstrap/modules.php
            $file = new Filesystem;
            $modulesPath = $this->app->bootstrapPath('modules.php');
            if (! $file->exists($modulesPath)) {
                return $registry;
            }
            try {
                $modules = $file->getRequire($modulesPath);
            } catch (Throwable $e) {
                throw new RuntimeException("Failed to load modules from 'bootstrap/modules.php' file.", $e->getCode(), $e);
            }

            foreach ($modules as $module) {
                try {
                    $registry->register($this->app->make($module, ['app' => $this->app]));
                } catch (Throwable $e) {
                    $identifier = is_string($module) ? $module : get_debug_type($module);
                    $wrappedError = new RuntimeException("Failed to register module '{$identifier}' from 'bootstrap/modules.php'.", $e->getCode(), $e);
                    if ($this->app->isLocal()) {
                        throw $wrappedError;
                    }

                    report($wrappedError);
                }
            }

            return $registry;
        });

        $this->app->singleton('foundation-options', function () {
            return new OptionsManager(new DatabaseOptionStore);
        });

        // TODO merge manifest into Epsicube
        // Keep alias binding to allow remapping and access to initial without triggering callback
        $this->app->alias('foundation-epsicube', Epsicube::$accessor);
        $this->app->alias('foundation-manifest', \Epsicube\Support\Facades\Manifest::$accessor);
        $this->app->alias('foundation-options', Options::$accessor);
        $this->app->alias('foundation-modules', Modules::$accessor);

        // Extend translator to handle number formatting
        $this->app->extend('translator', function (Translator $translator) {
            return new NumberTranslator($translator);
        });
    }

    public function boot(): void
    {
        Event::subscribe(FoundationSubscriber::class);

        $this->commands([
            CacheCommand::class,
            ClearCommand::class,
            InstallCommand::class,
            MakeModuleCommand::class,
            ModulesStatusCommand::class,
            ModulesEnableCommand::class,
            ModulesDisableCommand::class,
            ModuleShowCommand::class,
            OptionsListCommand::class,
            OptionsSetCommand::class,
            OptionsUnsetCommand::class,
            ReloadCommand::class,
            TerminateCommand::class,
            WorkCommand::class,
        ]);
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->reloads('epsicube:reload', 'epsicube');
        $this->optimizes('epsicube:cache', 'epsicube:clear', 'epsicube');
        AboutCommand::add('Epsicube', [
            'Version' => InstalledVersions::getPrettyVersion('epsicube/framework')
                ?? InstalledVersions::getPrettyVersion('epsicube/foundation'),
        ]);
    }
}
