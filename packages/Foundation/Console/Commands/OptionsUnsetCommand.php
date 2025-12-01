<?php

declare(strict_types=1);

namespace UniGale\Foundation\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use UniGale\Support\Exceptions\DefinitionNotFoundException;
use UniGale\Support\Facades\Options;

use function Laravel\Prompts\error;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\select;

class OptionsUnsetCommand extends Command implements PromptsForMissingInput
{
    protected $signature = '
        options:unset
        {group : Group}
        {keys* : The option key(s) to unset}
    ';

    protected $aliases = ['o:us'];

    protected $description = 'Unset an option value';

    public function handle(): int
    {
        // Module verification
        $group = $this->argument('group');
        try {
            $definition = Options::getDefinition($group);
        } catch (DefinitionNotFoundException $e) {
            error($e->getMessage());

            return self::FAILURE;
        }

        foreach ($this->argument('keys') as $key) {
            // Key verification
            if (! $definition->has($key)) {
                error("Unknown option '{$key}' for group '{$group}'");

                return self::FAILURE;
            }

            // Current value
            $currentValue = Options::get($group, $key, true);
            $defaultValue = $definition->getDefaultValue($key);

            // Delete the option
            Options::delete($group, $key);

            // Informative message
            $this->info(sprintf(
                "Options '%s': '%s' unset, reverting from '%s' to default '%s'",
                $group,
                $key,
                json_encode($currentValue),
                json_encode($defaultValue)
            ));
        }

        return self::SUCCESS;
    }

    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'group' => fn () => select(
                label: 'Which group do you want to update?',
                options: array_keys(Options::definitions()),
                required: 'You must select at least one group'
            ),
            'keys' => fn () => multiselect(
                label: 'Which key do you want to unset?',
                options: array_keys(Options::getDefinition($this->argument('group'))->all()),
                required: 'You must select at least one key'
            ),
        ];
    }
}
