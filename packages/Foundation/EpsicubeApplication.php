<?php

declare(strict_types=1);

namespace Epsicube\Foundation;

use Epsicube\Foundation\Bootstrap\BootstrapEpsicube;
use Illuminate\Foundation\Application;
use Override;

class EpsicubeApplication extends Application
{
    #[Override]
    public function bootstrapWith(array $bootstrappers): void
    {
        parent::bootstrapWith(array_merge([BootstrapEpsicube::class], $bootstrappers));
    }
}
