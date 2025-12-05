<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Integrations\McpServer\Proxies;

use EpsicubeModules\ExecutionPlatform\Contracts\Workflow;
use EpsicubeModules\McpServer\Contracts\Tool;

class WorkflowToolProxy implements Tool
{
    public function __construct(protected Workflow $workflow) {}

    public function identifier(): string
    {
        return "workflow:{$this->workflow->identifier()}";
    }

    public function label(): string
    {
        return $this->workflow->label();
    }

    public function description(): string
    {
        // TODO
        return $this->workflow->description();
    }

    public function handle(array $inputs = []): ?array
    {
        // TODO
        return $this->workflow->handle($inputs);
    }
}
