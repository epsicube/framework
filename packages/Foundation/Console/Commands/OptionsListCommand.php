<?php

declare(strict_types=1);

namespace UniGale\Foundation\Console\Commands;

use Illuminate\Console\Command;
use UniGale\Support\Facades\Options;
use UniGale\Support\OptionsDefinition;

use function Laravel\Prompts\table;

class OptionsListCommand extends Command
{
    protected $signature = '
        options:list
        {modules?* : Filter the output to specific module identifiers}
    ';

    protected $aliases = ['o:l'];

    protected $description = 'Display specific options';

    public function handle(): void
    {
        $noAnsi = $this->option('no-ansi');

        $fmt = function (string $text, string $ansi) use ($noAnsi) {
            $text = preg_replace('/[\p{So}\p{Cn}]/u', '', $text); // remove emojis

            return $noAnsi ? $text : "<{$ansi}>{$text}</>";
        };
        $headers = [
            $fmt('Module', 'fg=cyan;options=bold'),
            $fmt('Key', 'fg=blue;options=bold'),
            $fmt('Value', 'fg=green;options=bold'),
            $fmt('Default', 'fg=magenta;options=bold'),
            $fmt('Type', 'fg=yellow;options=bold'),
            $fmt('Autoload', 'fg=white;options=bold'),
        ];

        $definitions = Options::definitions();
        if ($modulesFilter = $this->argument('modules')) {
            $definitions = array_filter(
                $definitions,
                fn ($_, string $identifier) => in_array($identifier, $modulesFilter, true),
                ARRAY_FILTER_USE_BOTH
            );
        }

        $rows = collect($definitions)->map(function (OptionsDefinition $definition, string $moduleIdentifier) use (&$fmt) {
            return collect($definition->all())->map(fn (array $field, string $key) => [
                $fmt($moduleIdentifier, 'fg=cyan;options=bold'),
                $fmt($key, 'fg=blue'),

                $fmt(json_encode(Options::get($key, $moduleIdentifier, true)), 'fg=green'),

                $fmt(json_encode($definition->getDefaultValue($key)), 'fg=magenta'),
                $fmt($field['type'], 'fg=yellow'),
                $fmt(json_encode($field['autoload']), 'fg=white'),
            ])->all();
        })->flatten(1)->all();

        table($headers, $rows);

    }
}
