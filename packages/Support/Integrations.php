<?php

declare(strict_types=1);

namespace UniGale\Support;

use Closure;

class Integrations
{
    /**
     * @var array<string, array{enabled: list<callable>,disabled: list<callable>}>
     */
    protected array $registrations = [];

    public function forModule(
        string $identifier,
        Closure|callable|null $whenEnabled = null,
        Closure|callable|null $whenDisabled = null,
    ): static {

        $this->registrations[$identifier]['enabled'] ??= [];
        $this->registrations[$identifier]['disabled'] ??= [];

        if ($whenEnabled) {
            $this->registrations[$identifier]['enabled'][] = $whenEnabled;
        }

        if ($whenDisabled) {
            $this->registrations[$identifier]['disabled'][] = $whenDisabled;
        }

        return $this;
    }

    /**
     * @return array<string, array{ enabled: list<callable>, disabled: list<callable> }>
     */
    public function registrations(): array
    {
        return $this->registrations;
    }

    public static function make(): static
    {
        return new static;
    }
}
