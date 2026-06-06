<?php

declare(strict_types=1);

use Epsicube\Support\Enums\ModuleStatus;
use Epsicube\Tests\Fixtures\Modules\ConditionalSupportModule;
use Epsicube\Tests\Fixtures\Modules\DisabledStateSupportModule;
use Epsicube\Tests\Fixtures\Modules\InactiveStateSupportModule;
use Epsicube\Tests\Fixtures\Modules\SkippedSupportModule;
use Epsicube\Tests\Fixtures\Modules\TargetModule;

test('executes support callbacks only when their target module is active', function () {
    $this->configureModules([
        ConditionalSupportModule::class,
        TargetModule::class,
    ], [
        'tests::conditional-support' => true,
        'tests::target'              => true,
    ]);

    expect($this->app->bound('tests.supports.pass'))->toBeTrue();
    expect($this->app->make('tests.supports.pass'))->toBeTrue();
    expect($this->app->bound('tests.supports.fail'))->toBeFalse();
});

test('executes the support fail branch when the target module is unavailable', function () {
    $this->configureModules([
        ConditionalSupportModule::class,
    ], [
        'tests::conditional-support' => true,
    ]);

    expect($this->app->bound('tests.supports.pass'))->toBeFalse();
    expect($this->app->bound('tests.supports.fail'))->toBeTrue();
    expect($this->app->make('tests.supports.fail'))->toBeTrue();
});

test('does not evaluate supports for modules that are themselves disabled', function () {
    $this->configureModules([
        ConditionalSupportModule::class,
        TargetModule::class,
    ], [
        'tests::target' => true,
    ]);

    expect($this->moduleStatus('tests::conditional-support'))->toBe(ModuleStatus::DISABLED);
    expect($this->app->bound('tests.supports.pass'))->toBeFalse();
    expect($this->app->bound('tests.supports.fail'))->toBeFalse();
});

test('executes skipped support callbacks when the condition is skipped', function () {
    $this->configureModules([
        SkippedSupportModule::class,
    ], [
        'tests::skipped-support' => true,
    ]);

    expect($this->app->bound('tests.supports.skipped'))->toBeTrue();
    expect($this->app->make('tests.supports.skipped'))->toBeTrue();
});

test('support condition active fails when the target module is present but disabled', function () {
    $this->configureModules([
        ConditionalSupportModule::class,
        TargetModule::class,
    ], [
        'tests::conditional-support' => true,
    ]);

    expect($this->moduleStatus('tests::target'))->toBe(ModuleStatus::DISABLED);
    expect($this->app->bound('tests.supports.pass'))->toBeFalse();
    expect($this->app->bound('tests.supports.fail'))->toBeTrue();
});

test('support condition disabled passes when the target module is installed but disabled', function () {
    $this->configureModules([
        DisabledStateSupportModule::class,
        TargetModule::class,
    ], [
        'tests::disabled-state-support' => true,
    ]);

    expect($this->moduleStatus('tests::target'))->toBe(ModuleStatus::DISABLED);
    expect($this->app->bound('tests.supports.disabled-state.pass'))->toBeTrue();
    expect($this->app->bound('tests.supports.disabled-state.fail'))->toBeFalse();
});

test('support condition inactive passes when the target module is absent', function () {
    $this->configureModules([
        InactiveStateSupportModule::class,
    ], [
        'tests::inactive-state-support' => true,
    ]);

    expect($this->app->bound('tests.supports.inactive-state.pass'))->toBeTrue();
    expect($this->app->bound('tests.supports.inactive-state.fail'))->toBeFalse();
});

test('support condition inactive passes when the target module is installed but disabled', function () {
    $this->configureModules([
        InactiveStateSupportModule::class,
        TargetModule::class,
    ], [
        'tests::inactive-state-support' => true,
    ]);

    expect($this->moduleStatus('tests::target'))->toBe(ModuleStatus::DISABLED);
    expect($this->app->bound('tests.supports.inactive-state.pass'))->toBeTrue();
    expect($this->app->bound('tests.supports.inactive-state.fail'))->toBeFalse();
});
