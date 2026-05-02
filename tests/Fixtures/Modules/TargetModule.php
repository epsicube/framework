<?php

declare(strict_types=1);

namespace Epsicube\Tests\Fixtures\Modules;

use Epsicube\Support\Contracts\IsModule;
use Epsicube\Support\Modules\Module;

final class TargetModule implements IsModule
{
    public function module(): Module
    {
        return Module::make('tests::target', '1.0.0');
    }
}
