<?php

declare(strict_types=1);

namespace Epsicube\Schemas\Properties;

use Closure;
use Epsicube\Schemas\Contracts\FilamentExportable;
use Epsicube\Schemas\Contracts\JsonSchemaExportable;
use Epsicube\Schemas\Contracts\LaravelRulesExportable;
use Epsicube\Schemas\Contracts\PromptExportable;
use Epsicube\Schemas\Contracts\Property;
use Epsicube\Schemas\Exceptions\UndefinedDefaultException;
use Epsicube\Schemas\Exporters\FilamentComponentsExporter;
use Epsicube\Schemas\Exporters\JsonSchemaExporter;
use Epsicube\Schemas\Exporters\LaravelPromptsFormExporter;
use Epsicube\Schemas\Exporters\LaravelValidatorExporter;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;

/**
 * BaseField provides the core functionality for all native fields in Epsicube Schema.
 *
 * Each native field supports the following exporters:
 * - @see FilamentComponentsExporter
 * - @see JsonSchemaExporter
 * - @see LaravelValidatorExporter
 * - @see LaravelPromptsFormExporter
 */
abstract class BaseProperty implements FilamentExportable, JsonSchemaExportable, LaravelRulesExportable, PromptExportable, Property
{
    protected ?string $title = null;

    protected ?string $description = null;

    protected bool $optional = false;

    protected bool $nullable = false;

    protected mixed $default;

    public static function make(): static
    {
        return new static;
    }

    /**
     * Keep to ensure var_export and require work for cache
     *
     * @throws ReflectionException
     */
    public static function __set_state(array $properties): static
    {
        $instance = (new ReflectionClass(static::class))->newInstanceWithoutConstructor();

        foreach ($properties as $key => $value) {
            if (property_exists($instance, $key)) {
                $instance->{$key} = $value;
            }
        }

        return $instance;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function isOptional(): bool
    {
        return $this->optional;
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }

    public function getDefault(): mixed
    {
        if (! $this->hasDefault()) {
            throw UndefinedDefaultException::forProperty($this);
        }

        return $this->default instanceof Closure ? call_user_func($this->default) : $this->default;
    }

    public function hasDefault(): bool
    {
        // Use reflection because isset with 'false or null' return 'false'
        $reflectedDefault = new ReflectionProperty(static::class, 'default');

        return $reflectedDefault->isInitialized($this);
    }

    // ------------------------------
    // Fluent setters
    // ------------------------------
    public function title(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function description(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function optional(bool $optional = true): static
    {
        $this->optional = $optional;

        return $this;
    }

    public function nullable(bool $nullable = true): static
    {
        $this->nullable = $nullable;

        return $this;
    }
}
