<?php

declare(strict_types=1);

use Epsicube\Support\Facades\Epsicube;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    config(['cache.default' => 'array']);
});

test('supervisor stays alive and reports idle when no work command is registered', function () {
    expect(Epsicube::workCommands())->toBeEmpty();

    // Pre-set the terminate signal so the supervisor loop exits after its first iteration
    Cache::forever('epsicube:work:terminate', now()->addMinute()->timestamp);

    $this->artisan('epsicube:work')
        ->expectsOutputToContain('No work to do')
        ->doesntExpectOutputToContain('Supervisor cannot start')
        ->expectsOutputToContain('Termination signal detected')
        ->assertSuccessful();
});
