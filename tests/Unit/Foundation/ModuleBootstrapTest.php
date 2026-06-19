<?php

declare(strict_types=1);

use Epsicube\Support\Enums\ModuleStatus;
use Epsicube\Support\Exceptions\BootstrapEpsicubeException;
use Epsicube\Tests\Fixtures\Modules\BootProbeModule;
use Epsicube\Tests\Fixtures\OptionsStores\NonFunctionalOptionsStore;

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

test('throws when options store remains in fallback mode', function () {
    $this->useOptionsStore(NonFunctionalOptionsStore::class);

    expect(fn () => $this->configureModules([
        BootProbeModule::class,
    ], [
        'tests::boot-probe' => true,
    ]))->toThrow(BootstrapEpsicubeException::class);
});
