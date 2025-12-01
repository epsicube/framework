<?php

declare(strict_types=1);

namespace UniGale\Foundation\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Throwable;
use UniGale\Support\Contracts\Module;
use UniGale\Support\Facades\Modules;

use function Laravel\Prompts\error;
use function Laravel\Prompts\multiselect;

class ModulesEnableCommand extends Command implements PromptsForMissingInput
{
    protected $signature = 'modules:enable {identifier* : The identifier of the module to enable}';

    protected $aliases = ['m:e'];

    protected $description = 'Enable a module by its identifier';

    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'identifier' => fn () => multiselect(
                label: 'Which modules would you like to enable?',
                options: array_map(
                    fn (Module $module) => $module->identity()->name,
                    array_filter(Modules::disabled(), fn (Module $module) => Modules::canBeEnabled($module))
                ),
                required: 'You must select at least one module'
            ),
        ];
    }

    public function handle(): int
    {
        $identifiers = $this->argument('identifier');

        if (empty($identifiers)) {
            error('No module identifier provided.');

            return self::FAILURE;
        }

        foreach ($identifiers as $identifier) {
            try {
                Modules::enable($identifier);
                info("Module [{$identifier}] has been enabled.");
            } catch (Throwable $e) {
                error("Unable to enable module [{$identifier}]: {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
