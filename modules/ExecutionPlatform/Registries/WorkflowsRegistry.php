<?php

declare(strict_types=1);

namespace UniGaleModules\ExecutionPlatform\Registries;

use UniGale\Foundation\Concerns\Registry;
use UniGaleModules\ExecutionPlatform\Contracts\Workflow;

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
