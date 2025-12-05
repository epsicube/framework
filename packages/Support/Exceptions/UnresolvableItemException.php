<?php

declare(strict_types=1);

namespace Epsicube\Support\Exceptions;

use Epsicube\Support\Registry;
use RuntimeException;
use Throwable;

class UnresolvableItemException extends RuntimeException
{
    public static int $errorCode = 929;

    public function __construct(string $identifier, Registry $registry, ?Throwable $previous = null)
    {
        $registryClassName = $registry::class;
        parent::__construct(
            "Registry '{$registryClassName}' doesn't have item with identifier '{$identifier}'.",
            code: static::$errorCode,
            previous: $previous
        );
    }
}
