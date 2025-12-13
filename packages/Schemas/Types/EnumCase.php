<?php

declare(strict_types=1);

namespace Epsicube\Schemas\Types;

class EnumCase
{
    public function make(int|string $value, ?string $label = null, ?array $meta = []): static
    {
        return new static($value, $label, $meta);
    }

    public function __construct(
        protected int|string $value,
        protected ?string $label = null,
        protected ?array $meta = []
    ) {}

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
