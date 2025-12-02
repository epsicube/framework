<?php

declare(strict_types=1);

namespace UniGaleModules\McpServer\Registries;

use UniGale\Support\Registry;
use UniGaleModules\McpServer\Contracts\Resource as ResourceContract;

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
