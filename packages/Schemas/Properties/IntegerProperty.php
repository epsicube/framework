<?php

declare(strict_types=1);

namespace Epsicube\Schemas\Properties;

use Closure;
use Epsicube\Schemas\Exporters\FilamentComponentsExporter;
use Epsicube\Schemas\Exporters\JsonSchemaExporter;
use Epsicube\Schemas\Exporters\LaravelPromptsFormExporter;
use Epsicube\Schemas\Exporters\LaravelValidatorExporter;
use Epsicube\Schemas\Schema;
use Epsicube\Schemas\Types\UndefinedValue;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Component;
use Filament\Support\Enums\Operation;
use Illuminate\Validation\ValidationException;
use Laravel\Prompts\TextPrompt;

class IntegerProperty extends BaseProperty
{
    protected ?int $minimum = null;

    protected ?int $maximum = null;

    protected bool $exclusiveMinimum = false;

    protected bool $exclusiveMaximum = false;

    protected ?int $multipleOf = null;

    public function default(int|null|Closure $default): static
    {
        $this->default = $default;

        return $this;
    }

    public function minimum(int $minimum, bool $exclusive = false): static
    {
        $this->minimum = $minimum;
        $this->exclusiveMinimum = $exclusive;

        return $this;
    }

    public function maximum(int $maximum, bool $exclusive = false): static
    {

        $this->maximum = $maximum;
        $this->exclusiveMaximum = $exclusive;

        return $this;
    }

    public function multipleOf(int $multiple): static
    {
        $this->multipleOf = $multiple;

        return $this;
    }

    public function toJsonSchema(JsonSchemaExporter $exporter): array
    {
        $schema = [
            'type' => 'integer',
        ];

        if ($this->minimum !== null) {
            if ($this->exclusiveMinimum) {
                $schema['exclusiveMinimum'] = $this->minimum;
            } else {
                $schema['minimum'] = $this->minimum;
            }
        }

        if ($this->maximum !== null) {
            if ($this->exclusiveMaximum) {
                $schema['exclusiveMaximum'] = $this->maximum;
            } else {
                $schema['maximum'] = $this->maximum;
            }
        }

        if ($this->multipleOf !== null && $this->multipleOf > 0) {
            $schema['multipleOf'] = $this->multipleOf;
        }

        return $schema;
    }

    public function toFilamentComponent(string $name, FilamentComponentsExporter $exporter): Component
    {
        if ($exporter->operation === Operation::View) {
            return TextEntry::make($name)
                ->numeric(0)
                ->inlineLabel()
                ->placeholder(__('No selection'));
        }

        return TextInput::make($name)
            ->integer()
            ->maxValue($this->maximum) // Exclusive not possible
            ->minValue($this->minimum) // Exclusive not possible
            ->step($this->multipleOf);
    }

    public function askPrompt(?string $name, mixed $value, LaravelPromptsFormExporter $exporter): ?int
    {
        $default = ($value instanceof UndefinedValue)
            ? ($this->hasDefault() ? $this->getDefault() : null)
            : $value;

        $prompt = new TextPrompt(
            label: $this->getTitle() ?? $name,
            default: (string) $default,
            validate: function (string $raw) use ($name) {
                // Accept empty input if nullable
                if ($raw === '' && $this->isNullable()) {
                    return null;
                }

                $value = filter_var($raw, FILTER_VALIDATE_INT);
                if ($value === false) {
                    return 'Value must be a valid integer';
                }

                $s = Schema::create('', properties: [$name => $this]);
                try {
                    $s->toValidator([$name => $value])->validate();
                } catch (ValidationException $e) {
                    return implode("\n  ⚠ ", $e->errors()[$name]);
                }

                return null;
            },
            hint: $this->getDescription() ?? ''
        );

        $isNull = false;
        if ($this->isNullable()) {
            $prompt->placeholder = ' — Press CTRL+Del or Enter to set null —';
            $prompt->on('key', function ($key) use (&$prompt, &$isNull): void {
                if ($key === "\e[3;5~") { // Ctrl + Delete
                    $isNull = true;
                    $prompt->state = 'submit';
                }
            });
        }

        $input = $prompt->prompt();

        return $isNull ? null : ($input === '' && $this->isNullable() ? null : (int) $input);
    }

    public function resolveValidationRules(mixed $value, LaravelValidatorExporter $exporter): array
    {
        $rules = ['integer'];

        if ($this->minimum !== null) {
            if ($this->exclusiveMinimum) {
                $rules[] = function (string $attribute, $value, callable $fail): void {
                    if ((int) $value <= $this->minimum) {
                        $fail(__('The :attribute must be greater than :value.', ['value' => $this->minimum]));
                    }
                };
            } else {
                $rules[] = "min:{$this->minimum}";
            }
        }
        if ($this->maximum !== null) {
            if ($this->exclusiveMaximum) {
                $rules[] = function (string $attribute, $value, callable $fail): void {
                    if ((int) $value >= $this->maximum) {
                        $fail(__('The :attribute must be less than :value.', ['value' => $this->maximum]));
                    }
                };
            } else {
                $rules[] = "max:{$this->maximum}";
            }
        }

        if ($this->multipleOf !== null && $this->multipleOf > 0) {
            $rules[] = "multiple_of:{$this->multipleOf}";
        }

        return $rules;
    }
}
