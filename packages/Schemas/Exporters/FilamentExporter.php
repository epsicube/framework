<?php

declare(strict_types=1);

namespace Epsicube\Schemas\Exporters;

use Epsicube\Schemas\Contracts\FilamentExportable;
use Epsicube\Schemas\Contracts\Property;
use Epsicube\Schemas\Contracts\SchemaExporter;
use Epsicube\Schemas\Properties\ObjectProperty;
use Epsicube\Schemas\Schema;
use Filament\Schemas\Components\Component;
use Filament\Support\Enums\Operation;
use RuntimeException;

class FilamentExporter implements SchemaExporter
{
    public Operation $operation;

    public function __construct(Operation|string $operation)
    {
        $this->operation = is_string($operation) ? Operation::from($operation) : $operation;
    }

    public function exportSchema(Schema $schema): Component
    {
        return $this->export(
            ObjectProperty::make()
                ->title($schema->title())
                ->properties($schema->properties())
                ->description($schema->description())
                ->additionalProperties(false),
            $schema->identifier()
        )->statePath(null);
    }

    public function export(Property $field, ?string $name): Component
    {
        if (! ($field instanceof FilamentExportable)) {
            throw new RuntimeException('cannot export field that does not implement FilamentExportable');
        }

        $component = $field->toFilamentComponent($name, $this);

        return $name ? $component->statePath($name)->key($name) : $component;
    }
}
