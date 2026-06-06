<?php

declare(strict_types=1);

use EpsicubeModules\AccountsManager\AccountsManagerModule;
use EpsicubeModules\Administration\Administration;
use EpsicubeModules\Administration\AdministrationModule;
use Filament\Panel;

test('accounts manager module declares expected metadata', function () {
    $this->configureModules([
        AccountsManagerModule::class,
    ]);

    $module = $this->module('core::accounts-manager');

    expect($module->identifier)->toBe('core::accounts-manager')
        ->and($module->identity->name)->toBe('Accounts Manager')
        ->and($module->supports->supports)->toHaveCount(2)
        ->and($module->options->properties())->toBe([]);
});

test('accounts manager registers the accounts auth stack when enabled', function () {
    $this->configureModules([
        AccountsManagerModule::class,
    ], [
        'core::accounts-manager' => true,
    ]);

    expect(config('auth.guards.accounts'))->toBe([
        'driver'   => 'session',
        'provider' => 'accounts',
    ])->and(config('auth.providers.accounts'))->toBe([
        'driver' => 'eloquent',
        'model'  => 'EpsicubeModules\\AccountsManager\\Models\\Account',
    ])->and(config('auth.passwords.accounts'))->toBe([
        'provider' => 'accounts',
        'table'    => 'account_password_reset_tokens',
        'expire'   => 60,
        'throttle' => 60,
    ]);
});
test('accounts manager administration support configures the administration panel for accounts auth', function () {
    $this->configureModules([
        AdministrationModule::class,
        AccountsManagerModule::class,
    ], [
        'core::administration'   => true,
        'core::accounts-manager' => true,
    ]);

    $panel = Panel::make()
        ->id(Administration::$identifier)
        ->configure();

    $expectedPath = realpath(__DIR__.'/../../../modules/AccountsManager/Integrations/Administration/Resources');

    $resourceDirs = array_map(fn ($path) => realpath($path), $panel->getResourceDirectories());

    expect($panel->hasLogin())->toBeTrue()
        ->and($panel->getAuthGuard())->toBe('accounts')
        ->and($resourceDirs)->toContain($expectedPath)
        ->and($panel->getResourceNamespaces())->toContain(
            'EpsicubeModules\\AccountsManager\\Integrations\\Administration\\Resources'
        );
});
