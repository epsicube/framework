<?php

declare(strict_types=1);

use Epsicube\Foundation\Managers\ModulesManager;
use Epsicube\Foundation\Providers\EpsicubeServiceProvider;
use Epsicube\Tests\Fixtures\Modules\PreventDiscoveredProviderModule;
use Epsicube\Tests\Fixtures\Providers\DiscoveredProbeServiceProvider;
use Illuminate\Contracts\Foundation\Application;

test('ensure EpsicubeServiceProvider is registered into application', function () {
    $this->configureModules([]);
    expect($this->app)->toBeInstanceOf(Application::class)
        ->and($this->app->getLoadedProviders())->toHaveKey(EpsicubeServiceProvider::class);
});

test('ensure ModulesManager is registered into application', function () {
    $this->configureModules([]);
    expect($this->app->make(ModulesManager::class))->toBeInstanceOf(ModulesManager::class);
});

test('preventProviders excludes discovered Laravel providers during bootstrap', function () {
    $this->writePhpFile($this->workspace.'/bootstrap/cache/packages.php', [
        'tests/discovered-provider' => [
            'providers' => [
                DiscoveredProbeServiceProvider::class,
            ],
        ],
    ]);

    $this->configureModules([
        PreventDiscoveredProviderModule::class,
    ], [
        'tests::prevent-discovered-provider' => true,
    ]);

    expect($this->app->bound('tests.discovered-provider.registered'))->toBeFalse()
        ->and($this->app->getLoadedProviders())->not->toHaveKey(DiscoveredProbeServiceProvider::class);
});
