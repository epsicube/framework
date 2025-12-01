<?php

declare(strict_types=1);

namespace UniGaleModules\Administration;

use Carbon\Laravel\ServiceProvider;
use Composer\InstalledVersions;
use Filament\FilamentServiceProvider;
use Filament\PanelRegistry;
use UniGale\Support\Contracts\HasOptions;
use UniGale\Support\Contracts\Module;
use UniGale\Support\ModuleIdentity;
use UniGale\Support\OptionsDefinition;

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
            version: InstalledVersions::getPrettyVersion('unigale/framework')
            ?? InstalledVersions::getPrettyVersion('unigale/module-administration'),
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
                $registry->register(Administration::make()->id('unigale-administration'));
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
        $this->loadViewsFrom(__DIR__.'/resources/views', 'unigale-administration');
    }
}
