<?php

declare(strict_types=1);

namespace UniGaleModules\ExecutionPlatform\Integrations\McpServer;

use UniGaleModules\ExecutionPlatform\Contracts\Activity;
use UniGaleModules\ExecutionPlatform\Contracts\Workflow;
use UniGaleModules\ExecutionPlatform\Facades\Activities;
use UniGaleModules\ExecutionPlatform\Facades\Workflows;
use UniGaleModules\ExecutionPlatform\Integrations\McpServer\Proxies\ActivityToolProxy;
use UniGaleModules\ExecutionPlatform\Integrations\McpServer\Proxies\WorkflowToolProxy;
use UniGaleModules\McpServer\Facades\Tools;

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
