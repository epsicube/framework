<?php

declare(strict_types=1);

namespace UniGaleModules\MailingSystem\Exceptions;

use RuntimeException;
use Throwable;

class DuplicateMailerException extends RuntimeException
{
    public static int $errorCode = 127;

    public function __construct(string $name, ?Throwable $previous = null)
    {
        parent::__construct(
            "Mailer with name '{$name}' already exists.",
            code: static::$errorCode,
            previous: $previous
        );
    }
}
