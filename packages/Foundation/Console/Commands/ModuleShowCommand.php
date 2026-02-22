<?php

declare(strict_types=1);

namespace Epsicube\Foundation\Console\Commands;

use Epsicube\Support\Facades\Modules;
use Epsicube\Support\Modules\Module;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Support\Facades\Blade;

use function Laravel\Prompts\select;
use function Termwind\render;

class ModuleShowCommand extends Command implements PromptsForMissingInput
{
    protected $signature = 'module:show {identifier}';

    protected $aliases = ['m:s'];

    protected $description = 'Show information about a specific module';

    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'identifier' => fn () => select(
                label: 'Which module would you like to show?',
                options: array_map(fn (Module $m) => $m->identifier, Modules::all()),
                required: 'You must select at least one module'
            ),
        ];
    }

    public function handle(): int
    {
        $module = Modules::safeGet($this->argument('identifier'));

        if (! $module) {
            $this->components->error("Module [{$this->argument('identifier')}] not found.");

            return static::FAILURE;
        }

        render(Blade::render(file_get_contents(__DIR__.'/ui/module.blade.php'), ['module' => $module]));

        return static::SUCCESS;
    }
}
