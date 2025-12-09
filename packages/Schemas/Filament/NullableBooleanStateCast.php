<?php

declare(strict_types=1);

namespace Epsicube\Schemas\Filament;

use Filament\Schemas\Components\StateCasts\Contracts\StateCast;

class NullableBooleanStateCast implements StateCast
{
    public function get(mixed $state): mixed
    {
        return match ($state) {
            0, '0' => false,
            1, '1' => true,
            default => null,
        };
    }

    public function set(mixed $state): mixed
    {
        return match ($state) {
            false   => 0,
            true    => 1,
            default => 2,
        };
    }
}
