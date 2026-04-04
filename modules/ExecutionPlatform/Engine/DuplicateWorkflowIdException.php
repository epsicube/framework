<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Engine;

use Exception;

class DuplicateWorkflowIdException extends Exception
{
    public function __construct(string $workflowId)
    {
        parent::__construct("Workflow with ID {$workflowId} already exists.");
    }
}
