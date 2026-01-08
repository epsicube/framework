<?php

declare(strict_types=1);

namespace EpsicubeModules\JsonRpcServer\Registries;

use Epsicube\Support\Registry;
use EpsicubeModules\JsonRpcServer\Concerns\Procedure;

/**
 * @extends Registry<Procedure>
 */
class ProceduresRegistry extends Registry
{
    /**
     * {@inheritDoc}
     */
    public function getRegistrableType(): string
    {
        return Procedure::class;
    }
}
