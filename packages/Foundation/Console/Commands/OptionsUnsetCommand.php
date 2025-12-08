<?php

declare(strict_types=1);

namespace Epsicube\Foundation\Console\Commands;

use Epsicube\Schemas\Contracts\Property;
use Epsicube\Support\Exceptions\SchemaNotFound;
use Epsicube\Support\Facades\Options;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;

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
            $schema = Options::getSchema($group);
        } catch (SchemaNotFound $e) {
            error($e->getMessage());

            return self::FAILURE;
        }

        $properties = $schema->properties();
        foreach ($this->argument('keys') as $key) {
            // Key verification
            if (! isset($properties[$key])) {
                error("Unknown option '{$key}' for group '{$group}'");

                return self::FAILURE;
            }

            // Extract current and delete option
            $currentValue = Options::get($group, $key, true);
            Options::delete($group, $key);

            $this->info(sprintf(
                "[%s] option '%s' unset, reverting from '%s' to default",
                $group, $key, json_encode($currentValue),
            ));
        }

        return self::SUCCESS;
    }

    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'group' => fn () => select(
                label: 'Which group do you want to update?',
                options: array_keys(Options::schemas()),
                required: 'You must select at least one group'
            ),
            'keys' => fn () => multiselect(
                label: 'Which option(s) do you want to unset?',
                options: collect(Options::schemas()[$this->argument('group')]->properties())
                    ->map(fn (Property $property, string $name) => $property->getTitle() ?? $name)
                    ->all(),
                required: 'You must select at least one option'
            ),
        ];
    }
}
