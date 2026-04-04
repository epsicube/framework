<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Jobs;

use EpsicubeModules\ExecutionPlatform\Engine\WorkflowEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class WorkflowJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected string $workflowId
    ) {}

    public function uniqueId(): string
    {
        return $this->workflowId;
    }

    public function handle(): void
    {
        $engine = app(WorkflowEngine::class);

        $engine->resumeWithLock($this->workflowId);
    }
}
