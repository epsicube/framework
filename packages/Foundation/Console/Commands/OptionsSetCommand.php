<?php

declare(strict_types=1);

namespace UniGale\Foundation\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use InvalidArgumentException;
use UniGale\Foundation\Contracts\Module;
use UniGale\Foundation\Exceptions\DefinitionNotFoundException;
use UniGale\Foundation\Facades\Modules;
use UniGale\Foundation\Facades\Options;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class OptionsSetCommand extends Command implements PromptsForMissingInput
{
    protected $signature = '
        options:set
        {module : Module identifier}
        {key : The option key to update}
        {value : The new value to set}
    ';

    protected $aliases = ['o:s'];

    protected $description = 'Set an option value for a specific module';

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

        // Key verification
        $key = $this->argument('key');
        if (! $definition->has($key)) {
            error("Unknown option '{$key}' for module '{$moduleIdentifier}'");

            return self::FAILURE;
        }

        // Applying value
        $currentValue = Options::get($key, $moduleIdentifier, true);
        $value = $this->argument('value');

        // TODO ENUM IN DEFINTION FOR TYPES WITH validate, ...
        $typedValue = $value === 'null' ? null : match ($definition->all()[$key]['type']) {
            'string' => (string) $value,

            'integer' => filter_var($value, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE)
                ?? throw new InvalidArgumentException("Value '{$value}' is not a valid integer."),

            'boolean' => filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE)
                ?? throw new InvalidArgumentException("Value '{$value}' is not a valid boolean."),

            default => throw new InvalidArgumentException(
                "Unsupported type '".$definition->all()[$key]['type']."' for key '{$key}'."
            ),
        };
        Options::set($key, $typedValue, $moduleIdentifier);

        info(sprintf(
            "Option '%s': '%s' updated from '%s' to '%s'",
            $moduleIdentifier,
            $key,
            json_encode($currentValue),
            json_encode($typedValue)
        ));

        return self::SUCCESS;
    }

    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'module' => fn () => select(
                label: 'Which modules do you want to update?',
                options: collect(Options::definitions())->map(
                    fn ($_, string $moduleIdentifier) => Modules::safeGet($moduleIdentifier)?->identity()->name ?? $moduleIdentifier,
                )->all(),
                required: 'You must select at least one module'
            ),
            'key' => function () {
                $moduleIdentifier = $this->argument('module') ?? [];
                try {
                    $definition = Options::getDefinition($moduleIdentifier);
                } catch (DefinitionNotFoundException $e) {
                    return [];
                }

                return select(
                    label: 'Which key do you want to modify?',
                    options: array_keys($definition->all()),
                    required: 'You must select at least one key'
                );
            },

            'value' => fn () => text(
                label: 'Enter the new value for the selected key(s):'
            ),
        ];
    }
}
