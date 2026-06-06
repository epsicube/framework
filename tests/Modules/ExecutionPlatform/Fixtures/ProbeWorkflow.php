<?php

declare(strict_types=1);

namespace Epsicube\Tests\Modules\ExecutionPlatform\Fixtures;

use EpsicubeModules\ExecutionPlatform\Contracts\Workflow;

final class ProbeWorkflow implements Workflow
{
    public function identifier(): string
    {
        return 'tests::probe-workflow';
    }

    public function label(): string
    {
        return 'Probe Workflow';
    }

    public function run(int $execution_id, array $input = []): mixed
    {
        return [
            'execution_id' => $execution_id,
            'input'        => $input,
        ];
    }
}
