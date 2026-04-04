<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Events;

use EpsicubeModules\ExecutionPlatform\Models\WorkflowModel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WorkflowStarted
{
    use Dispatchable, SerializesModels;

    public function __construct(public WorkflowModel $workflow) {}
}
