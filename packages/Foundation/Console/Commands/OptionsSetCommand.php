<?php

declare(strict_types=1);

namespace Epsicube\Foundation\Console\Commands;

use Epsicube\Support\Exceptions\DefinitionNotFoundException;
use Epsicube\Support\Facades\Options;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use InvalidArgumentException;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class OptionsSetCommand extends Command implements PromptsForMissingInput
{
    protected $signature = '
        options:set
        {group : Group}
        {key : The option key to update}
        {value : The new value to set}
    ';

    protected $aliases = ['o:s'];

    protected $description = 'Set an option value';

    public function handle(): int
    {
        // Group verification
        $group = $this->argument('group');
        try {
            $definition = Options::getDefinition($group);
        } catch (DefinitionNotFoundException $e) {
            error($e->getMessage());

            return self::FAILURE;
        }

        // Key verification
        $key = $this->argument('key');
        if (! $definition->has($key)) {
            error("Unknown option '{$key}' for group '{$group}'");

            return self::FAILURE;
        }

        // Applying value
        $currentValue = Options::get($group, $key, true);
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
        Options::set($group, $key, $typedValue);

        info(sprintf(
            "Option '%s': '%s' updated from '%s' to '%s'",
            $group,
            $key,
            json_encode($currentValue),
            json_encode($typedValue)
        ));

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
            'key' => fn () => select(
                label: 'Which key do you want to modify?',
                options: array_keys(Options::getDefinition($this->argument('group'))->all()),
                required: 'You must select at least one key'
            ),

            'value' => fn () => text(
                label: 'Enter the new value for the selected key:'
            ),
        ];
    }
}
