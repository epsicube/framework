<?php

declare(strict_types=1);

namespace Epsicube\Tests\Modules\ExecutionPlatform\Fixtures;

use Epsicube\Support\Contracts\IsModule;
use Epsicube\Support\Modules\Identity;
use Epsicube\Support\Modules\Module;
use Epsicube\Support\Modules\Support;
use Epsicube\Support\Modules\Supports;
use Illuminate\Support\ServiceProvider;

final class ExecutionPlatformProbeModule extends ServiceProvider implements IsModule
{
    public function module(): Module
    {
        return Module::make('tests::execution-platform-probe', '1.0.0')
            ->providers(self::class)
            ->identity(fn (Identity $identity) => $identity
                ->name('Execution Platform Probe')
                ->author('Tests')
                ->description('Registers test activities and workflows for Execution Platform.'))
            ->supports(fn (Supports $supports) => $supports->add(
                Support::forModule('core::execution-platform', ExecutionPlatformTestIntegration::handle(...)),
            ));
    }
}
