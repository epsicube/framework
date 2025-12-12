<?php

declare(strict_types=1);

namespace Epsicube\Schemas\Properties;

use Closure;
use Epsicube\Schemas\Exporters\FilamentComponentsExporter;
use Epsicube\Schemas\Exporters\JsonSchemaExporter;
use Epsicube\Schemas\Exporters\LaravelPromptsFormExporter;
use Epsicube\Schemas\Exporters\LaravelValidatorExporter;
use Epsicube\Schemas\Filament\NullableBooleanStateCast;
use Epsicube\Schemas\Types\UndefinedValue;
use Filament\Forms\Components\ToggleButtons;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Component;
use Filament\Support\Enums\Operation;
use Filament\Support\Icons\Heroicon;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;

class BooleanProperty extends BaseProperty
{
    protected bool $accepted = false;

    public function default(bool|null|Closure $default): static
    {
        $this->default = $default;

        return $this;
    }

    public function accepted(bool $accepted = true): static
    {
        $this->accepted = $accepted;

        return $this;
    }

    public function toJsonSchema(JsonSchemaExporter $exporter): array
    {
        return $this->accepted ? ['const' => true] : ['type' => 'boolean'];
    }

    public function toFilamentComponent(string $name, FilamentComponentsExporter $exporter): Component
    {
        if ($exporter->operation === Operation::View) {
            return TextEntry::make($name)
                ->formatStateUsing(fn ($state) => match (true) {
                    $state                      => __('Yes'),
                    $state !== null && ! $state => __('No'),
                })
                ->placeholder(__('No selection'))
                ->icon(fn ($state) => match (true) {
                    $state                      => Heroicon::OutlinedCheckCircle,
                    $state !== null && ! $state => Heroicon::OutlinedXCircle,
                })->color(fn ($state) => match (true) {
                    $state                      => 'success',
                    $state !== null && ! $state => 'danger',
                })->iconColor(fn ($state) => match (true) {
                    $state                      => 'success',
                    $state !== null && ! $state => 'danger',
                })->inlineLabel();

        }

        $component = ToggleButtons::make($name)
            ->rules($this->accepted ? ['accepted'] : [])
            ->grouped()
            ->inline();

        if ($this->isNullable()) {
            $component->options([
                1 => __('filament-forms::components.toggle_buttons.boolean.true'),
                0 => __('filament-forms::components.toggle_buttons.boolean.false'),
                2 => __('No selection'),
            ])->colors([2 => 'gray', 1 => 'success', 0 => 'danger'])
                ->icons([1 => Heroicon::Check, 0 => Heroicon::XMark, 2 => Heroicon::Minus])
                ->stateCast(new NullableBooleanStateCast);
        } else {
            $component->boolean();
        }

        return $component;
    }

    public function askPrompt(?string $name, mixed $value, LaravelPromptsFormExporter $exporter): ?bool
    {
        $default = ($value instanceof UndefinedValue)
            ? ($this->hasDefault() ? $this->getDefault() : null)
            : $value;

        if ($this->isNullable()) {
            return match (select(
                label: $this->getTitle() ?? $name,
                options: $this->accepted ? [
                    1 => 'Yes',
                    2 => 'No selection',
                ] : [
                    1 => 'Yes',
                    0 => 'No',
                    2 => 'No selection',
                ],
                default: match (true) {
                    $default                        => 1,
                    $default !== null && ! $default => 0,
                    default                         => 2,
                },
                hint: $this->getDescription() ?? '',
            )) {
                1       => true,
                0       => false,
                default => null,
            };
        }

        return confirm(
            label: $this->getTitle() ?? $name,
            default: filter_var($default, FILTER_VALIDATE_BOOLEAN),
            required: $this->accepted,
            hint: $this->getDescription() ?? '',
        );

    }

    public function resolveValidationRules(mixed $value, LaravelValidatorExporter $exporter): array
    {
        $rules = ['boolean'];

        if ($this->accepted) {
            $rules[] = 'accepted';
        }

        return $rules;
    }
}
