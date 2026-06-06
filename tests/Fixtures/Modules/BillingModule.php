<?php

declare(strict_types=1);

namespace Epsicube\Tests\Fixtures\Modules;

use Epsicube\Support\Contracts\IsModule;
use Epsicube\Support\Modules\Conditions\Callback;
use Epsicube\Support\Modules\Module;
use Epsicube\Support\Modules\Requirements;

final class BillingModule implements IsModule
{
    public function module(): Module
    {
        return Module::make('tests::billing', '1.0.0')
            ->requirements(function (Requirements $requirements): void {
                $requirements->add(new Callback(
                    callback: fn (): bool => false,
                    name: 'Unavailable dependency',
                    failMessage: 'The dependency is unavailable.',
                ));
            });
    }
}
