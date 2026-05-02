<?php

declare(strict_types=1);

namespace Epsicube\Tests\Fixtures\Modules;

use Epsicube\Support\Contracts\IsModule;
use Epsicube\Support\Modules\Dependencies;
use Epsicube\Support\Modules\Module;

final class CircularAlphaModule implements IsModule
{
    public function module(): Module
    {
        return Module::make('tests::circular-alpha', '1.0.0')
            ->dependencies(function (Dependencies $dependencies): void {
                $dependencies->module('tests::circular-beta');
            });
    }
}
