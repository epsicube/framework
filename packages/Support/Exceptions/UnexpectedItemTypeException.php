<?php

declare(strict_types=1);

namespace Epsicube\Support\Exceptions;

use Epsicube\Support\Registry;
use RuntimeException;
use Throwable;

class UnexpectedItemTypeException extends RuntimeException
{
    public static int $errorCode = 932;

    public function __construct(string $identifier, Registry $registry, ?Throwable $previous = null)
    {
        $registryClassname = $registry::class;
        parent::__construct(
            "Item with identifier '{$identifier}' cannot be registered in registry '{$registryClassname}' because item type is not of type '{$registry->getRegistrableType()}' .",
            code: static::$errorCode,
            previous: $previous
        );
    }
}
