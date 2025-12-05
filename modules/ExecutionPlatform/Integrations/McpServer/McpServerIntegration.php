<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Integrations\McpServer;

use EpsicubeModules\ExecutionPlatform\Contracts\Activity;
use EpsicubeModules\ExecutionPlatform\Contracts\Workflow;
use EpsicubeModules\ExecutionPlatform\Facades\Activities;
use EpsicubeModules\ExecutionPlatform\Facades\Workflows;
use EpsicubeModules\ExecutionPlatform\Integrations\McpServer\Proxies\ActivityToolProxy;
use EpsicubeModules\ExecutionPlatform\Integrations\McpServer\Proxies\WorkflowToolProxy;
use EpsicubeModules\McpServer\Facades\Tools;

class McpServerIntegration
{
    public static function handle(): void
    {
        // Keep resolved to ensure Activities are registered before sending them to tools
        Tools::resolved(function () {
            Tools::register(...array_values(
                array_map(fn (Activity $a) => new ActivityToolProxy($a), Activities::all())
            ));
            // TODO workflow
            // Tools::register(...array_values(
            //     array_map(fn (Workflow $w) => new WorkflowToolProxy($w), Workflows::all())
            // ));
        });
    }
}
