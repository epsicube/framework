<?php

declare(strict_types=1);

namespace EpsicubeModules\Hypercore;

use Carbon\Laravel\ServiceProvider;
use Composer\InstalledVersions;
use Epsicube\Support\Contracts\HasIntegrations;
use Epsicube\Support\Contracts\IsModule;
use Epsicube\Support\Integrations;
use Epsicube\Support\ModuleIdentity;
use EpsicubeModules\Administration\Administration;
use EpsicubeModules\Administration\Pages\ManageModules;
use Filament\Panel;
use Filament\View\PanelsRenderHook;

class HypercoreIsModuleActivator extends ServiceProvider implements HasIntegrations, IsModule
{
    public function identifier(): string
    {
        return 'core::hypercore-activator';
    }

    public function identity(): ModuleIdentity
    {
        return ModuleIdentity::make(
            name: __('Hyper-Core Activator ⚡'),
            version: InstalledVersions::getPrettyVersion('epsicube/framework')
              ?? InstalledVersions::getPrettyVersion('epsicube/module-hypercore'),
            author: 'Core Team',
            description: __('Injected module from Hyper-Core to enable support for modules.')
        );
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/resource/views/activator', 'hypercore-activator');
    }

    public function integrations(): Integrations
    {
        return Integrations::make()->forModule(
            identifier: 'core::administration',
            whenEnabled: static function (): void {
                Administration::configureUsing(function (Panel $administration): void {
                    $administration->renderHook(
                        PanelsRenderHook::PAGE_START,
                        fn () => view('hypercore-activator::banner'),
                        scopes: ManageModules::class,
                    );
                });
            }
        );
    }
}
