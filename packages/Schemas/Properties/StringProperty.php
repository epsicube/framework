<?php

declare(strict_types=1);

namespace Epsicube\Schemas\Properties;

use Closure;
use Epsicube\Schemas\Contracts\JsonSchemaExportable;
use Epsicube\Schemas\Enums\StringFormat;
use Epsicube\Schemas\Exporters\FilamentExporter;
use Epsicube\Schemas\Exporters\JsonSchemaExporter;
use Epsicube\Schemas\Exporters\LaravelPromptsFormExporter;
use Epsicube\Schemas\Exporters\LaravelValidatorExporter;
use Epsicube\Schemas\Schema;
use Epsicube\Schemas\Types\UndefinedValue;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Component;
use Filament\Support\Enums\Operation;
use Illuminate\Validation\ValidationException;
use Laravel\Prompts\TextPrompt;

class StringProperty extends BaseProperty implements JsonSchemaExportable
{
    protected ?StringFormat $format = null;

    protected ?int $minLength = null;

    protected ?int $maxLength = null;

    protected ?string $pattern = null;

    public function default(string|null|Closure $default): static
    {
        $this->default = $default;

        return $this;
    }

    public function format(?StringFormat $format = null): static
    {
        $this->format = $format;

        return $this;
    }

    public function minLength(?int $minLength): static
    {
        $this->minLength = $minLength;

        return $this;
    }

    public function maxLength(?int $maxLength): static
    {
        $this->maxLength = $maxLength;

        return $this;
    }

    public function pattern(?string $pattern): static
    {
        $this->pattern = $pattern;

        return $this;
    }

    public function toJsonSchema(JsonSchemaExporter $exporter): array
    {
        $schema = [
            'type' => 'string',
        ];

        if ($this->format !== null && $jsonFormat = $this->format->jsonSchemaFormat()) {
            $schema['format'] = $jsonFormat;
        }

        if ($this->minLength !== null) {
            $schema['minLength'] = $this->minLength;
        }

        if ($this->maxLength !== null) {
            $schema['maxLength'] = $this->maxLength;
        }

        if ($this->pattern !== null) {
            $schema['pattern'] = $this->pattern;
        }

        return $schema;
    }

    public function toFilamentComponent(string $name, FilamentExporter $exporter): Component
    {
        if ($exporter->operation === Operation::View) {
            return (match ($this->format) {
                StringFormat::MARKDOWN  => TextEntry::make($name)->markdown(),
                StringFormat::HTML      => TextEntry::make($name)->html(),
                StringFormat::DATE      => TextEntry::make($name)->date(),
                StringFormat::DATE_TIME => TextEntry::make($name)->dateTime(),
                default                 => TextEntry::make($name)
            })->placeholder(__('Empty'))->inlineLabel();
        }

        return match ($this->format) {
            StringFormat::DATE      => DatePicker::make($name),
            StringFormat::DATE_TIME => DateTimePicker::make($name),
            StringFormat::TIME      => TimePicker::make($name),
            StringFormat::MARKDOWN  => MarkdownEditor::make($name)->fileAttachments(false),
            StringFormat::HTML      => RichEditor::make($name)->fileAttachments(false),
            default                 => TextInput::make($name ?? 'test')
                ->minLength($this->minLength)
                ->maxLength($this->maxLength)
                ->when(! empty($this->pattern), fn (TextInput $component) => $component->regex($this->pattern))
                ->email($this->format === StringFormat::EMAIL)
                ->url($this->format === StringFormat::URL)
                ->ipv4($this->format === StringFormat::IPV4)
                ->ipv6($this->format === StringFormat::IPV6)
                ->uuid($this->format === StringFormat::UUID)
        };
    }

    public function askPrompt(?string $name, mixed $value, LaravelPromptsFormExporter $exporter): ?string
    {

        $default = ($value instanceof UndefinedValue)
            ? ($this->hasDefault() ? $this->getDefault() : null)
            : $value;

        $prompt = new TextPrompt(
            label: $this->getTitle() ?? $name,
            default: (string) $default,
            validate: function (string $value) use ($name) {
                $s = Schema::create('', properties: [$name => $this]);
                try {
                    $s->toValidator([$name => $value])->validate();
                } catch (ValidationException $e) {
                    return implode("\n  ⚠ ", $e->errors()[$name]);
                }

                return null;
            },
            hint: $this->getDescription() ?? '',
        );

        $isNull = false;
        if ($this->isNullable()) {
            $prompt->placeholder = ' — Press CTRL+Del to set null, or Enter for empty —';
            $prompt->on('key', function ($key) use (&$prompt, &$isNull) {
                // Use CTRL_DELETE to set null
                if ($key === "\e[3;5~") {
                    $isNull = true;
                    $prompt->state = 'submit';
                }
            });
        }

        $input = $prompt->prompt();

        return $isNull ? null : $input;
    }

    public function resolveValidationRules(mixed $value, LaravelValidatorExporter $exporter): array
    {
        $rules = ['string', 'sometimes'];

        if ($this->minLength !== null) {
            $rules[] = "min:{$this->minLength}";
        }

        if ($this->maxLength !== null) {
            $rules[] = "max:{$this->maxLength}";
        }

        if (! empty($this->pattern)) {
            $isDelimited = preg_match('/^\/.+\/[a-zA-Z]*$/', $this->pattern) === 1;
            $regex = $isDelimited ? $this->pattern : '/'.str_replace('/', '\/', $this->pattern).'/u';
            $rules[] = "regex:{$regex}";
        }

        if (! empty($this->format)) {
            $formatRules = match ($this->format) {
                StringFormat::EMAIL     => ['email'],
                StringFormat::URL       => ['url'],
                StringFormat::IPV4      => ['ipv4'],
                StringFormat::IPV6      => ['ipv6'],
                StringFormat::UUID      => ['uuid'],
                StringFormat::DATE      => ['date'],
                StringFormat::DATE_TIME => ['date'],
                StringFormat::TIME      => ['date', 'date_format:H:i:s'],
                // Unsupported formats like hostname, phone, duration are intentionally skipped for now.
                default => [],
            };

            $rules = array_merge($rules, $formatRules);
        }

        return $rules;
    }
}
