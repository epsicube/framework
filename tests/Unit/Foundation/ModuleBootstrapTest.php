<?php

declare(strict_types=1);

use Epsicube\Support\Enums\ModuleStatus;
use Epsicube\Tests\Fixtures\Modules\BootProbeModule;

test('loads module providers only for enabled modules', function () {
    $this->configureModules([
        BootProbeModule::class,
    ], [
        'tests::boot-probe' => true,
    ]);

    expect($this->app->make('tests.provider.events'))->toBe([
        'register',
        'boot',
    ]);
});

test('does not load providers for disabled modules', function () {
    $this->configureModules([
        BootProbeModule::class,
    ]);

    expect($this->app->bound('tests.provider.events'))->toBeFalse();
    expect($this->moduleStatus('tests::boot-probe'))->toBe(ModuleStatus::DISABLED);
});
