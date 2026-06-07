<?php

declare(strict_types=1);

namespace Epsicube\Tests\Fixtures\Modules;

use Epsicube\Support\Contracts\IsModule;
use Epsicube\Support\Modules\Module;
use Epsicube\Tests\Fixtures\Providers\DiscoveredProbeServiceProvider;

final class PreventDiscoveredProviderModule implements IsModule
{
    public function module(): Module
    {
        return Module::make('tests::prevent-discovered-provider', '1.0.0')
            ->preventProviders(DiscoveredProbeServiceProvider::class);
    }
}
