<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Engine;

use EpsicubeModules\ExecutionPlatform\Enum\WorkflowEventType;
use Exception;

class WorkflowHistoryMismatchException extends Exception
{
    public function __construct(string $expectedAction, WorkflowEventType $actualEvent, int $index)
    {
        parent::__construct("Workflow history mismatch at step #{$index}. Expected action '{$expectedAction}', but found event type '{$actualEvent->name}' in history.");
    }
}
