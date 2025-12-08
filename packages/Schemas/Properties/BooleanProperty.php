<?php

declare(strict_types=1);

namespace Epsicube\Schemas\Properties;

use Closure;
use Epsicube\Schemas\Exporters\FilamentExporter;
use Epsicube\Schemas\Exporters\JsonSchemaExporter;
use Epsicube\Schemas\Exporters\LaravelPromptsFormExporter;
use Filament\Forms\Components\ToggleButtons;
use Filament\Infolists\Components\IconEntry;
use Filament\Schemas\Components\Component;
use Filament\Support\Enums\Operation;
use Filament\Support\Icons\Heroicon;

use function Laravel\Prompts\confirm;

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

    public function toFilamentComponent(string $name, FilamentExporter $exporter): Component
    {
        if ($exporter->operation === Operation::View) {
            return IconEntry::make($name)
                ->boolean()->inlineLabel()
                ->label($this->getTitle())->default($this->getDefault())
                ->hintIcon(Heroicon::OutlinedInformationCircle)->hintColor('info')
                ->hintIconTooltip($this->getDescription());
        }

        // Don't use toggle because cannot handle null state
        return ToggleButtons::make($name)
            ->required($this->isRequired())
            ->rules($this->accepted ? ['accepted'] : [])
            ->boolean()->grouped()
            ->inline()
            ->label($this->getTitle())->default($this->getDefault())
            ->hintIcon(Heroicon::OutlinedInformationCircle)->hintColor('info')
            ->hintIconTooltip($this->getDescription());
    }

    public function askPrompt(?string $name, mixed $value, LaravelPromptsFormExporter $exporter): ?bool
    {
        // TODO handling nullable using ternary multi-select
        return confirm(
            label: $this->getTitle() ?? $name,
            default: (is_bool($value) ? $value : $this->getDefault()) ?? false,
            required: $this->accepted,
            hint: $this->getDescription() ?? '',
        );
    }
}
