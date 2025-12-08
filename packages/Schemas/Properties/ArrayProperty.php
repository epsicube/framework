<?php

declare(strict_types=1);

namespace Epsicube\Schemas\Properties;

use Closure;
use Epsicube\Schemas\Contracts\Property;
use Epsicube\Schemas\Exporters\FilamentExporter;
use Epsicube\Schemas\Exporters\JsonSchemaExporter;
use Epsicube\Schemas\Exporters\LaravelPromptsFormExporter;
use Filament\Forms\Components\Repeater;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Schemas\Components\Component;
use Filament\Support\Enums\Operation;

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

    public function toFilamentComponent(string $name, FilamentExporter $exporter): Component
    {
        $components = [];
        if ($this->items instanceof Property) {
            $components[] = $exporter->export($this->items, null);
        }

        if ($exporter->operation === Operation::View) {
            return RepeatableEntry::make($name)
                ->schema($components)->inlineLabel()
                ->label($this->getTitle())->hint($this->getDescription())->default($this->getDefault());
        }

        // TODO filament unique items, using custom rule
        return Repeater::make($name)
            ->required($this->isRequired())
            ->components($components)
            ->minItems($this->minItems)
            ->maxItems($this->maxItems)
            ->label($this->getTitle())->hint($this->getDescription())->default($this->getDefault());
    }

    public function askPrompt(?string $name, mixed $value, LaravelPromptsFormExporter $exporter): array
    {
        // TODO: Implement askPrompt() method.
    }
}
