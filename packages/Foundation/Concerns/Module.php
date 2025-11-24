<?php

declare(strict_types=1);

namespace UniGale\Foundation\Concerns;

use Illuminate\Support\ServiceProvider;
use UniGale\Foundation\Contracts\Registrable;

abstract class Module extends ServiceProvider implements Registrable
{
    abstract public function name(): string;

    abstract public function version(): string;

    abstract public function author(): string;

    public function description(): ?string
    {
        return null;
    }

    public static function make(): static
    {
        return new static(app());
    }
}
