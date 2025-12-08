<?php

declare(strict_types=1);

namespace Epsicube\Schemas\Properties;

use Closure;
use Epsicube\Schemas\Contracts\Property;
use Epsicube\Schemas\Exporters\FilamentExporter;
use Epsicube\Schemas\Exporters\JsonSchemaExporter;
use Epsicube\Schemas\Exporters\LaravelPromptsFormExporter;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use RuntimeException;

use function Laravel\Prompts\form;

class ObjectProperty extends BaseProperty
{
    protected bool $accepted = false;

    /** @var array<string, Property> */
    protected array $properties;

    protected bool|Property $additionalProperties = false;

    public function default(array|null|Closure $default): static
    {
        $this->default = $default;

        return $this;
    }

    /**
     * @param  array<string,Property>  $properties
     */
    public function properties(array $properties): static
    {
        foreach ($properties as $name => $field) {
            if (isset($this->fields[$name])) {
                // TODO custom exception
                throw new RuntimeException("Property {$name} already registered");
            }
            $this->properties[$name] = $field;
        }

        return $this;
    }

    public function additionalProperties(bool|Property $additionalProperties = true): static
    {
        $this->additionalProperties = $additionalProperties;

        return $this;
    }

    public function toJsonSchema(JsonSchemaExporter $exporter): array
    {
        $schema = [
            'type'       => 'object',
            'properties' => [],
        ];

        $required = [];
        foreach ($this->properties as $name => $field) {
            $schema['properties'][$name] = $exporter->export($field);

            if ($field->isRequired()) {
                $required[] = $name;
            }
        }

        if (! empty($required)) {
            $schema['required'] = $required;
        }

        if ($this->additionalProperties instanceof Property) {
            $schema['additionalProperties'] = $exporter->export($this->additionalProperties);
        } else {
            $schema['additionalProperties'] = $this->additionalProperties;
        }

        return $schema;
    }

    public function toFilamentComponent(string $name, FilamentExporter $exporter): Component
    {
        $components = [];
        foreach ($this->properties as $propertyName => $field) {
            $components[] = $exporter->export($field, $propertyName);
        }

        // TODO additionalProperties
        // TODO required section

        return Section::make($this->getTitle() ?? $name)
            ->description($this->getDescription())
            ->schema($components)->default($this->getDefault());
    }

    public function askPrompt(?string $name, mixed $value, LaravelPromptsFormExporter $exporter): mixed
    {
        $form = form();
        foreach ($this->properties as $name => $field) {
            $form->add(fn () => $exporter->export($field, $name, $value[$name] ?? null), name: $name);
        }

        // TODO additionalProperties
        // TODO required section

        return $form->submit();
    }
}
