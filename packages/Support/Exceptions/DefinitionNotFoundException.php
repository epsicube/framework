<?php

declare(strict_types=1);

namespace UniGale\Support\Exceptions;

use RuntimeException;
use Throwable;

class DefinitionNotFoundException extends RuntimeException
{
    public function __construct(string $group, int $code = 0, ?Throwable $previous = null)
    {
        $message = "Definition not found for group '{$group}'.";

        parent::__construct($message, $code, $previous);
    }
}
