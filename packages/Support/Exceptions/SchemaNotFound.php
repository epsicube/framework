<?php

declare(strict_types=1);

namespace Epsicube\Support\Exceptions;

use RuntimeException;
use Throwable;

class SchemaNotFound extends RuntimeException
{
    public function __construct(string $group, int $code = 0, ?Throwable $previous = null)
    {
        $message = "Schema not found for group '{$group}'.";

        parent::__construct($message, $code, $previous);
    }
}
