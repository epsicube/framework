<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Events;

use EpsicubeModules\ExecutionPlatform\Models\WorkflowModel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ActivityScheduled
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public WorkflowModel $workflow,
        public string $activityClass,
        public array $input,
        public int $index
    ) {}
}
