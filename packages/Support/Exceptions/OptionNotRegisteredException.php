<?php

declare(strict_types=1);

namespace UniGale\Support\Exceptions;

use RuntimeException;
use Throwable;

class OptionNotRegisteredException extends RuntimeException
{
    public function __construct(string $key, ?string $moduleIdentifier = null, int $code = 0, ?Throwable $previous = null)
    {
        $scope = $moduleIdentifier !== null ? "module '{$moduleIdentifier}'" : 'global scope';
        $message = "Option '{$key}' not registered in {$scope}.";

        parent::__construct($message, $code, $previous);
    }
}
