<?php

declare(strict_types=1);

namespace Epsicube\Schemas\Types;

use ReflectionClass;
use ReflectionException;

class EnumCase
{
    public static function make(int|string $value, ?string $label = null, ?array $meta = []): static
    {
        return new static($value, $label, $meta);
    }

    public function __construct(
        protected int|string $value,
        protected ?string $label = null,
        protected ?array $meta = []
    ) {}

    /**
     * Keep to ensure var_export and require work for cache
     *
     * @throws ReflectionException
     */
    public static function __set_state(array $properties): static
    {
        $instance = (new ReflectionClass(static::class))->newInstanceWithoutConstructor();

        foreach ($properties as $key => $value) {
            if (property_exists($instance, $key)) {
                $instance->{$key} = $value;
            }
        }

        return $instance;
    }

    public function value(): int|string
    {
        return $this->value;
    }

    public function label(): ?string
    {
        return $this->label;
    }

    public function meta(): ?array
    {
        return $this->meta;
    }
}
