<?php

declare(strict_types=1);

namespace UniGaleModules\McpServer\Registries;

use UniGale\Support\Registry;
use UniGaleModules\McpServer\Contracts\Tool;

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
