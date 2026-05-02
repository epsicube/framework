<?php

declare(strict_types=1);

use Epsicube\Support\Facades\Options;
use Epsicube\Tests\Modules\Administration\Fixtures\AdministrationProbeModule;
use EpsicubeModules\Administration\Administration;
use EpsicubeModules\Administration\AdministrationModule;
use EpsicubeModules\Administration\AdministrationOptions;
use EpsicubeModules\Administration\Pages\ManageModules;
use Filament\Panel;

function administrationProbeResourcesPath(): string
{
    return __DIR__.'/Fixtures/AdministrationProbe/Resources';
}

test('administration options are resolved through the module helper api', function () {
    $this->configureModules([
        AdministrationModule::class,
    ], [
        'core::administration' => true,
    ]);

    expect(AdministrationOptions::all())->toBe([
        'enable-modules-manager' => true,
        'brand-name'             => null,
        'spa'                    => true,
        'top-navigation'         => true,
        'application-navigation' => true,
        'path'                   => '/admin',
        'domain'                 => null,
    ]);

    Options::set('core::administration', 'enable-modules-manager', false);
    Options::set('core::administration', 'brand-name', 'Acme Backoffice');
    Options::set('core::administration', 'spa', false);
    Options::set('core::administration', 'top-navigation', false);
    Options::set('core::administration', 'application-navigation', false);
    Options::set('core::administration', 'path', '/backoffice');
    Options::set('core::administration', 'domain', 'admin.example.test');

    expect(AdministrationOptions::isModulesManagerEnabled())->toBeFalse()
        ->and(AdministrationOptions::brandName())->toBe('Acme Backoffice')
        ->and(AdministrationOptions::isSpaEnabled())->toBeFalse()
        ->and(AdministrationOptions::hasTopNavigation())->toBeFalse()
        ->and(AdministrationOptions::hasApplicationNavigation())->toBeFalse()
        ->and(AdministrationOptions::path())->toBe('/backoffice')
        ->and(AdministrationOptions::domain())->toBe('admin.example.test')
        ->and(ManageModules::canAccess())->toBeFalse();
});

test('external modules can inject administration panel configuration through supports and configureUsing', function () {
    $this->configureModules([
        AdministrationModule::class,
        AdministrationProbeModule::class,
    ], [
        'core::administration'        => true,
        'tests::administration-probe' => true,
    ]);

    $panel = Administration::configure(Panel::make());

    expect($panel->getResourceDirectories())->toContain(administrationProbeResourcesPath())
        ->and($panel->getResourceNamespaces())->toContain(
            'Epsicube\\Tests\\Modules\\Administration\\Fixtures\\AdministrationProbe\\Resources'
        );
});
