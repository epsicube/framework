<?php

declare(strict_types=1);

namespace EpsicubeModules\McpServer\Registries;

use Epsicube\Support\Registry;
use EpsicubeModules\McpServer\Contracts\Tool;

/**
 * @extends Registry<Tool>
 */
class ToolsRegistry extends Registry
{
    /**
     * {@inheritDoc}
     */
    public function getRegistrableType(): string
    {
        return Tool::class;
    }
}
