<?php

declare(strict_types=1);

namespace UniGale\Support\Exceptions;

use RuntimeException;
use Throwable;

class DefinitionNotFoundException extends RuntimeException
{
    public function __construct(?string $moduleIdentifier = null, int $code = 0, ?Throwable $previous = null)
    {
        $scope = $moduleIdentifier !== null ? "module '{$moduleIdentifier}'" : 'global scope';
        $message = "Definition not found for {$scope}.";

        parent::__construct($message, $code, $previous);
    }
}
