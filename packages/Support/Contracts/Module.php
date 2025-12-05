<?php

declare(strict_types=1);

namespace Epsicube\Support\Contracts;

use Epsicube\Support\ModuleIdentity;

interface Module extends Registrable
{
    public function identity(): ModuleIdentity;
}
