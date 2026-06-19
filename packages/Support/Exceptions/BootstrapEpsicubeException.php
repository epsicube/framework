<?php

declare(strict_types=1);

namespace Epsicube\Support\Exceptions;

use RuntimeException;

class BootstrapEpsicubeException extends RuntimeException
{
    public static function dueToNonFunctionalOptionsStore(): static
    {
        return new static('The options store is not functional, so Epsicube modules cannot be bootstrapped.');
    }
}
