<?php

declare(strict_types=1);

namespace Epsicube\Tests\Fixtures\Modules;

use Epsicube\Support\Contracts\IsModule;
use Epsicube\Support\Modules\Conditions\Callback;
use Epsicube\Support\Modules\Module;
use Epsicube\Support\Modules\Requirements;

final class MultiRequirementModule implements IsModule
{
    public function module(): Module
    {
        return Module::make('tests::multi-requirement', '1.0.0')
            ->requirements(function (Requirements $requirements): void {
                $requirements->add(
                    new Callback(
                        callback: fn (): bool => false,
                        name: 'First requirement',
                        failMessage: 'The first requirement failed.',
                    ),
                    new Callback(
                        callback: fn (): bool => false,
                        name: 'Second requirement',
                        failMessage: 'The second requirement failed.',
                    ),
                );
            });
    }
}
