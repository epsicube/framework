<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Registries;

use Epsicube\Support\Registry;
use EpsicubeModules\ExecutionPlatform\Contracts\Activity;

/**
 * @extends Registry<Activity>
 */
class ActivitiesRegistry extends Registry
{
    public function getRegistrableType(): string
    {
        return Activity::class;
    }
}
