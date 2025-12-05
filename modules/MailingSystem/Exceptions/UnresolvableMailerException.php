<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Exceptions;

use RuntimeException;
use Throwable;

class UnresolvableMailerException extends RuntimeException
{
    public static int $errorCode = 129;

    public function __construct(string $name, ?Throwable $previous = null)
    {
        parent::__construct(
            "Mailer with name '{$name}' not registered.",
            code: static::$errorCode,
            previous: $previous
        );
    }
}
