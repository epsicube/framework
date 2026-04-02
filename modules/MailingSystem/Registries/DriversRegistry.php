<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Registries;

use Epsicube\Support\Registry;
use EpsicubeModules\MailingSystem\Contracts\Driver;

/**
 * @extends Registry<Driver>
 */
class DriversRegistry extends Registry
{
    public function getRegistrableType(): string
    {
        return Driver::class;
    }
}
