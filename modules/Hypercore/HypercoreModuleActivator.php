<?php

declare(strict_types=1);

namespace UniGaleModules\Hypercore;

use Carbon\Laravel\ServiceProvider;
use Composer\InstalledVersions;
use Filament\View\PanelsRenderHook;
use UniGale\Foundation\Contracts\HasIntegrations;
use UniGale\Foundation\Contracts\Module;
use UniGale\Foundation\IntegrationsManager;
use UniGale\Foundation\ModuleIdentity;
use UniGaleModules\Administration\Administration;
use UniGaleModules\Administration\Pages\ManageModules;

class HypercoreModuleActivator extends ServiceProvider implements HasIntegrations, Module
{
    public function identifier(): string
    {
        return 'core::hypercore-activator';
    }

    public function identity(): ModuleIdentity
    {
        return ModuleIdentity::make(
            name: __('Hyper-Core Activator ⚡'),
            version: InstalledVersions::getPrettyVersion('unigale/framework')
              ?? InstalledVersions::getPrettyVersion('unigale/module-hypercore'),
            author: 'Core Team',
            description: __('Injected module from Hyper-Core to enable support for modules.')
        );
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/resource/views/activator', 'hypercore-activator');
    }

    public function integrations(IntegrationsManager $integrations): void
    {
        $integrations->forModule(
            identifier: 'core::administration',
            whenEnabled: static function () {
                Administration::configureUsing(function (Administration $administration) {
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
