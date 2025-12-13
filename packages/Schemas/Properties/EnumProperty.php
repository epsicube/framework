<?php

declare(strict_types=1);

namespace Epsicube\Schemas\Properties;

use Closure;
use Epsicube\Schemas\Contracts\EnumResolver;
use Epsicube\Schemas\Exporters\FilamentComponentsExporter;
use Epsicube\Schemas\Exporters\JsonSchemaExporter;
use Epsicube\Schemas\Exporters\LaravelPromptsFormExporter;
use Epsicube\Schemas\Exporters\LaravelValidatorExporter;
use Epsicube\Schemas\Resolvers\DynamicEnumResolver;
use Epsicube\Schemas\Resolvers\StaticEnumResolver;
use Epsicube\Schemas\Types\EnumCase;
use Epsicube\Schemas\Types\UndefinedValue;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Component;
use Filament\Support\Enums\Operation;
use LogicException;

use function Laravel\Prompts\select;

class EnumProperty extends BaseProperty
{
    protected ?EnumResolver $resolver = null;

    public function default(string|int|null|Closure $default): static
    {
        $this->default = $default;

        return $this;
    }

    public function resolver(?EnumResolver $resolver): static
    {
        $this->resolver = $resolver;

        return $this;
    }

    public function cases(EnumCase ...$cases): static
    {
        if ($this->resolver !== null) {
            throw new LogicException('Enum resolver is already defined. Enum cases cannot be redeclared.');
        }
        $this->resolver = new StaticEnumResolver(...$cases);

        return $this;
    }

    /**
     * @param  Closure():array<EnumCase>  $callback
     * @return $this
     */
    public function dynamic(Closure $callback): static
    {
        if ($this->resolver !== null) {
            throw new LogicException('Enum resolver is already defined. Dynamic enum cannot be redeclared.');
        }
        $this->resolver = new DynamicEnumResolver($callback);

        return $this;
    }

    protected function getCases(): array
    {
        if ($this->resolver === null) {
            throw new LogicException(
                'No enum resolver has been defined. Call cases() or resolver() before accessing enum cases.'
            );
        }

        return $this->resolver->cases();
    }

    public function toJsonSchema(JsonSchemaExporter $exporter): array
    {
        $cases = $this->getCases();
        $schema = [
            'enum' => array_map(static fn (EnumCase $case) => $case->value(), $cases),
        ];

        $metas = collect($this->resolver->cases())
            ->mapWithKeys(static fn (EnumCase $case) => [$case->value() => $case->meta()])
            ->filter()
            ->all();

        if (! empty($metas)) {
            $schema['$meta'] = $metas;
        }

        return $schema;
    }

    public function toFilamentComponent(string $name, FilamentComponentsExporter $exporter): Component
    {
        $optionsMap = collect($this->getCases())
            ->mapWithKeys(static fn (EnumCase $case) => [$case->value() => $case->label() ?? $case->value()])
            ->filter()
            ->all();

        if ($exporter->operation === Operation::View) {
            return TextEntry::make($name)
                ->formatStateUsing(fn (string|int|null $state) => $optionsMap[$state] ?? null)
                ->inlineLabel();
        }

        return Select::make($name)->options($optionsMap);
    }

    public function askPrompt(?string $name, mixed $value, LaravelPromptsFormExporter $exporter): int|string|null
    {
        $default = ($value instanceof UndefinedValue)
            ? ($this->hasDefault() ? $this->getDefault() : null)
            : $value;

        $options = collect($this->getCases())->mapWithKeys(
            static fn (EnumCase $case) => [$case->value() => $case->label() ?? $case->value()]
        );
        if ($this->isNullable()) {
            $options['__null__'] = 'NULL';
        }

        $selected = select(
            label: $this->getTitle() ?? $name,
            options: $options,
            default: ($default === null && $this->isNullable()) ? '__null__' : $default,
            hint: $this->getDescription() ?? '',
        );

        return $selected === '__null__' ? null : $selected;
    }

    public function resolveValidationRules(mixed $value, LaravelValidatorExporter $exporter): array
    {
        return [
            'in:'.implode(',',
                array_map(static fn (EnumCase $case) => $case->value(), $this->resolver->cases())
            ),
        ];
    }
}
