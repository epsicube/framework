<?php

declare(strict_types=1);

namespace UniGale\Foundation\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use UniGale\Foundation\Concerns\Module;
use UniGale\Foundation\Facades\Modules;

use function Laravel\Prompts\table;

class ModulesStatusCommand extends Command
{
    protected $signature = 'modules:status';
    protected $aliases = ['m:s'];

    protected $description = 'Displays all available modules with their metadata and activation status.';

    public function handle(): void
    {
        // Détection du mode sans ANSI
        $noAnsi = $this->option('no-ansi');

        // Helper simple pour gérer couleur + fallback
        $fmt = function (string $text, string $ansi) use ($noAnsi) {
            $text = preg_replace('/[\p{So}\p{Cn}]/u', '', $text); // <- remove emoji

            return $noAnsi ? $text : "<{$ansi}>{$text}</>";
        };

        $rows = array_map(function (Module $module) use ($fmt) {
            $enabled = Modules::isEnabled($module);

            return [
                $fmt($module->identifier(), 'fg=cyan;options=bold'),
                $fmt($module->name(), 'fg=yellow'),
                $fmt(Str::limit($module->description() ?? '', 50), 'fg=gray'),
                $fmt($module->author(), 'fg=magenta'),
                $fmt($module->version(), 'fg=green'),
                match (true) {
                    Modules::isMustUse($module) => $fmt('MUST-USE', 'fg=yellow'),
                    $enabled                    => $fmt('ENABLED', 'fg=green'),
                    ! $enabled                  => $fmt('DISABLED', 'fg=red'),
                },
            ];
        }, Modules::all());

        $headers = [
            $fmt('Identifier', 'fg=cyan;options=bold'),
            $fmt('Name', 'fg=yellow;options=bold'),
            $fmt('Description', 'fg=gray;options=bold'),
            $fmt('Author', 'fg=magenta;options=bold'),
            $fmt('Version', 'fg=green;options=bold'),
            $fmt('Status', 'fg=white;options=bold'),
        ];

        table($headers, $rows);
    }
}
