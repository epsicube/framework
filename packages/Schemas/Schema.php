<?php

declare(strict_types=1);

namespace Epsicube\Schemas;

use Epsicube\Schemas\Contracts\Property;
use Epsicube\Schemas\Contracts\SchemaExporter;
use Epsicube\Schemas\Exceptions\DuplicatePropertyException;
use Epsicube\Schemas\Exporters\JsonSchemaExporter;
use Epsicube\Schemas\Exporters\LaravelPromptsFormExporter;
use Epsicube\Schemas\Exporters\LaravelValidatorExporter;
use Illuminate\Contracts\Validation\Validator;
use LogicException;

class Schema
{
    /**
     * @param  array<string,Property>  $properties
     */
    public function __construct(
        protected string $identifier,
        protected ?string $title = null,
        protected ?string $description = null,
        protected array $properties = [],
    ) {}

    /**
     * @param  array<string, Property>  $properties
     */
    public static function create(string $identifier, ?string $title = null, ?string $description = null, array $properties = []): Schema
    {
        return new static($identifier, $title, $description, $properties);
    }

    public function identifier(): string
    {
        return $this->identifier;
    }

    public function title(): ?string
    {
        return $this->title;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    /**
     * @return array<string,Property>
     */
    public function properties(): array
    {
        return $this->properties;
    }

    public function property(string $name): ?Property
    {
        return $this->properties[$name] ?? null;
    }

    /**
     * @param  array<string, Property>  $properties
     * @return $this
     *
     * @throws DuplicatePropertyException
     * @throws LogicException
     */
    public function append(array $properties): static
    {
        foreach ($properties as $name => $property) {
            if (isset($this->properties[$name])) {
                throw DuplicatePropertyException::forSchema($this, $name);
            }

            // Consistency check: required vs default
            if (! $property->isOptional() && $property->hasDefault()) {
                throw new LogicException(
                    sprintf("Property '%s' is not marked as optional but has a default value defined.", $name)
                );
            }

            $this->properties[$name] = $property;
        }

        return $this;
    }

    public function export(SchemaExporter $exporter): mixed
    {
        return $exporter->exportSchema($this);
    }

    public function only(string ...$properties): static
    {
        $filtered = array_intersect_key($this->properties(), array_flip($properties));

        return new static(
            identifier: $this->identifier(),
            title: $this->title(),
            description: $this->description(),
            properties: $filtered
        );
    }

    public function withDefaults(array $data): array
    {
        $defaults = array_map(
            fn (Property $property) => $property->getDefault(),
            array_filter($this->properties(), fn (Property $property) => $property->hasDefault())
        );

        return array_merge($defaults, $data);
    }

    public function toJsonSchema(): array
    {
        return $this->export(new JsonSchemaExporter);
    }

    public function toExecutedPrompts(?array $data = null): array
    {
        return $this->export(new LaravelPromptsFormExporter($data));
    }

    public function toValidator(array $data = [], array $messages = [], array $attributes = [], array $prepend = []): Validator
    {
        return $this->export(new LaravelValidatorExporter($data, $messages, $attributes, $prepend));
    }

    /**
     * Validate input data against the schema and return validated values
     * with defaults applied.
     *
     * @param  array<string, mixed>  $data  Input data
     * @param  bool  $bail  Whether to stop validation on first error
     * @return array<string, mixed> Validated data
     */
    public function validated(array $data, bool $bail = false): array
    {
        $validator = $this->toValidator(
            $this->withDefaults($data),
            prepend: $bail ? ['bail'] : []
        );

        return $validator->validated();
    }
}
