<?php

declare(strict_types=1);

namespace UniGale\Foundation\Concerns;

trait Makeable
{
    public static function make(...$args): static
    {
        return new static(...$args);
    }
}
