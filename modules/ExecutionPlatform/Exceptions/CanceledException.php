<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Exceptions;

use RuntimeException;
use Throwable;

class CanceledException extends RuntimeException
{
    public static int $errorCode = 4000;

    public function __construct(protected string $reason = '', ?Throwable $previous = null)
    {
        parent::__construct($reason, static::$errorCode, $previous);
    }

    public function getReason(): string
    {
        return $this->reason;
    }
}
