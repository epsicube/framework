<?php

declare(strict_types=1);

namespace Epsicube\Support\Exceptions;

use RuntimeException;
use Throwable;

class UnresolvableItemException extends RuntimeException
{
    public static int $errorCode = 929;

    public function __construct(string $identifier, ?Throwable $previous = null)
    {
        parent::__construct(
            "item with identifier '{$identifier}' not found.",
            code: static::$errorCode,
            previous: $previous
        );
    }
}
