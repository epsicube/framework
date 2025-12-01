<?php

declare(strict_types=1);

namespace UniGale\Support;

use Closure;
use InvalidArgumentException;

class OptionsDefinition
{
    /**
     * @var array<string,array{
     *      type: string,
     *      autoload: bool,
     *      default: mixed|Closure|null
     * }>
     */
    protected array $options = [];

    /**
     * Register a new option.
     *
     * @param  string  $key  The unique identifier for the option.
     * @param  bool|null  $autoload  Whether this option should be preloaded on boot. Defaults to false.
     * @param  mixed|Closure|null  $default  A static default value or a closure returning a value.
     */
    public function add(
        string $key,
        string $type = 'string', // <- todo ENUM or INTERFACE (wait for schemas)
        ?bool $autoload = false,
        mixed $default = null,
    ): static {
        if (isset($this->options[$key])) {
            throw new InvalidArgumentException("Option '{$key}' already defined.");
        }
        $this->options[$key] = [
            'type'     => $type,
            'autoload' => (bool) $autoload,
            'default'  => $default,
        ];

        return $this;
    }

    public function getAutoloads(): array
    {
        return array_keys(array_filter($this->options, fn ($d) => $d['autoload']));
    }

    public function getDefaultValue(string $key): mixed
    {
        if (! isset($this->options[$key])) {
            throw new InvalidArgumentException("Option '{$key}' is not defined.");
        }

        $default = $this->options[$key]['default'];

        return value($default);
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->options);
    }

    public function all(): array
    {
        return $this->options;
    }

    public static function make(): static
    {
        return new static;
    }
}
