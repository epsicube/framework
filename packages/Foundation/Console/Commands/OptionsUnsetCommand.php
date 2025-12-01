<?php

declare(strict_types=1);

namespace UniGale\Foundation\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use UniGale\Support\Exceptions\DefinitionNotFoundException;
use UniGale\Support\Facades\Modules;
use UniGale\Support\Facades\Options;

use function Laravel\Prompts\error;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\select;

class OptionsUnsetCommand extends Command implements PromptsForMissingInput
{
    protected $signature = '
        options:unset
        {module : Module identifier}
        {keys* : The option key(s) to unset}
    ';

    protected $aliases = ['o:us'];

    protected $description = 'Unset an option value for a specific module';

    public function handle(): int
    {
        // Module verification
        $moduleIdentifier = $this->argument('module');
        try {
            $definition = Options::getDefinition($moduleIdentifier);
        } catch (DefinitionNotFoundException $e) {
            error("Options definition for module '{$moduleIdentifier}' not found");

            return self::FAILURE;
        }

        foreach ($this->argument('keys') as $key) {
            // Key verification
            if (! $definition->has($key)) {
                error("Unknown option '{$key}' for module '{$moduleIdentifier}'");

                return self::FAILURE;
            }

            // Current value
            $currentValue = Options::get($key, $moduleIdentifier, true);
            $defaultValue = $definition->getDefaultValue($key);

            // Delete the option
            Options::delete($key, $moduleIdentifier);

            // Informative message
            $this->info(sprintf(
                "Options '%s': '%s' unset, reverting from '%s' to default '%s'",
                $moduleIdentifier,
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
            'module' => fn () => select(
                label: 'Which module do you want to update?',
                options: collect(Options::definitions())->map(
                    fn ($_, string $moduleIdentifier) => Modules::safeGet($moduleIdentifier)?->identity()->name ?? $moduleIdentifier,
                )->all(),
                required: 'You must select at least one module'
            ),
            'keys' => function () {
                $moduleIdentifier = $this->argument('module') ?? [];
                try {
                    $definition = Options::getDefinition($moduleIdentifier);
                } catch (DefinitionNotFoundException $e) {
                    return [];
                }

                return multiselect(
                    label: 'Which key do you want to unset?',
                    options: array_keys($definition->all()),
                    required: 'You must select at least one key'
                );
            },
        ];
    }
}
