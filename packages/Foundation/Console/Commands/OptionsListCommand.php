<?php

declare(strict_types=1);

namespace UniGale\Foundation\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use UniGale\Support\Facades\Options;
use UniGale\Support\OptionsDefinition;

use function Laravel\Prompts\table;

class OptionsListCommand extends Command
{
    protected $signature = '
        options:list
        {groups?* : Filter the output to specific groups}
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
            $fmt('Group', 'fg=cyan;options=bold'),
            $fmt('Key', 'fg=blue;options=bold'),
            $fmt('Value', 'fg=green;options=bold'),
            $fmt('Default', 'fg=magenta;options=bold'),
            $fmt('Type', 'fg=yellow;options=bold'),
        ];

        $definitions = Options::definitions();
        if ($groupsFilter = $this->argument('groups')) {
            $definitions = array_filter(
                $definitions,
                fn ($_, string $identifier) => in_array($identifier, $groupsFilter, true),
                ARRAY_FILTER_USE_BOTH
            );
        }

        $rows = collect($definitions)->map(function (OptionsDefinition $definition, string $group) use (&$fmt) {
            return collect($definition->all())->map(fn (array $field, string $key) => [
                $fmt($group, 'fg=cyan;options=bold'),
                $fmt($key, 'fg=blue'),

                $fmt(Str::limit(json_encode(Options::get($group, $key, true)), 50), 'fg=green'),

                $fmt(Str::limit(json_encode($definition->getDefaultValue($key)), 50), 'fg=magenta'),
                $fmt($field['type'], 'fg=yellow'),
            ])->all();
        })->flatten(1)->all();

        table($headers, $rows);

    }
}
