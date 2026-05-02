<?php

declare(strict_types=1);

use Epsicube\Foundation\EpsicubeApplication;
use Epsicube\Foundation\Managers\ModulesManager;

test('bootstraps the custom epsicube application', function () {
    $this->configureModules([]);

    expect($this->app)->toBeInstanceOf(EpsicubeApplication::class);
    expect($this->app->make(ModulesManager::class))->toBeInstanceOf(ModulesManager::class);
});
