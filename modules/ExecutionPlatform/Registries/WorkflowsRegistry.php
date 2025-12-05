<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Registries;

use Epsicube\Support\Registry;
use EpsicubeModules\ExecutionPlatform\Contracts\Workflow;

/**
 * @extends Registry<Workflow>
 */
class WorkflowsRegistry extends Registry
{
    public function getRegistrableType(): string
    {
        return Workflow::class;
    }
}
