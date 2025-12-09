<?php

declare(strict_types=1);

namespace Epsicube\Schemas\Properties;

use Closure;
use Epsicube\Schemas\Contracts\Property;
use Epsicube\Schemas\Exporters\FilamentExporter;
use Epsicube\Schemas\Exporters\JsonSchemaExporter;
use Epsicube\Schemas\Exporters\LaravelPromptsFormExporter;
use Epsicube\Schemas\Exporters\LaravelValidationExporter;
use Epsicube\Schemas\Types\UndefinedValue;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use RuntimeException;

use function Laravel\Prompts\form;

class ObjectProperty extends BaseProperty
{
    protected bool $accepted = false;

    /** @var array<string, Property> */
    protected array $properties = [];

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
            ->when(
                $this->getDescription(),
                fn (Section $component) => $component->description($this->getDescription())
            )
            ->schema($components);
    }

    public function askPrompt(?string $name, mixed $value, LaravelPromptsFormExporter $exporter): mixed
    {
        $form = form();
        foreach ($this->properties as $name => $field) {
            $initialValue = (is_array($value) && array_key_exists($name, $value)) ? $value[$name] : new UndefinedValue;
            $form->add(fn () => $exporter->export($field, $name, $initialValue), name: $name);
        }

        // TODO additionalProperties
        // TODO required section

        return $form->submit();
    }

    public function resolveValidationRules(mixed $value, LaravelValidationExporter $exporter): array
    {
        $rules = ['array'];
        foreach ($this->properties as $name => $property) {
            $childValue = is_array($value) ? ($value[$name] ?? null) : null;
            $exporter->exportChild($property, $name, $childValue);
        }

        // additionalProperties = Property → validation typed
        if ($this->additionalProperties instanceof Property && is_array($value)) {
            foreach ($value as $key => $childValue) {
                if (! array_key_exists($key, $this->properties)) {
                    $exporter->exportChild($this->additionalProperties, $key, $childValue);
                }
            }
        }

        // additionalProperties = true → always keep
        elseif ($this->additionalProperties === true && is_array($value)) {
            foreach ($value as $key => $childValue) {
                if (! array_key_exists($key, $this->properties)) {
                    // rule ensure laravel validated keep the property
                    $exporter->setChildRules($key, ['present']);
                }
            }
        }
        // additionalProperties = null → always prohibits
        elseif ($this->additionalProperties === false && is_array($value)) {
            foreach ($value as $key => $childValue) {
                if (! array_key_exists($key, $this->properties)) {
                    // rule ensure laravel validated keep the property
                    $exporter->setChildRules($key, ['prohibited']);
                }
            }
        }

        return $rules;
    }
}
