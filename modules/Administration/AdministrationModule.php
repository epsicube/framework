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
use Throwable;

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
            'enable-modules-manager' => BooleanProperty::make()
                ->title('Enable Modules Manager')
                ->description('Activates the integrated module management system, allowing you to enable, disable, or configure application modules.')
                ->optional()
                ->default(true),

            'brand-name' => StringProperty::make()
                ->title('Brand Name')
                ->description('Specifies the display name used across the administration interface.')
                ->optional()
                ->default(fn () => config('app.name')),

            'spa' => BooleanProperty::make()
                ->title('Single-Page Application')
                ->description('Enables SPA mode for enhanced navigation performance and reduced page reloads.')
                ->optional()
                ->default(true),

            'top-navigation' => BooleanProperty::make()
                ->title('Top Navigation')
                ->description('Displays the primary navigation bar at the top instead of the sidebar.')
                ->optional()
                ->default(false),

            'path' => StringProperty::make()
                ->title('Path')
                ->description('Defines the subpath under which the administration panel is served, e.g., /admin or /dashboard.')
                ->optional()
                ->default('/epsicube'),

            'domain' => StringProperty::make()
                ->title('Domain')
                ->description('Restricts the administration panel to a specific domain. Leave empty to allow access from any domain.')
                ->nullable()
                ->optional()
                ->default(null),
        ]);
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
    }
}
