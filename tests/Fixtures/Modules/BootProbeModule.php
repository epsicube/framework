<?php

declare(strict_types=1);

namespace Epsicube\Tests\Fixtures\Modules;

use Epsicube\Support\Contracts\IsModule;
use Epsicube\Support\Modules\Module;
use Illuminate\Support\ServiceProvider;

final class BootProbeModule extends ServiceProvider implements IsModule
{
    public function module(): Module
    {
        return Module::make('tests::boot-probe', '1.0.0')
            ->providers(self::class);
    }

    public function register(): void
    {
        $events = $this->app->bound('tests.provider.events')
            ? $this->app->make('tests.provider.events')
            : [];

        $events[] = 'register';

        $this->app->instance('tests.provider.events', $events);
    }

    public function boot(): void
    {
        $events = $this->app->bound('tests.provider.events')
            ? $this->app->make('tests.provider.events')
            : [];

        $events[] = 'boot';

        $this->app->instance('tests.provider.events', $events);
    }
}
