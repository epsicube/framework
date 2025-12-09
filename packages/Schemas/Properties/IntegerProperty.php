<?php

declare(strict_types=1);

namespace Epsicube\Schemas\Properties;

use Closure;
use Epsicube\Schemas\Exporters\FilamentExporter;
use Epsicube\Schemas\Exporters\JsonSchemaExporter;
use Epsicube\Schemas\Exporters\LaravelPromptsFormExporter;
use Epsicube\Schemas\Exporters\LaravelValidationExporter;
use Exception;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Component;
use Filament\Support\Enums\Operation;

use function Laravel\Prompts\text;

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

    public function toFilamentComponent(string $name, FilamentExporter $exporter): Component
    {
        if ($exporter->operation === Operation::View) {
            return TextEntry::make($name)->numeric(0)->inlineLabel();
        }

        return TextInput::make($name)
            ->integer()
            ->maxValue($this->maximum) // Exclusive not possible
            ->minValue($this->minimum) // Exclusive not possible
            ->step($this->multipleOf);
    }

    public function askPrompt(?string $name, mixed $value, LaravelPromptsFormExporter $exporter): ?int
    {
        // TODO handling null correctly, is not the inverse of required

        $input = text(
            label: $this->getTitle() ?? $name,
            default: ($value !== null ? $value : $this->getDefault()) ?? '',
            required: $this->isRequired(),
            validate: function (string $value) {
                try {
                    $value = filter_var($value, FILTER_VALIDATE_INT, FILTER_THROW_ON_FAILURE);
                } catch (Exception $e) {
                    return 'Invalid integer value.';
                }

                if ($this->minimum !== null) {
                    if ($this->exclusiveMinimum && $value <= $this->minimum) {
                        return "Value must be greater than {$this->minimum}.";
                    }
                    if (! $this->exclusiveMinimum && $value < $this->minimum) {
                        return "Value must be at least {$this->minimum}.";
                    }
                }

                if ($this->maximum !== null) {
                    if ($this->exclusiveMaximum && $value >= $this->maximum) {
                        return "Value must be less than {$this->maximum}.";
                    }
                    if (! $this->exclusiveMaximum && $value > $this->maximum) {
                        return "Value must be at most {$this->maximum}.";
                    }
                }

                if ($this->multipleOf !== null && $this->multipleOf > 0 && $value % $this->multipleOf !== 0) {
                    return "Value must be a multiple of {$this->multipleOf}.";
                }

                return null; // Valid
            },
            hint: $this->getDescription() ?? '',
        );

        return $input === '' ? null : (int) $input;
    }

    public function resolveValidationRules(mixed $value, LaravelValidationExporter $exporter): array
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
