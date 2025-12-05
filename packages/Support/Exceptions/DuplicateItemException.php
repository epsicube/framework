<?php

declare(strict_types=1);

namespace Epsicube\Support\Exceptions;

use Epsicube\Support\Registry;
use RuntimeException;
use Throwable;

class DuplicateItemException extends RuntimeException
{
    public static int $errorCode = 927;

    public function __construct(string $identifier, Registry $registry, ?Throwable $previous = null)
    {
        $registryClassname = $registry::class;
        parent::__construct(
            "Registry '{$registryClassname}' already have item with identifier '{$identifier}'.",
            code: static::$errorCode,
            previous: $previous
        );
    }
}
