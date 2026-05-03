<?php

declare(strict_types=1);

namespace Epsicube\Tests\Fixtures\Plans;

use Epsicube\Support\Plan;

/**
 * @extends Plan<null>
 */
final class InspectablePlan extends Plan
{
    protected function setUp(): void
    {
        $this->addTask('First visible task', static function (): void {}, 0);
        $this->addTask('Hidden task', static function (): void {}, 1, true);
        $this->addTask('Second visible task', static function (): void {}, 2);
    }
}
