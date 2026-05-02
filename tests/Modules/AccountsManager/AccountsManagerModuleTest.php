<?php

declare(strict_types=1);

use EpsicubeModules\AccountsManager\AccountsManagerModule;
use EpsicubeModules\Administration\Administration;
use EpsicubeModules\Administration\AdministrationModule;
use Filament\Http\Middleware\Authenticate;
use Filament\Panel;

function accountsManagerModulePath(string $path = ''): string
{
    $base = dirname(__DIR__, 3).'/modules/AccountsManager';

    return $path === '' ? $base : $base.'/'.mb_ltrim($path, '/');
}

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

    expect($panel->hasLogin())->toBeTrue()
        ->and($panel->hasPasswordReset())->toBeTrue()
        ->and($panel->hasRegistration())->toBeTrue()
        ->and($panel->hasProfile())->toBeTrue()
        ->and($panel->getAuthGuard())->toBe('accounts')
        ->and($panel->getAuthPasswordBroker())->toBe('accounts')
        ->and($panel->getAuthMiddleware())->toContain(Authenticate::class)
        ->and($panel->getResourceDirectories())->toContain(
            accountsManagerModulePath('Integrations/Administration/Resources')
        )
        ->and($panel->getResourceNamespaces())->toContain(
            'EpsicubeModules\\AccountsManager\\Integrations\\Administration\\Resources'
        );
});
