<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Exceptions;

use Exception;

class DuplicateWorkflowIdException extends Exception
{
    public function __construct(
        protected string $workflowId
    ) {
        parent::__construct("Workflow with ID {$workflowId} already exists.");
    }

    public function getWorkflowId(): string
    {
        return $this->workflowId;
    }
}
