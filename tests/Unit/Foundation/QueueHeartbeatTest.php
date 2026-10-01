<?php

declare(strict_types=1);

use Epsicube\Foundation\Console\Commands\QueueHeartbeatCommand;
use Illuminate\Queue\Events\Looping;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    config(['cache.default' => 'array']);
    Cache::forget(QueueHeartbeatCommand::cacheKey());
});

test('heartbeat fails when the worker never looped', function () {
    $this->artisan('epsicube:queue-heartbeat')->assertFailed();
});

test('heartbeat succeeds after the worker looped', function () {
    Event::dispatch(new Looping('database', 'default'));

    $this->artisan('epsicube:queue-heartbeat')->assertSuccessful();
});

test('heartbeat fails when it is older than max-age', function () {
    Cache::put(QueueHeartbeatCommand::cacheKey(), time() - 120, now()->addMinutes(5));

    $this->artisan('epsicube:queue-heartbeat')->assertFailed();
    $this->artisan('epsicube:queue-heartbeat', ['--max-age' => 300])->assertSuccessful();
});

test('heartbeat command is available through its alias', function () {
    Event::dispatch(new Looping('database', 'default'));

    $this->artisan('ec:qh')->assertSuccessful();
});
