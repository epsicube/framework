<?php

declare(strict_types=1);

namespace Epsicube\Tests\Modules\Administration\Fixtures;

use Epsicube\Support\Contracts\IsModule;
use Epsicube\Support\Modules\Identity;
use Epsicube\Support\Modules\Module;
use Epsicube\Support\Modules\Support;
use Epsicube\Support\Modules\Supports;
use Illuminate\Support\ServiceProvider;

final class AdministrationProbeModule extends ServiceProvider implements IsModule
{
    public function module(): Module
    {
        return Module::make('tests::administration-probe', '1.0.0')
            ->providers(self::class)
            ->identity(fn (Identity $identity) => $identity->name('Administration Probe'))
            ->supports(fn (Supports $supports) => $supports->add(
                Support::forModule('core::administration', AdministrationProbeIntegration::handle(...)),
            ));
    }
}
