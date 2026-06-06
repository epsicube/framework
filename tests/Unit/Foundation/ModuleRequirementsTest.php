<?php

declare(strict_types=1);

use Epsicube\Support\Enums\ModuleStatus;
use Epsicube\Support\Exceptions\CircularDependencyException;
use Epsicube\Support\Facades\Modules;
use Epsicube\Tests\Fixtures\Modules\AnalyticsModule;
use Epsicube\Tests\Fixtures\Modules\BillingModule;
use Epsicube\Tests\Fixtures\Modules\CircularAlphaModule;
use Epsicube\Tests\Fixtures\Modules\CircularBetaModule;
use Epsicube\Tests\Fixtures\Modules\CoreModule;
use Epsicube\Tests\Fixtures\Modules\MultiRequirementModule;
use Epsicube\Tests\Fixtures\Modules\SkippedRequirementModule;
use Epsicube\Tests\Fixtures\Modules\VersionLockedShopModule;

test('refuses to enable a module when a requirement fails', function () {
    $this->configureModules([
        BillingModule::class,
    ]);

    expect($this->moduleStatus('tests::billing'))->toBe(ModuleStatus::DISABLED);
    expect(Modules::canBeEnabled('tests::billing'))->toBeFalse();
});

test('treats skipped requirements as non blocking during bootstrap', function () {
    $this->configureModules([
        SkippedRequirementModule::class,
    ], [
        'tests::skipped-requirement' => true,
    ]);

    expect($this->moduleStatus('tests::skipped-requirement'))->toBe(ModuleStatus::ENABLED);
    expect(Modules::canBeDisabled('tests::skipped-requirement'))->toBeTrue();
});

test('records bootstrap logs for failed requirements', function () {
    $this->configureModules([
        BillingModule::class,
    ], [
        'tests::billing' => true,
    ]);

    expect($this->moduleStatus('tests::billing'))->toBe(ModuleStatus::ERROR);
    expect($this->bootstrapLogs('tests::billing'))
        ->toHaveCount(1)
        ->and($this->bootstrapLogs('tests::billing')[0])
        ->toContain('Requirement condition failed:')
        ->toContain('The dependency is unavailable.');
});

test('records all failed requirement logs when several requirements fail', function () {
    $this->configureModules([
        MultiRequirementModule::class,
    ], [
        'tests::multi-requirement' => true,
    ]);

    expect($this->moduleStatus('tests::multi-requirement'))->toBe(ModuleStatus::ERROR);
    expect($this->bootstrapLogs('tests::multi-requirement'))
        ->toHaveCount(2)
        ->and($this->bootstrapLogs('tests::multi-requirement')[0])->toContain('The first requirement failed.')
        ->and($this->bootstrapLogs('tests::multi-requirement')[1])->toContain('The second requirement failed.');
});

test('records bootstrap logs for missing module dependencies', function () {
    $this->configureModules([
        AnalyticsModule::class,
    ], [
        'tests::analytics' => true,
    ]);

    expect($this->moduleStatus('tests::analytics'))->toBe(ModuleStatus::ERROR);
    expect($this->bootstrapLogs('tests::analytics'))
        ->toHaveCount(1)
        ->and($this->bootstrapLogs('tests::analytics')[0])
        ->toContain('Dependency [tests::missing] is missing.');
});

test('records version mismatch logs when a dependency version is incompatible', function () {
    $this->configureModules([
        CoreModule::class,
        VersionLockedShopModule::class,
    ], [
        'tests::core'                => true,
        'tests::version-locked-shop' => true,
    ]);

    expect($this->moduleStatus('tests::version-locked-shop'))->toBe(ModuleStatus::ERROR);
    expect($this->bootstrapLogs('tests::version-locked-shop'))
        ->toHaveCount(1)
        ->and($this->bootstrapLogs('tests::version-locked-shop')[0])
        ->toContain('Version mismatch:')
        ->toContain('tests::core')
        ->toContain('does not match ^2.0.');
});

test('marks circular dependencies as not enableable', function () {
    $this->configureModules([
        CircularAlphaModule::class,
        CircularBetaModule::class,
    ]);

    expect(Modules::canBeEnabled('tests::circular-alpha'))->toBeFalse();
    expect(Modules::canBeEnabled('tests::circular-beta'))->toBeFalse();
});

test('throws during bootstrap when activated modules contain a circular dependency', function () {
    expect(fn () => $this->configureModules([
        CircularAlphaModule::class,
        CircularBetaModule::class,
    ], [
        'tests::circular-alpha' => true,
        'tests::circular-beta'  => true,
    ]))->toThrow(CircularDependencyException::class);
});
