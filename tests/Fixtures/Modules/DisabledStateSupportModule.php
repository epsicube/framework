<?php

declare(strict_types=1);

namespace Epsicube\Tests\Fixtures\Modules;

use Epsicube\Support\Contracts\IsModule;
use Epsicube\Support\Enums\ModuleCondition;
use Epsicube\Support\Modules\Module;
use Epsicube\Support\Modules\Support;
use Epsicube\Support\Modules\Supports;

final class DisabledStateSupportModule implements IsModule
{
    public function module(): Module
    {
        return Module::make('tests::disabled-state-support', '1.0.0')
            ->supports(fn (Supports $supports) => $supports->add(
                Support::forModule(
                    'tests::target',
                    whenPass: function (): void {
                        app()->instance('tests.supports.disabled-state.pass', true);
                    },
                    whenFail: function (): void {
                        app()->instance('tests.supports.disabled-state.fail', true);
                    },
                    state: ModuleCondition::DISABLED,
                ),
            ));
    }
}
