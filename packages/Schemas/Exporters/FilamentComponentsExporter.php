<?php

declare(strict_types=1);

namespace Epsicube\Schemas\Exporters;

use Closure;
use Epsicube\Schemas\Contracts\FilamentExportable;
use Epsicube\Schemas\Contracts\Property;
use Epsicube\Schemas\Contracts\SchemaExporter;
use Epsicube\Schemas\Schema;
use Filament\Forms\Components\Field;
use Filament\Infolists\Components\Entry;
use Filament\Schemas\Components\Component;
use Filament\Support\Enums\Operation;
use Filament\Support\Icons\Heroicon;
use RuntimeException;

class FilamentComponentsExporter implements SchemaExporter
{
    public Operation $operation;

    /**
     * @param  Closure(Property $property, ?string $name, Component $component): void |null  $modifyComponentUsing
     */
    public function __construct(Operation|string $operation, protected ?Closure $modifyComponentUsing = null)
    {
        $this->operation = is_string($operation) ? Operation::from($operation) : $operation;
    }

    public function exportSchema(Schema $schema): array
    {
        return collect($schema->properties())
            ->map(fn (Property $property, string $name) => $this->export($property, $name))
            ->values()->all();
    }

    public function export(Property $property, ?string $name): Component
    {
        if (! ($property instanceof FilamentExportable)) {
            throw new RuntimeException('cannot export field that does not implement FilamentExportable');
        }

        $component = $property->toFilamentComponent($name ?? '_', $this); // <- TODO only used for array, but all fields requires $name

        // Globally apply default
        if ($property->hasDefault() && $this->operation !== Operation::View) {
            $component->default($property->getDefault());
        }

        // Globally apply required (when possible)
        if ($component instanceof Field && ! $property->isNullable()) {
            $component->required(! $property->isOptional());
        }

        // Globally apply label (when possible)
        if (($component instanceof Field || $component instanceof Entry) && ! $component->hasCustomLabel()) {
            $component->label($property->getTitle());
        }

        // Globally apply description (when possible)
        if (($component instanceof Field || $component instanceof Entry) && $description = $property->getDescription()) {

            if ($this->operation === Operation::Edit) {
                $component->helperText($description);
            } else {
                $component->hintIcon(Heroicon::OutlinedInformationCircle)->hintColor('info')->hintIconTooltip($description);
            }

        }

        if ($this->modifyComponentUsing) {
            call_user_func($this->modifyComponentUsing, $property, $name, $component);
        }

        return $component;
    }
}
