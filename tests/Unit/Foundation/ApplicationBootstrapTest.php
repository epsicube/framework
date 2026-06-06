<?php

declare(strict_types=1);

use Epsicube\Foundation\Managers\ModulesManager;
use Epsicube\Foundation\Providers\EpsicubeServiceProvider;
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
