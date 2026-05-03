<?php

declare(strict_types=1);

use Epsicube\Foundation\EpsicubeApplication;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return EpsicubeApplication::configure(basePath: dirname(__DIR__))
    ->withRouting()
    ->withMiddleware(function (Middleware $middleware): void {})
    ->withExceptions(function (Exceptions $exceptions): void {})
    ->create();
