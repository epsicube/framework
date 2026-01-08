<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Integrations\JsonRpcServer;

use EpsicubeModules\ExecutionPlatform\Integrations\JsonRpcServer\Procedures\ExecutionProcedure;
use EpsicubeModules\JsonRpcServer\Facades\Procedures;

class JsonRpcServerIntegration
{
    public static function handle(): void
    {
        Procedures::register(new ExecutionProcedure);
    }
}
