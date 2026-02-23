<?php

declare(strict_types=1);

namespace Epsicube\Foundation\Events;

use Epsicube\Support\Modules\Module;

class ModuleEnabled
{
    public function __construct(public Module $module)
    {
        //
    }
}
