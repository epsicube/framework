<?php

declare(strict_types=1);

namespace EpsicubeModules\Administration;

use Carbon\Laravel\ServiceProvider;
use Composer\InstalledVersions;
use Epsicube\Support\Contracts\IsModule;
use Epsicube\Support\Facades\Epsicube;
use Epsicube\Support\Modules\Identity;
use Epsicube\Support\Modules\Module;
use EpsicubeModules\Administration\View\Components\ApplicationGroupIcon;
use Filament\Facades\Filament;
use Filament\Panel;

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
            ->options(AdministrationOptions::configure(...));
    }

    public function register(): void
    {
        Filament::registerPanel(static fn (): Panel => Administration::configure(Panel::make()));
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
