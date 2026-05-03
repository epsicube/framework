<?php

declare(strict_types=1);

use Epsicube\Foundation\Managers\ModulesManager;
use Epsicube\Support\Enums\ModuleStatus;
use Epsicube\Support\Facades\Modules;
use Epsicube\Tests\Fixtures\Modules\AnalyticsModule;
use Epsicube\Tests\Fixtures\Modules\BlogModule;
use Epsicube\Tests\Fixtures\Modules\CoreModule;
use Epsicube\Tests\Fixtures\Modules\ShopModule;

test('persists activation state when enabling a disabled module', function () {
    $this->configureModules([
        BlogModule::class,
    ]);

    expect($this->moduleStatus('tests::blog'))->toBe(ModuleStatus::DISABLED);
    expect(Modules::canBeEnabled('tests::blog'))->toBeTrue();

    Modules::activationPlan()($this->module('tests::blog'));

    expect($this->moduleStatus('tests::blog'))->toBe(ModuleStatus::ENABLED);
    expect($this->readActivationState())->toBe([
        'tests::blog' => true,
    ]);
});

test('injects foundation tasks into the activation plan', function () {
    $this->configureModules([
        BlogModule::class,
    ]);

    $labels = array_map(fn (array $task) => $task['label'], Modules::activationPlan()->getTasks());

    expect($labels)->toBe([
        'Ensure module can be activated',
        'Mark module as enabled',
        'Clear cache',
        'Run migrations',
        'Terminate worker',
    ]);
});

test('persists activation state when disabling an enabled module', function () {
    $this->configureModules([
        BlogModule::class,
    ], [
        'tests::blog' => true,
    ]);

    expect($this->moduleStatus('tests::blog'))->toBe(ModuleStatus::ENABLED);
    expect(Modules::canBeDisabled('tests::blog'))->toBeTrue();

    Modules::deactivationPlan()($this->module('tests::blog'));

    expect($this->moduleStatus('tests::blog'))->toBe(ModuleStatus::DISABLED);
    expect($this->readActivationState())->toBe([
        'tests::blog' => false,
    ]);
});

test('injects foundation tasks into the deactivation plan', function () {
    $this->configureModules([
        BlogModule::class,
    ], [
        'tests::blog' => true,
    ]);

    $labels = array_map(fn (array $task) => $task['label'], Modules::deactivationPlan()->getTasks());

    expect($labels)->toBe([
        'Ensure module can be deactivated',
        'Mark module as disabled',
        'Clear cache',
        'Terminate worker',
    ]);
});

test('refuses to disable a module while another enabled module depends on it', function () {
    $this->configureModules([
        CoreModule::class,
        ShopModule::class,
    ], [
        'tests::core' => true,
        'tests::shop' => true,
    ]);

    expect($this->moduleStatus('tests::core'))->toBe(ModuleStatus::ENABLED);
    expect($this->moduleStatus('tests::shop'))->toBe(ModuleStatus::ENABLED);
    expect(Modules::canBeDisabled('tests::core'))->toBeFalse();
    expect(Modules::canBeDisabled('tests::shop'))->toBeTrue();
});

test('allows disabling an errored module to recover persisted state', function () {
    $this->configureModules([
        AnalyticsModule::class,
    ], [
        'tests::analytics' => true,
    ]);

    expect($this->moduleStatus('tests::analytics'))->toBe(ModuleStatus::ERROR);
    expect(Modules::canBeDisabled('tests::analytics'))->toBeTrue();

    Modules::deactivationPlan()($this->module('tests::analytics'));

    expect($this->moduleStatus('tests::analytics'))->toBe(ModuleStatus::DISABLED);
    expect($this->readActivationState())->toBe([
        'tests::analytics' => false,
    ]);
});

test('reports enabled and disabled module collections consistently', function () {
    $this->configureModules([
        BlogModule::class,
        CoreModule::class,
    ], [
        'tests::blog' => true,
    ]);

    expect(array_keys(Modules::enabled()))->toBe(['tests::blog']);
    expect(array_keys(Modules::disabled()))->toBe(['tests::core']);
    expect(array_keys(Modules::all()))->toBe(['tests::blog', 'tests::core']);
});

test('refuses enabling and disabling a module once marked as must use', function () {
    $this->configureModules([
        BlogModule::class,
    ], [
        'tests::blog' => true,
    ]);

    $manager = $this->app->make(ModulesManager::class);
    $module = $this->module('tests::blog');
    $manager->driver->markAsMustUse($module);

    expect($manager->driver->isMustUse($module))->toBeTrue();
    expect(Modules::canBeEnabled('tests::blog'))->toBeFalse();
    expect(Modules::canBeDisabled('tests::blog'))->toBeFalse();
});
