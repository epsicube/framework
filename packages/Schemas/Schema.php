<?php

declare(strict_types=1);

namespace Epsicube\Schemas;

use Epsicube\Schemas\Contracts\Property;
use Epsicube\Schemas\Contracts\SchemaExporter;
use RuntimeException;

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

    /**
     * @param  array<string, Property>  $properties
     * @return $this
     */
    public function append(array $properties): Schema
    {
        foreach ($properties as $name => $property) {
            if (isset($this->properties[$name])) {
                // TODO Custom error
                throw new RuntimeException("Property '{$name}' already exists.");
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

    /**
     * Apply default values to an options array according to this schema.
     *
     * @param  array<string,mixed>  $values
     * @param  bool  $insertMissing  If true, missing keys are added with default values.
     * @param  bool  $keepExtraKeys  If false, values not present in the schema are removed.
     */
    public function withDefaults(array $values, bool $insertMissing = false, bool $keepExtraKeys = false): array
    {
        $result = $values;

        // TODO recursive + sanitize across field
        /**
         * Implementation test
         * $property->sanitize(mixed $value)
         */
        foreach ($this->properties as $name => $property) {
            if (! array_key_exists($name, $result) && $insertMissing) {
                $result[$name] = $property->getDefault();

                continue;
            }

            if (array_key_exists($name, $result) && $result[$name] === null) {
                $result[$name] = $property->getDefault();
            }
        }

        // Remove keys not defined in the schema (unless allowed)
        if (! $keepExtraKeys) {
            $result = array_intersect_key($result, $this->properties);
        }

        return $result;
    }
}
