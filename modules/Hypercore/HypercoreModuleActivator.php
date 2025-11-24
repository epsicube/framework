<?php

declare(strict_types=1);

namespace UniGaleModules\Hypercore;

use Composer\InstalledVersions;
use Filament\View\PanelsRenderHook;
use UniGale\Foundation\Concerns\CoreModule;
use UniGale\Foundation\Contracts\HasIntegrations;
use UniGale\Foundation\IntegrationsManager;
use UniGaleModules\Administration\Administration;
use UniGaleModules\Administration\Pages\ManageModules;

class HypercoreModuleActivator extends CoreModule implements HasIntegrations
{
    protected function coreIdentifier(): string
    {
        return 'hypercore-activator';
    }

    public function name(): string
    {
        return __('Hyper-Core Activator ⚡');
    }

    public function description(): ?string
    {
        return __('Injected module from Hyper-Core to enable support for modules.');
    }

    public function version(): string
    {
        return InstalledVersions::getPrettyVersion('unigale/framework')
            ?? InstalledVersions::getPrettyVersion('unigale/module-hypercore');
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
