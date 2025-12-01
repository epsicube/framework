<?php

declare(strict_types=1);

namespace UniGaleModules\ExecutionPlatform\Registries;

use UniGale\Support\Registry;
use UniGaleModules\ExecutionPlatform\Contracts\Activity;

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
