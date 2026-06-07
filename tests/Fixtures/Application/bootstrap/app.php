<?php

declare(strict_types=1);

use Epsicube\Foundation\Actions\InjectEpsicube;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return tap(Application::configure(basePath: dirname(__DIR__))
    ->withRouting()
    ->withMiddleware(function (Middleware $middleware): void {})
    ->withExceptions(function (Exceptions $exceptions): void {})
    ->create(), function (Application $app) {
        $app->beforeBootstrapping(LoadEnvironmentVariables::class, InjectEpsicube::configure(...));
    });
