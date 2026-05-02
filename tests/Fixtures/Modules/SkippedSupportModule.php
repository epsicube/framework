<?php

declare(strict_types=1);

namespace Epsicube\Tests\Fixtures\Modules;

use Epsicube\Support\Contracts\IsModule;
use Epsicube\Support\Modules\Conditions\Callback;
use Epsicube\Support\Modules\Module;
use Epsicube\Support\Modules\Support;
use Epsicube\Support\Modules\Supports;

final class SkippedSupportModule implements IsModule
{
    public function module(): Module
    {
        return Module::make('tests::skipped-support', '1.0.0')
            ->supports(function (Supports $supports): void {
                $supports->add(
                    Support::for(
                        condition: (new Callback(
                            callback: fn (): bool => false,
                            name: 'Skipped support',
                            failMessage: 'This support should be skipped.',
                        ))->skipWhen(fn (): bool => true),
                        whenSkipped: function (): void {
                            app()->instance('tests.supports.skipped', true);
                        },
                    ),
                );
            });
    }
}
