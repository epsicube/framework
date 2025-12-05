<?php

declare(strict_types=1);

namespace EpsicubeModules\McpServer\Registries;

use Epsicube\Support\Registry;
use EpsicubeModules\McpServer\Contracts\Resource as ResourceContract;

/**
 * @extends Registry<ResourceContract>
 */
class ResourcesRegistry extends Registry
{
    /**
     * {@inheritDoc}
     */
    public function getRegistrableType(): string
    {
        return ResourceContract::class;
    }
}
