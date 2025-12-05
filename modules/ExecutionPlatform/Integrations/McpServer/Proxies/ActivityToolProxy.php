<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Integrations\McpServer\Proxies;

use EpsicubeModules\ExecutionPlatform\Contracts\Activity;
use EpsicubeModules\McpServer\Contracts\Tool;

class ActivityToolProxy implements Tool
{
    public function __construct(protected Activity $activity) {}

    public function identifier(): string
    {
        return "activity:{$this->activity->identifier()}";
    }

    public function label(): string
    {
        return $this->activity->label();
    }

    public function description(): string
    {
        return $this->activity->description();
    }

    public function inputSchema(): array
    {
        return $this->activity->inputSchema();
    }

    public function handle(array $inputs = []): ?array
    {
        return $this->activity->handle($inputs);
    }

    public function outputSchema(): array
    {
        return $this->activity->outputSchema();
    }
}
