<?php

declare(strict_types=1);

namespace Epsicube\Foundation\Console\Commands;

use Epsicube\Schemas\Contracts\Property;
use Epsicube\Schemas\Schema;
use Epsicube\Support\Facades\Options;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

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
            $fmt('Name', 'fg=yellow;options=bold'),
            $fmt('Key', 'fg=blue;options=bold'),
            $fmt('Value', 'fg=white;options=bold'),
            $fmt('Default', 'fg=magenta;options=bold'),
            $fmt('Type', 'fg=green;options=bold'),
        ];

        $schemas = Options::schemas();

        if ($groupsFilter = $this->argument('groups')) {
            $schemas = array_filter(
                $schemas,
                fn (string $identifier) => in_array($identifier, $groupsFilter),
                ARRAY_FILTER_USE_KEY
            );
        }

        $rows = collect($schemas)->map(function (Schema $schema, string $group) use ($fmt) {
            $storedValues = Options::store()->all($group);

            return collect($schema->properties())->map(
                fn (Property $property, string $key) => [
                    $fmt($group, 'fg=cyan;options=bold'),
                    $fmt($property->getTitle() ?? $key, 'fg=yellow'),
                    $fmt($key, 'fg=blue'),

                    array_key_exists($key, $storedValues)
                        ? $fmt(Str::limit(json_encode($storedValues[$key]), 50), 'fg=white')
                        : $fmt('Not provided', 'fg=gray'),

                    $property->hasDefault()
                        ? $fmt(Str::limit(json_encode($property->getDefault()), 50), 'fg=magenta')
                        : $fmt('Not provided', 'fg=gray'),

                    $fmt(
                        ($property->isNullable() ? '?' : '')
                            .Str::beforeLast(class_basename($property), 'Property')
                            .(! $property->isOptional() ? '!' : ''),
                        'fg=green'
                    ),
                ]
            )->all();
        })->flatten(1)->all();

        table($headers, $rows);

    }
}
