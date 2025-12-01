<?php

declare(strict_types=1);

namespace UniGale\Support\Contracts;

use UniGale\Support\ModuleIdentity;

interface Module extends Registrable
{
    public function identity(): ModuleIdentity;
}
