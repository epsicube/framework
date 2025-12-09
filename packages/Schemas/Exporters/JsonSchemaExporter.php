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

        $schema = $field->toJsonSchema($this);

        if ($field->getTitle() !== null && ! isset($schema['title'])) {
            $schema['title'] = $field->getTitle();
        }

        if ($field->getDescription() !== null && ! isset($schema['description'])) {
            $schema['description'] = $field->getDescription();
        }

        if ($field->hasDefault() && ! isset($schema['default'])) {
            $schema['default'] = $field->getDefault();
        }

        if ($field->isNullable()) {
            // 1. Enum → add null
            if (! empty($schema['enum']) && is_array($schema['enum']) && ! in_array(null, $schema['enum'], true)) {
                $schema['enum'][] = null;
            }

            // 2. Const → convert to enum with null
            elseif (isset($schema['const'])) {
                $schema['enum'] = [$schema['const'], null];
                unset($schema['const']);
            }

            // 3. Type → add "null"
            elseif (isset($schema['type'])) {
                if (is_string($schema['type'])) {
                    $schema['type'] = [$schema['type'], 'null'];
                } elseif (is_array($schema['type']) && ! in_array('null', $schema['type'], true)) {
                    $schema['type'][] = 'null';
                }
            }

            // 4. Nothing → just allow null
            else {
                $schema['type'] = ['null'];
            }
        }

        return $schema;
    }
}
