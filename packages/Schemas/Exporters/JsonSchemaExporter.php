<?php

declare(strict_types=1);

namespace Epsicube\Schemas\Exporters;

use Epsicube\Schemas\Contracts\JsonSchemaExportable;
use Epsicube\Schemas\Contracts\Property;
use Epsicube\Schemas\Contracts\SchemaExporter;
use Epsicube\Schemas\Properties\ObjectProperty;
use Epsicube\Schemas\Schema;
use RuntimeException;

class JsonSchemaExporter implements SchemaExporter
{
    public function exportSchema(Schema $schema): array
    {
        return $this->export(
            ObjectProperty::make()
                ->title($schema->title())
                ->properties($schema->properties())
                ->description($schema->description())
                ->additionalProperties(false)
        );
    }

    public function export(Property $field): array
    {
        if (! ($field instanceof JsonSchemaExportable)) {
            throw new RuntimeException('cannot export field that does not implement JsonSchemaExportable');
        }

        $schema = [];

        if ($field->getTitle() !== null) {
            $schema['title'] = $field->getTitle();
        }

        if ($field->getDescription() !== null) {
            $schema['description'] = $field->getDescription();
        }

        if ($field->getDefault() !== null) {
            $schema['default'] = $field->getDefault();
        }

        return array_merge($schema, $field->toJsonSchema($this));
    }
}
