<?php

declare(strict_types=1);

namespace Epsicube\Schemas\Properties;

use Closure;
use Epsicube\Schemas\Contracts\Property;
use Epsicube\Schemas\Exporters\FilamentComponentsExporter;
use Epsicube\Schemas\Exporters\JsonSchemaExporter;
use Epsicube\Schemas\Exporters\LaravelPromptsFormExporter;
use Epsicube\Schemas\Exporters\LaravelValidatorExporter;
use Epsicube\Schemas\Filament\CustomRepeater;
use Epsicube\Schemas\Schema;
use Epsicube\Schemas\Types\UndefinedValue;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Repeater;
use Filament\Infolists\Components\Entry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Schemas\Components\Component;
use Filament\Support\Enums\Operation;
use Illuminate\Validation\ValidationException;

use function Laravel\Prompts\select;
use function Laravel\Prompts\warning;

class ArrayProperty extends BaseProperty
{
    protected ?Property $items = null;

    protected ?int $minItems = null;

    protected ?int $maxItems = null;

    protected bool $uniqueItems = false;

    public function default(array|null|Closure $default): static
    {
        $this->default = $default;

        return $this;
    }

    public function items(Property $item): static
    {
        $this->items = $item;

        return $this;
    }

    public function minItems(?int $min): static
    {
        $this->minItems = $min;

        return $this;
    }

    public function maxItems(?int $max): static
    {
        $this->maxItems = $max;

        return $this;
    }

    public function uniqueItems(bool $unique = true): static
    {
        $this->uniqueItems = $unique;

        return $this;
    }

    public function toJsonSchema(JsonSchemaExporter $exporter): array
    {
        $schema = [
            'type' => 'array',
        ];

        if ($this->items !== null) {
            $schema['items'] = $exporter->export($this->items);
        }

        if ($this->minItems !== null) {
            $schema['minItems'] = $this->minItems;
        }

        if ($this->maxItems !== null) {
            $schema['maxItems'] = $this->maxItems;
        }

        if ($this->uniqueItems) {
            $schema['uniqueItems'] = true;
        }

        return $schema;
    }

    public function toFilamentComponent(string $name, FilamentComponentsExporter $exporter): Component
    {
        $childComponent = null;
        if ($this->items instanceof Property) {
            $childComponent = $exporter->export($this->items, null);
        }

        if ($exporter->operation === Operation::View) {

            $entry = RepeatableEntry::make($name)
                ->contained(false)
                ->schema([$childComponent])
                ->inlineLabel();

            // Handle scalar type
            if ($childComponent instanceof Entry) {
                $entry->getStateUsing(function (RepeatableEntry $component) use (&$childComponent) {
                    // Resolve initial state
                    (function (): void {
                        $this->hasConstantState = false;
                    })->call($component);
                    $initialState = $component->getState();
                    (function (): void {
                        $this->hasConstantState = true;
                    })->call($component);

                    return array_map(fn ($i) => ['_s_' => $i], $initialState ?? []);
                })->schema([$childComponent->inlineLabel(false)->statePath('_s_')]);
            }

            return $entry;
        }

        // TODO support nullable, and empty array without nullable
        // TODO not work with array of array (nested)
        return CustomRepeater::make($name)
            ->when(
                $childComponent instanceof Field,
                fn (Repeater $field) => $field->simple($childComponent),
                fn (Repeater $field) => $field->schema([$childComponent]),
            )
            ->uniqueItems($this->uniqueItems)
            ->minItems($this->minItems)
            ->maxItems($this->maxItems);
    }

    /**
     * Prompt the user to manage an array of items.
     *
     * Supports adding, editing, and deleting items, with optional uniqueness.
     * Uses the exporter to handle item input.
     */
    public function askPrompt(?string $name, mixed $value, LaravelPromptsFormExporter $exporter): array
    {
        $items = is_array($value) ? array_values($value) : [];

        while (true) {
            $options = array_merge(array_combine(
                array_map(fn ($i) => "item_{$i}", array_keys($items)),
                array_map(fn ($i, $item) => "[{$i}] ".(is_scalar($item) ? (string) $item : json_encode($item)), array_keys($items), $items)
            ), ['add' => 'Add new item', 'finish' => 'Finish']);

            $choice = select(
                label: $this->getTitle() ?? $name ?? '',
                options: $options,
                default: 'finish',
                validate: function (string $option) use ($items, $name) {
                    if ($option !== 'finish') {
                        return null;
                    }

                    $s = Schema::create('', properties: [$name => $this]);
                    try {
                        $s->toValidator([$name => $items])->validate();
                    } catch (ValidationException $e) {
                        return implode("\n  ⚠ ", $e->errors()[$name]);
                    }

                    return null;
                }, hint: 'Select an item to Edit/Delete or choose Add/Finish'
            );

            if ($choice === 'finish') {
                return $items;
            }

            if ($choice === 'add') {
                $newItem = $exporter->export($this->items, 'New Item', new UndefinedValue);

                $potentialList = collect($items)->push($newItem);
                if ($this->uniqueItems && $potentialList->duplicates()->contains($newItem)) {
                    warning('This item already exists and must be unique.');

                    continue;
                }

                $items[] = $newItem;
                info('Item added.');

                continue;
            }

            $index = (int) str_replace('item_', '', $choice);
            $action = select(
                label: "Choose action for item #{$index}",
                options: ['edit' => 'Edit', 'delete' => 'Delete', 'back' => 'Back'],
                default: 'back',
                hint: "Item: {$options[$choice]}"
            );

            if ($action === 'edit') {
                $edited = $exporter->export($this->items, "Item #{$index}", $items[$index]);

                if ($this->uniqueItems) {
                    $existingItems = collect(array_values(array_diff_key($items, [$index => null])));
                    $potentialList = $existingItems->push($edited);
                    if ($potentialList->duplicates()->contains($edited)) {
                        warning('This item already exists and must be unique.');

                        continue;
                    }
                }

                $items[$index] = $edited;
                info("Item #{$index} updated.");
            } elseif ($action === 'delete') {
                unset($items[$index]);
                $items = array_values($items);
                info("Item #{$index} deleted.");
            }
        }
    }

    public function resolveValidationRules(mixed $value, LaravelValidatorExporter $exporter): array
    {
        $rules = [
            'array',
            'list',
        ];

        if ($this->minItems !== null) {
            $rules[] = 'min:'.$this->minItems;
        }

        if ($this->maxItems !== null) {
            $rules[] = 'max:'.$this->maxItems;
        }

        // TODO ensure it work ith nested object, or array
        if ($this->uniqueItems) {
            $rules[] = 'distinct';
        }

        // Loop over each instead of using .* to get index on error
        if (is_array($value) && $this->items instanceof Property) {
            foreach (array_values($value) as $i => $itemValue) {
                $exporter->exportChild($this->items, (string) $i, $itemValue);
            }
        }

        return $rules;
    }
}
