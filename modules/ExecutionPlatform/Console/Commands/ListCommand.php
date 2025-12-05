<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Console\Commands;

use EpsicubeModules\ExecutionPlatform\Contracts\Activity;
use EpsicubeModules\ExecutionPlatform\Contracts\Workflow;
use EpsicubeModules\ExecutionPlatform\Facades\Activities;
use EpsicubeModules\ExecutionPlatform\Facades\Workflows;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

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
            $fmt(Str::limit('TODO', 50), 'fg=white'),
        ], Workflows::all());

        $activities = array_map(fn (Activity $activity) => [
            $fmt('Activity', 'fg=magenta'),
            $fmt($activity->identifier(), 'fg=cyan;options=bold'),
            $fmt($activity->label(), 'fg=yellow'),
            $fmt(Str::limit($activity->description(), 50), 'fg=white'),
        ], Activities::all());

        $headers = [
            $fmt('Type', 'fg=magenta;options=bold'),
            $fmt('Identifier', 'fg=cyan;options=bold'),
            $fmt('Name', 'fg=yellow;options=bold'),
            $fmt('Description', 'fg=white;options=bold'),
        ];
        table($headers, [...$workflows, ...$activities]);
    }
}
