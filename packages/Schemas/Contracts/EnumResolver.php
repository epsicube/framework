<?php

declare(strict_types=1);

namespace Epsicube\Schemas\Contracts;

use Epsicube\Schemas\Types\EnumCase;

interface EnumResolver
{
    /**
     * Returns the list of available options.
     *
     * @return EnumCase[]
     */
    public function cases(): array;
}
