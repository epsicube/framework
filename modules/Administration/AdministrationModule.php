<?php

declare(strict_types=1);

namespace UniGaleModules\Administration;

use Composer\InstalledVersions;
use Filament\FilamentServiceProvider;
use Filament\PanelRegistry;
use UniGale\Foundation\Concerns\CoreModule;
use UniGale\Foundation\Contracts\HasOptions;
use UniGale\Foundation\Options\OptionsDefinition;

class AdministrationModule extends CoreModule implements HasOptions
{
    protected function coreIdentifier(): string
    {
        return 'administration';
    }

    public function name(): string
    {
        return __('Administration');
    }

    public function version(): string
    {
        return
            InstalledVersions::getPrettyVersion('unigale/framework')
            ?? InstalledVersions::getPrettyVersion('unigale/module-administration');
    }

    public function description(): ?string
    {
        return __('Provides administrative tools and management features for the system.');
    }

    public function options(OptionsDefinition $options): void
    {
        $options->add(
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
