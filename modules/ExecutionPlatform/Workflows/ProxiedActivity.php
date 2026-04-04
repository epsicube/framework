<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Workflows;

use EpsicubeModules\ExecutionPlatform\Facades\Activities;
use Workflow\Activity;

class ProxiedActivity extends Activity
{
    public $tries = 2;

    public function execute(string $identifier, array $configuration = []): ?array
    {
        $execution = Activities::run($identifier, $configuration);

        return $execution->output;
    }
}
