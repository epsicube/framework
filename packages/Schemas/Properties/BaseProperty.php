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
use Epsicube\Schemas\Exporters\FilamentExporter;
use Epsicube\Schemas\Exporters\JsonSchemaExporter;
use Epsicube\Schemas\Exporters\LaravelPromptsFormExporter;
use Epsicube\Schemas\Exporters\LaravelValidationExporter;
use ReflectionProperty;

/**
 * BaseField provides the core functionality for all native fields in Epsicube Schema.
 *
 * Each native field supports the following exporters:
 * - @see FilamentExporter
 * - @see JsonSchemaExporter
 * - @see LaravelValidationExporter
 * - @see LaravelPromptsFormExporter
 */
abstract class BaseProperty implements FilamentExportable, JsonSchemaExportable, LaravelRulesExportable, PromptExportable, Property
{
    protected ?string $title = null;

    protected ?string $description = null;

    protected bool $required = false;

    protected bool $nullable = false;

    /**
     * @var ReflectionProperty Used to detect if default provided because isset return false when value is null
     */
    protected ReflectionProperty $reflectionDefault;

    protected mixed $default;

    public function __construct()
    {
        $this->reflectionDefault = new ReflectionProperty(static::class, 'default');
    }

    public static function make(): static
    {
        return new static;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function isRequired(): bool
    {
        return $this->required;
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
        return $this->reflectionDefault->isInitialized($this);
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

    public function required(bool $required = true): static
    {
        $this->required = $required;

        return $this;
    }

    public function nullable(bool $nullable = true): static
    {
        $this->nullable = $nullable;

        return $this;
    }
}
