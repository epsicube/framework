<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Console\Commands;

use EpsicubeModules\ExecutionPlatform\Contracts\Workflow;
use EpsicubeModules\ExecutionPlatform\Facades\Workflows;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

use function Laravel\Prompts\table;

class WorkflowsListCommand extends Command
{
    protected $signature = 'workflows:list';

    protected $aliases = ['w:l'];

    protected $description = 'List all registered workflows in Execution Platform';

    public function handle(): void
    {
        $noAnsi = $this->option('no-ansi');

        $fmt = function (string $text, string $ansi) use ($noAnsi) {
            $text = preg_replace('/[\p{So}\p{Cn}]/u', '', $text); // remove emoji

            return $noAnsi ? $text : "<{$ansi}>{$text}</>";
        };

        $workflows = array_map(fn (Workflow $workflow) => [
            $fmt($workflow->identifier(), 'fg=cyan;options=bold'),
            $fmt($workflow->label(), 'fg=yellow'),
            $fmt(Str::limit('TODO', 50), 'fg=white'),
        ], Workflows::all());

        $headers = [
            $fmt('Identifier', 'fg=cyan;options=bold'),
            $fmt('Name', 'fg=yellow;options=bold'),
            $fmt('Description', 'fg=white;options=bold'),
        ];
        table($headers, $workflows);
    }
}
