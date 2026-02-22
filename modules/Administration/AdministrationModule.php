<?php

declare(strict_types=1);

namespace EpsicubeModules\Administration;

use Carbon\Laravel\ServiceProvider;
use Composer\InstalledVersions;
use Epsicube\Schemas\Schema;
use Epsicube\Support\Contracts\IsModule;
use Epsicube\Support\Facades\Epsicube;
use Epsicube\Support\Modules\Identity;
use Epsicube\Support\Modules\Module;
use EpsicubeModules\Administration\View\Components\ApplicationGroupIcon;
use Filament\FilamentServiceProvider;
use Filament\PanelRegistry;
use Throwable;

class AdministrationModule extends ServiceProvider implements IsModule
{
    public function module(): Module
    {
        return Module::make(
            identifier: 'core::administration',
            version: InstalledVersions::getVersion('epsicube/framework')
            ?? InstalledVersions::getVersion('epsicube/module-administration')
        )
            ->providers(static::class)
            ->identity(fn (Identity $identity) => $identity
                ->name(__('Administration'))
                ->author('Core Team')
                ->description(__('Provides administrative tools and management features for the system.'))
            )
            ->options(fn (Schema $schema) => $schema->append(
                AdministrationOptions::definition()
            ));
    }

    public function register(): void
    {
        $this->app->booted(function (): void {

            try {
                $callback = function (PanelRegistry $registry): void {
                    $registry->register(Administration::make()->id('epsicube-administration'));
                };

                $this->app->resolving(PanelRegistry::class, $callback);
                if ($this->app->resolved(PanelRegistry::class)) {
                    $callback(app(PanelRegistry::class));

                    // Force routes to register because filament cannot handle that
                    (function (): void {
                        /** @var FilamentServiceProvider $this */
                        $this->bootPackageRoutes();
                    })->call($this->app->getProvider(FilamentServiceProvider::class));
                }
            } catch (Throwable $e) {
                report($e);
            }
        });
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/resources/views', 'epsicube-administration');
        $this->loadViewComponentsAs('epsicube-administration', [
            ApplicationGroupIcon::class,
        ]);
        Epsicube::addInstallCommand('core::administration', 'filament:assets');
    }
}
