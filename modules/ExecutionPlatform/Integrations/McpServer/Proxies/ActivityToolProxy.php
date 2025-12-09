<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Integrations\McpServer\Proxies;

use Epsicube\Schemas\Schema;
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

    public function inputSchema(Schema $schema): void
    {
        $this->activity->inputSchema($schema);
    }

    public function handle(array $input = []): ?array
    {
        return $this->activity->handle($input);
    }

    public function outputSchema(Schema $schema): void
    {
        $this->activity->outputSchema($schema);
    }
}
