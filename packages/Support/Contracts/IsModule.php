<?php

declare(strict_types=1);

namespace Epsicube\Support\Contracts;

use Epsicube\Support\Modules\Module;

interface IsModule
{
    public function module(): Module;
}
