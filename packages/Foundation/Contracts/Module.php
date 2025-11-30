<?php

declare(strict_types=1);

namespace UniGale\Foundation\Contracts;

use UniGale\Foundation\ModuleIdentity;

interface Module extends Registrable
{
    public function identity(): ModuleIdentity;
}
