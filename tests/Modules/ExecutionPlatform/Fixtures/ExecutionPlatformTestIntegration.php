<?php

declare(strict_types=1);

namespace Epsicube\Tests\Modules\ExecutionPlatform\Fixtures;

use EpsicubeModules\ExecutionPlatform\Facades\Activities;
use EpsicubeModules\ExecutionPlatform\Facades\Workflows;

final class ExecutionPlatformTestIntegration
{
    public static function handle(): void
    {
        Activities::register(new ProbeActivity);
        Workflows::register(new ProbeWorkflow);
    }
}
