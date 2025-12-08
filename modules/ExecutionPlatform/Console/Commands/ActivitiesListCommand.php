<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Console\Commands;

use EpsicubeModules\ExecutionPlatform\Contracts\Activity;
use EpsicubeModules\ExecutionPlatform\Facades\Activities;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

use function Laravel\Prompts\table;

class ActivitiesListCommand extends Command
{
    protected $signature = 'activities:list';

    protected $aliases = ['a:l'];

    protected $description = 'List all registered activities in Execution Platform';

    public function handle(): void
    {
        $noAnsi = $this->option('no-ansi');

        $fmt = function (string $text, string $ansi) use ($noAnsi) {
            $text = preg_replace('/[\p{So}\p{Cn}]/u', '', $text); // remove emoji

            return $noAnsi ? $text : "<{$ansi}>{$text}</>";
        };

        $activities = array_map(fn (Activity $activity) => [
            $fmt($activity->identifier(), 'fg=cyan;options=bold'),
            $fmt($activity->label(), 'fg=yellow'),
            $fmt(Str::limit($activity->description(), 100), 'fg=white'),
        ], Activities::all());

        $headers = [
            $fmt('Identifier', 'fg=cyan;options=bold'),
            $fmt('Name', 'fg=yellow;options=bold'),
            $fmt('Description', 'fg=white;options=bold'),
        ];
        table($headers, $activities);
    }
}
