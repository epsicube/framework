<?php

declare(strict_types=1);

namespace Epsicube\Schemas\Properties;

use Closure;
use Epsicube\Schemas\Contracts\FilamentExportable;
use Epsicube\Schemas\Contracts\JsonSchemaExportable;
use Epsicube\Schemas\Contracts\PromptExportable;
use Epsicube\Schemas\Contracts\Property;
use Epsicube\Schemas\Exporters\FilamentExporter;
use Epsicube\Schemas\Exporters\JsonSchemaExporter;
use Epsicube\Schemas\Exporters\LaravelPromptsFormExporter;
use Epsicube\Schemas\Exporters\LaravelValidationExporter;

/**
 * BaseField provides the core functionality for all native fields in Epsicube Schema.
 *
 * Each native field supports the following exporters:
 * - @see FilamentExporter
 * - @see JsonSchemaExporter
 * - @see LaravelValidationExporter
 * - @see LaravelPromptsFormExporter
 */
abstract class BaseProperty implements FilamentExportable, JsonSchemaExportable, PromptExportable, Property
{
    public static function make(): static
    {
        return new static;
    }

    // ------------------------------
    // Interface properties with hooks
    // ------------------------------
    protected ?string $title = null;

    protected ?string $description = null;

    protected bool $required = false;

    protected bool $nullable = false;

    protected mixed $default = null;

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
        return $this->default instanceof Closure ? call_user_func($this->default) : $this->default;
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
