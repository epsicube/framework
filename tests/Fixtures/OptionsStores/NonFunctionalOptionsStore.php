<?php

declare(strict_types=1);

namespace Epsicube\Tests\Fixtures\OptionsStores;

use Epsicube\Schemas\Types\UndefinedValue;
use Epsicube\Support\Contracts\OptionsStore;
use LogicException;

final class NonFunctionalOptionsStore implements OptionsStore
{
    public function isFunctional(): bool
    {
        return false;
    }

    public function get(string $key, string $group): mixed
    {
        return new UndefinedValue;
    }

    public function set(string $key, mixed $value, string $group): void
    {
        throw new LogicException('The non-functional options store cannot persist values.');
    }

    public function delete(string $key, string $group): void
    {
        throw new LogicException('The non-functional options store cannot delete values.');
    }

    public function all(string $group): array
    {
        return [];
    }

    public function clear(string $group): void
    {
        throw new LogicException('The non-functional options store cannot clear values.');
    }
}
