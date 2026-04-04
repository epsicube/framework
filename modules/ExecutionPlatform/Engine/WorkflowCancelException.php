<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Engine;

use Exception;
use Throwable;

class WorkflowCancelException extends Exception
{
    public function __construct(
        string $message = '',
        protected ?string $reason = null,
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }
}
