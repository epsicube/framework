<?php

declare(strict_types=1);

namespace Epsicube\Support\Exceptions;

use RuntimeException;
use Throwable;

class DuplicateItemException extends RuntimeException
{
    public static int $errorCode = 927;

    public function __construct(string $identifier, ?Throwable $previous = null)
    {
        parent::__construct(
            "item with identifier '{$identifier}' already registered.",
            code: static::$errorCode,
            previous: $previous
        );
    }
}
