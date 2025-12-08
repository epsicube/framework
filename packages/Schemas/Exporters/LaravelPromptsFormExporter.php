<?php

declare(strict_types=1);

namespace Epsicube\Schemas\Exporters;

use Epsicube\Schemas\Contracts\PromptExportable;
use Epsicube\Schemas\Contracts\Property;
use Epsicube\Schemas\Contracts\SchemaExporter;
use Epsicube\Schemas\Properties\ObjectProperty;
use Epsicube\Schemas\Schema;
use RuntimeException;

class LaravelPromptsFormExporter implements SchemaExporter
{
    public function __construct(protected ?array $data = null) {}

    public function exportSchema(Schema $schema): mixed
    {
        return $this->export(
            ObjectProperty::make()
                ->title($schema->title())
                ->properties($schema->properties())
                ->description($schema->description())
                ->additionalProperties(false),
            null,
            $this->data
        );
    }

    public function export(Property $field, ?string $name, mixed $value): mixed
    {
        if (! ($field instanceof PromptExportable)) {
            throw new RuntimeException('cannot export field that does not implement PromptExportable');
        }

        return $field->askPrompt($name, $value, $this);
    }
}
