<?php

declare(strict_types=1);

use Epsicube\Support\Console\PlanConsoleHelper;
use Epsicube\Tests\Fixtures\Plans\InspectablePlan;
use Symfony\Component\Console\Output\BufferedOutput;

test('returns only visible tasks from a plan', function () {
    $plan = new InspectablePlan;

    expect(array_map(
        static fn (array $task): string => $task['label'],
        $plan->getVisibleTasks(),
    ))->toBe([
        'First visible task',
        'Second visible task',
    ]);
});

test('renders a console plan', function () {
    $output = new BufferedOutput;

    PlanConsoleHelper::render($output, new InspectablePlan);

    expect($output->fetch())->toBe(implode(PHP_EOL, [
        '',
        'Plan:',
        '   • First visible task',
        '   • Second visible task',
        '',
        '',
    ]));
});
