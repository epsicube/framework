<?php

declare(strict_types=1);

namespace Epsicube\Schemas\Exporters;

use Closure;
use Epsicube\Schemas\Contracts\LaravelRulesExportable;
use Epsicube\Schemas\Contracts\Property;
use Epsicube\Schemas\Contracts\SchemaExporter;
use Epsicube\Schemas\Overrides\SchemaValidator;
use Epsicube\Schemas\Properties\ObjectProperty;
use Epsicube\Schemas\Schema;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class LaravelValidatorExporter implements SchemaExporter
{
    /** @var array<string, array<int, string|Closure>> */
    protected array $rules = [];

    /** @var list<string> */
    protected array $pathStack = [];

    public function __construct(protected array $data = [], protected array $messages = [], protected array $attributes = [], protected array $prepend = []) {}

    public function exportSchema(Schema $schema): ValidatorContract
    {
        $this->rules = [];
        $this->pathStack = [];

        $root = ObjectProperty::make()
            ->title($schema->title())
            ->properties($schema->properties())
            ->description($schema->description())
            ->optional(false) // avoid injecting property on root
            ->nullable(false) // avoid injecting property on root
            ->additionalProperties(false);

        $root->resolveValidationRules($this->data, $this);

        // Customize the resolver temporarily to handle empty string
        Validator::resolver(function ($translator, $data, $rules, $messages) {
            return new SchemaValidator($translator, $data, $rules, $messages);
        });
        try {
            return Validator::make($this->data, $this->rules, $this->messages, $this->attributes);
        } finally {
            // Force regenerate singleton to remove custom resolver
            app()->forgetInstance('validator');
            Validator::clearResolvedInstances();
        }
    }

    public function exportChild(Property $field, string $path, mixed $value = null): void
    {
        if (! ($field instanceof LaravelRulesExportable)) {
            throw new RuntimeException(
                'Cannot export field that does not implement LaravelRulesExportable'
            );
        }

        $prepend = $this->prepend;

        if (! $field->isOptional()) {
            $prepend[] = 'present'; // don't use required, fails when null
        }

        if ($field->isNullable()) {
            $prepend[] = 'nullable';
        }

        $this->runWithContext($path, function (string $absPath) use ($field, $value, $prepend): void {
            $this->rules[$absPath] = [...$prepend, ...$field->resolveValidationRules($value, $this)];
        });
    }

    public function setChildRules(string $path, array $rules): void
    {
        $this->runWithContext($path, function (string $absPath) use ($rules): void {
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

        $callback(implode('.', array_filter($this->pathStack, fn ($value) => $value !== null && $value !== '')));

        if ($pushed) {
            array_pop($this->pathStack);
        }
    }
}
