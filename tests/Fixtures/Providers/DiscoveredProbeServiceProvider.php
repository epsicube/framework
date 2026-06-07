<?php

declare(strict_types=1);

namespace Epsicube\Tests\Fixtures\Providers;

use Illuminate\Support\ServiceProvider;

final class DiscoveredProbeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->instance('tests.discovered-provider.registered', true);
    }
}
