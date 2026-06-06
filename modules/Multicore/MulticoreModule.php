<?php

declare(strict_types=1);

namespace EpsicubeModules\Multicore;

use Epsicube\Support\Contracts\IsModule;
use Epsicube\Support\Facades\Epsicube;
use Epsicube\Support\Modules\Condition;
use Epsicube\Support\Modules\Identity;
use Epsicube\Support\Modules\Module;
use Epsicube\Support\Modules\Requirements;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;

class MulticoreModule extends ServiceProvider implements IsModule
{
    public function module(): Module
    {
        return Module::make(
            'core::multicore',
            version: Epsicube::resolveComposerVersion('epsicube/framework', 'epsicube/module-multicore')
        )
            ->providers(static::class)
            ->identity(fn (Identity $identity) => $identity
                ->name(__('Multi-Core'))
                ->author('Core Team')
                ->description(__('Turn it into a multi-app manager, enabling multi-tenant setups.'))
            )->requirements(fn (Requirements $requirements) => $requirements->add(
                Condition::closure(
                    'FrankenPHP', fn () => PHP_SAPI === 'frankenphp',
                    'FrankenPHP is installed.', 'FrankenPHP is required.',
                )->skipWhen(fn () => app()->runningInConsole())
            ));
    }

    public function register(): void
    {
        // Tenant already provided in the container
        if ($this->app->bound('tenant')) {
            $activeTenant = $this->app->make('tenant');
            $this->configureTenant($activeTenant);

            return;
        }

        // Keep normal boot if the current request targets the central domain
        if ($this->isCentral()) {
            return;
        }

        // Resolve the tenant based on the current request context
        $tenant = $this->resolveTenant();
        if (! $tenant) {
            abort(404);
        }

        $this->app->terminate();

        /** @var \Illuminate\Contracts\Foundation\Application $app */
        $app = require Application::inferBasePath().'/bootstrap/app.php';
        $app->bind('tenant', fn () => $tenant);
        $app->handleRequest(Request::capture());
        $app->terminate();
        exit(0);
    }

    public function isCentral(): bool
    {
        return true;
    }

    public function resolveTenant(): ?string
    {
        return 'TENANT_ID';
    }

    protected function configureTenant(string $tenant): void {}
}
