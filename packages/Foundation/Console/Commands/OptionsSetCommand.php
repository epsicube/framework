<?php

declare(strict_types=1);

namespace Epsicube\Foundation\Console\Commands;

use Epsicube\Schemas\Types\UndefinedValue;
use Epsicube\Support\Exceptions\SchemaNotFound;
use Epsicube\Support\Facades\Options;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\select;

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
            $schema = Options::getSchema($group);
        } catch (SchemaNotFound $e) {
            error($e->getMessage());

            return self::FAILURE;
        }

        // Key verification
        $key = $this->argument('key');
        if (! isset($schema->properties()[$key])) {
            error("Unknown option '{$key}' for group '{$group}'");

            return self::FAILURE;
        }

        // Applying value
        $currentValue = Options::get($group, $key, true);
        $value = $this->argument('value');

        // TODO $typedValue using custom resolve/trandform in schema
        $typedValue = $value;
        Options::set($group, $key, $typedValue);

        info(sprintf(
            "Option '%s': '%s' updated from '%s' to '%s'",
            $group, $key,
            json_encode($currentValue),
            json_encode($typedValue)
        ));

        return self::SUCCESS;
    }

    protected function promptForMissingArgumentsUsing(): array
    {
        $schemas = Options::schemas();

        return [
            'group' => fn () => select(
                label: 'Which group do you want to update?',
                options: array_keys($schemas),
                required: 'You must select at least one group'
            ),
            'key' => fn () => select(
                label: 'Which key do you want to modify?',
                options: array_keys($schemas[$this->argument('group')]->properties()),
                required: 'You must select at least one key'
            ),

            'value' => function () use ($schemas) {
                $group = $this->argument('group');
                $key = $this->argument('key');
                $schema = $schemas[$group]->only($key);

                $currentValue = Options::store()->get($key, $group);
                if ($currentValue instanceof UndefinedValue) {
                    $inputs = $schema->toExecutedPrompts();
                } else {
                    $inputs = $schema->toExecutedPrompts([$key => $currentValue]);
                }

                return $inputs[$key] ?? null;
            },
        ];
    }
}
