<?php

declare(strict_types=1);

use Epsicube\Tests\TestApplicationFactory;
use Epsicube\Tests\TestCase;
use Illuminate\Testing\ParallelRunner;

uses(TestCase::class)->in('Unit', 'Modules');

if (class_exists(ParallelRunner::class)) {
    ParallelRunner::resolveApplicationUsing(
        static fn () => TestApplicationFactory::bootForParallelProcess(),
    );
}
