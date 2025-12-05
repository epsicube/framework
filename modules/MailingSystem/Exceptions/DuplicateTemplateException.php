<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Exceptions;

use RuntimeException;
use Throwable;

class DuplicateTemplateException extends RuntimeException
{
    public static int $errorCode = 137;

    public function __construct(string $name, ?Throwable $previous = null)
    {
        parent::__construct(
            "Template with name '{$name}' already exists.",
            code: static::$errorCode,
            previous: $previous
        );
    }
}
