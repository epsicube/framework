<?php

declare(strict_types=1);

namespace UniGaleModules\ExecutionPlatform\Console\Commands;

use Illuminate\Console\Command;
use UniGaleModules\ExecutionPlatform\Contracts\Activity;
use UniGaleModules\ExecutionPlatform\Contracts\Workflow;
use UniGaleModules\ExecutionPlatform\Facades\Activities;
use UniGaleModules\ExecutionPlatform\Facades\Workflows;

use function Laravel\Prompts\table;

class ListCommand extends Command
{
    protected $signature = 'execution-platform:list';

    protected $aliases = ['ep:l'];

    protected $description = 'List all registered workflows, activities and tasks';

    public function handle(): void
    {
        $noAnsi = $this->option('no-ansi');

        $fmt = function (string $text, string $ansi) use ($noAnsi) {
            $text = preg_replace('/[\p{So}\p{Cn}]/u', '', $text); // remove emoji

            return $noAnsi ? $text : "<{$ansi}>{$text}</>";
        };

        $workflows = array_map(fn (Workflow $workflow) => [
            $fmt('Workflow', 'fg=magenta'),
            $fmt($workflow->identifier(), 'fg=cyan;options=bold'),
            $fmt($workflow->label(), 'fg=yellow'),
        ], Workflows::all());

        $activities = array_map(fn (Activity $activity) => [
            $fmt('Activity', 'fg=magenta'),
            $fmt($activity->identifier(), 'fg=cyan;options=bold'),
            $fmt($activity->label(), 'fg=yellow'),
        ], Activities::all());

        $headers = [
            $fmt('Type', 'fg=magenta;options=bold'),
            $fmt('Identifier', 'fg=cyan;options=bold'),
            $fmt('Name', 'fg=yellow;options=bold'),
        ];
        table($headers, [...$workflows, ...$activities]);
    }
}
