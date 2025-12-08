<?php

declare(strict_types=1);

namespace EpsicubeModules\Administration;

use Carbon\Laravel\ServiceProvider;
use Composer\InstalledVersions;
use Epsicube\Schemas\Properties\BooleanProperty;
use Epsicube\Schemas\Properties\StringProperty;
use Epsicube\Schemas\Schema;
use Epsicube\Support\Contracts\HasOptions;
use Epsicube\Support\Contracts\Module;
use Epsicube\Support\ModuleIdentity;
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

    public function options(Schema $schema): void
    {
        $schema->append([
            'enable-modules-manager' => BooleanProperty::make()->title('Enable Modules Manager')->default(true),
            'brand-name'             => StringProperty::make()->title('Brand Name')->default(fn () => config('app.name')),
            'spa'                    => BooleanProperty::make()->title('Single-Page-Application')->default(true),
            'top-navigation'         => BooleanProperty::make()->title('Top Navigation')->default(false),
        ]);
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
