<?php

declare(strict_types=1);

namespace UniGale\Support\Exceptions;

use RuntimeException;
use Throwable;
use UniGale\Support\Registry;

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
