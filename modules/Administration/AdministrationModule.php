<?php

declare(strict_types=1);

namespace EpsicubeModules\Administration;

use Carbon\Laravel\ServiceProvider;
use Composer\InstalledVersions;
use Epsicube\Support\Contracts\HasOptions;
use Epsicube\Support\Contracts\Module;
use Epsicube\Support\ModuleIdentity;
use Epsicube\Support\OptionsDefinition;
use Filament\FilamentServiceProvider;
use Filament\PanelRegistry;

class AdministrationModule extends ServiceProvider implements HasOptions, Module
{
    public function identifier(): string
    {
        return 'core::administration';
    }

    public function identity(): ModuleIdentity
    {
        return ModuleIdentity::make(
            name: __('Administration'),
            version: InstalledVersions::getPrettyVersion('epsicube/framework')
            ?? InstalledVersions::getPrettyVersion('epsicube/module-administration'),
            author: 'Core Team',
            description: __('Provides administrative tools and management features for the system.')
        );
    }

    public function options(): OptionsDefinition
    {
        return OptionsDefinition::make()->add(
            key: 'enable-modules-manager',
            type: 'boolean', // <- todo type management
            default: true
        )->add(
            key: 'brand-name',
            type: 'string', // <- todo type management
            default: fn () => config('app.name')
        )->add(
            key: 'spa',
            type: 'boolean', // <- todo type management
            default: false
        )->add(
            key: 'top-navigation',
            type: 'boolean', // <- todo type management
            default: true
        );
    }

    public function register(): void
    {
        $this->app->booted(function () {
            $callback = function (PanelRegistry $registry) {
                $registry->register(Administration::make()->id('epsicube-administration'));
            };

            $this->app->resolving(PanelRegistry::class, $callback);
            if ($this->app->resolved(PanelRegistry::class)) {
                $callback(app(PanelRegistry::class));

                // Force routes to register because filament cannot handle that
                (function () {
                    /** @var FilamentServiceProvider $this */
                    $this->bootPackageRoutes();
                })->call($this->app->getProvider(FilamentServiceProvider::class));
            }
        });
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/resources/views', 'epsicube-administration');
    }
}
