<?php

declare(strict_types=1);

namespace Epsicube\Support\Console;

use Epsicube\Support\Plan;
use Symfony\Component\Console\Output\OutputInterface;

final class PlanConsoleHelper
{
    /**
     * @template T
     *
     * @param  Plan<T>  $plan
     */
    public static function render(
        OutputInterface $output,
        Plan $plan,
        string $title = 'Plan',
    ): void {
        $output->writeln('');
        $output->writeln(sprintf('<fg=yellow;options=bold>%s:</>', $title));

        self::renderTasks($output, $plan->getVisibleTasks());
        $output->writeln('');
    }

    /**
     * @param  list<array{label: string, callback: callable, hidden: bool}>  $tasks
     */
    private static function renderTasks(OutputInterface $output, array $tasks): void
    {
        if ($tasks === []) {
            $output->writeln('   <fg=gray>• No visible tasks</>');

            return;
        }

        foreach ($tasks as $task) {
            $output->writeln(sprintf('   <fg=gray>•</> %s', $task['label']));
        }
    }
}
