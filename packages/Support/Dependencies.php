<?php

declare(strict_types=1);

namespace Epsicube\Support;

class Dependencies
{
    public function __construct(string ...$moduleIdentifiers)
    {
        foreach ($moduleIdentifiers as $moduleIdentifier) {
            $this->addRequirement($moduleIdentifier);
        }
    }

    protected array $requiredModules = [];

    public function requiredModules(): array
    {
        return array_keys($this->requiredModules);
    }

    public function addRequirement(string $moduleIdentifier): static
    {
        $this->requiredModules[$moduleIdentifier] = true;

        return $this;
    }

    public static function make(string ...$moduleIdentifiers): static
    {
        return new static(...$moduleIdentifiers);
    }
}
