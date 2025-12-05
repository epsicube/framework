<?php

declare(strict_types=1);

namespace Epsicube\Support\Exceptions;

use RuntimeException;
use Throwable;

class OptionNotRegisteredException extends RuntimeException
{
    public function __construct(string $key, string $group, int $code = 0, ?Throwable $previous = null)
    {
        $message = "Option '{$key}' not registered in group '{$group}'.";

        parent::__construct($message, $code, $previous);
    }
}
