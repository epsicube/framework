<?php

declare(strict_types=1);

namespace Epsicube\Schemas;

use Epsicube\Schemas\Contracts\Property;
use Epsicube\Schemas\Contracts\SchemaExporter;
use Epsicube\Schemas\Exceptions\DuplicatePropertyException;
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
        return new static($identifier,$title, $description, $properties);
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
            if ($property->isRequired() && $property->hasDefault()) {
                throw new LogicException(
                    sprintf("Property '%s' is marked as required but has a default value defined.", $name)
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
}
