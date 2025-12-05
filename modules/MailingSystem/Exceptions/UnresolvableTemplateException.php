<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Exceptions;

use RuntimeException;
use Throwable;

class UnresolvableTemplateException extends RuntimeException
{
    public static int $errorCode = 139;

    public function __construct(string $name, ?Throwable $previous = null)
    {
        parent::__construct(
            "Template with name '{$name}' not registered.",
            code: static::$errorCode,
            previous: $previous
        );
    }
}
