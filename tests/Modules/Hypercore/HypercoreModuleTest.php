<?php

declare(strict_types=1);

use EpsicubeModules\Administration\Administration;
use EpsicubeModules\Administration\AdministrationModule;
use EpsicubeModules\Hypercore\Foundation\Bootstrap\BootstrapHypercore;
use EpsicubeModules\Hypercore\HypercoreModule;
use EpsicubeModules\Hypercore\Integrations\Administration\Resources\Tenants\TenantResource;
use Filament\Panel;

test('hypercore module declares expected metadata, bootstrapper and administration support hook', function () {
    $this->configureModules([
        HypercoreModule::class,
    ]);

    $module = $this->module('core::hypercore');
    $supportCheck = $module->supports->check();

    expect($module->identifier)->toBe('core::hypercore')
        ->and($module->version)->not->toBe('')
        ->and($module->identity->name)->toBe('Hypercore')
        ->and((new HypercoreModule($this->app))->bootstrappers())->toBe([BootstrapHypercore::class])
        ->and($module->supports->supports)->toHaveCount(1)
        ->and($supportCheck['valid'])->toBeFalse()
        ->and($supportCheck['results'][0]['name'])->toBe('Module core::administration')
        ->and($supportCheck['results'][0]['status'])->toBe('INVALID');
});

test('hypercore support registers tenant administration resources when administration is active', function () {
    $this->configureModules([
        AdministrationModule::class,
        HypercoreModule::class,
    ], [
        'core::administration' => true,
        'core::hypercore'      => true,
    ]);

    $panel = Panel::make()->id(Administration::$identifier);

    expect($panel->getResources())->toContain(TenantResource::class)
        ->and($panel->getResourceDirectories())->toContain(
            dirname(__DIR__, 3).'/modules/Hypercore/Integrations/Administration/Resources'
        )
        ->and($panel->getResourceNamespaces())->toContain(
            'EpsicubeModules\\Hypercore\\Integrations\\Administration\\Resources'
        );
});
