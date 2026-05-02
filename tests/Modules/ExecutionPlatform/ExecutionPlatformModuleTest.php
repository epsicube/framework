<?php

declare(strict_types=1);

use Epsicube\Tests\Modules\ExecutionPlatform\Fixtures\ExecutionPlatformProbeModule;
use EpsicubeModules\ExecutionPlatform\ExecutionPlatformModule;
use EpsicubeModules\ExecutionPlatform\Facades\Activities;
use EpsicubeModules\ExecutionPlatform\Facades\Workflows;
use EpsicubeModules\ExecutionPlatform\Models\Execution;
use Illuminate\Support\Facades\Artisan;

test('execution platform module declares expected metadata and supports', function () {
    $this->configureModules([
        ExecutionPlatformModule::class,
    ]);

    $module = $this->module('core::execution-platform');

    expect($module->identifier)->toBe('core::execution-platform')
        ->and($module->identity->name)->toBe('Execution Platform')
        ->and(array_map(
            fn ($support) => $support->condition->name(),
            $module->supports->supports,
        ))->toBe([
            'Module core::administration',
            'Module core::mcp-server',
            'Module core::json-rpc-server',
        ])
        ->and($module->options->properties())->toBe([]);
});

test('execution platform support hook lets other modules register activities and workflows', function () {
    $this->configureModules([
        ExecutionPlatformModule::class,
        ExecutionPlatformProbeModule::class,
    ], [
        'core::execution-platform'        => true,
        'tests::execution-platform-probe' => true,
    ]);

    expect(array_keys(Activities::all()))->toBe(['tests::probe-activity'])
        ->and(Activities::toIdentifierLabelMap())->toBe(['tests::probe-activity' => 'Probe Activity'])
        ->and(array_keys(Workflows::all()))->toBe(['tests::probe-workflow'])
        ->and(Workflows::toIdentifierLabelMap())->toBe(['tests::probe-workflow' => 'Probe Workflow']);
});

test('execution platform runs registered activities synchronously and persists execution details', function () {
    $this->configureModules([
        ExecutionPlatformModule::class,
        ExecutionPlatformProbeModule::class,
    ], [
        'core::execution-platform'        => true,
        'tests::execution-platform-probe' => true,
    ]);

    $exitCode = Artisan::call('migrate', ['--no-interaction' => true]);

    expect($exitCode)->toBe(0);

    $execution = Activities::run('tests::probe-activity');

    expect($execution)->toBeInstanceOf(Execution::class)
        ->and($execution->target)->toBe('tests::probe-activity')
        ->and($execution->input)->toBe(['payload' => 'default-payload'])
        ->and($execution->output)->toBe(['message' => 'DEFAULT-PAYLOAD'])
        ->and($execution->status->value)->toBe('COMPLETED')
        ->and(Execution::query()->count())->toBe(1);
});
