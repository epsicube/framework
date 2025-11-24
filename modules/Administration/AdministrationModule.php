<?php

declare(strict_types=1);

namespace UniGaleModules\Administration;

use Composer\InstalledVersions;
use Filament\FilamentServiceProvider;
use Filament\PanelRegistry;
use UniGale\Foundation\Concerns\CoreModule;

class AdministrationModule extends CoreModule
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
