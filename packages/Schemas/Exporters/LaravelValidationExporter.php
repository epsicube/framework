<?php

declare(strict_types=1);

namespace Epsicube\Schemas\Exporters;

use Closure;
use Epsicube\Schemas\Contracts\LaravelRulesExportable;
use Epsicube\Schemas\Contracts\Property;
use Epsicube\Schemas\Contracts\SchemaExporter;
use Epsicube\Schemas\Properties\ObjectProperty;
use Epsicube\Schemas\Schema;
use RuntimeException;

class LaravelValidationExporter implements SchemaExporter
{
    /** @var array<string, array<int, string|Closure>> */
    protected array $rules = [];

    /** @var list<string> */
    protected array $pathStack = [];

    public function __construct(protected array $data = [], protected array $prepend = []) {}

    public function exportSchema(Schema $schema): mixed
    {
        $this->rules = [];
        $this->pathStack = [];

        $root = ObjectProperty::make()
            ->title($schema->title())
            ->properties($schema->properties())
            ->description($schema->description())
            ->required(false) // avoid injecting property on root
            ->nullable(false) // avoid injecting property on root
            ->additionalProperties(false);

        $root->resolveValidationRules($this->data, $this);

        return $this->rules;
    }

    public function exportChild(Property $field, string $path, mixed $value = null): void
    {
        if (! ($field instanceof LaravelRulesExportable)) {
            throw new RuntimeException(
                'Cannot export field that does not implement LaravelRulesExportable'
            );
        }

        $prepend = $this->prepend;

        if ($field->isRequired()) {
            $prepend[] = 'present'; // don't use required, fails when null
        }

        if ($field->isNullable()) {
            $prepend[] = 'nullable';
        }

        $this->runWithContext($path, function (string $absPath) use ($field, $value, $prepend) {
            $this->rules[$absPath] = [...$prepend, ...$field->resolveValidationRules($value, $this)];
        });
    }

    public function setChildRules(string $path, array $rules): void
    {
        $this->runWithContext($path, function (string $absPath) use ($rules) {
            $this->rules[$absPath] = $rules;
        });
    }

    protected function runWithContext(?string $path, callable $callback): void
    {
        $pushed = false;

        if ($path !== null && $path !== '') {
            $this->pathStack[] = $path;
            $pushed = true;
        }

        $callback(implode('.', array_filter($this->pathStack)));

        if ($pushed) {
            array_pop($this->pathStack);
        }
    }
}
