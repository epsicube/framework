<?php

declare(strict_types=1);

use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;

Route::domain('epsicube.internal')->group(function () {
    Route::get('/health', function () {
        $exception = null;

        try {
            Event::dispatch(new DiagnosingHealth);
        } catch (Throwable $e) {
            if (app()->hasDebugModeEnabled()) {
                throw $e;
            }

            report($e);

            $exception = $e->getMessage();
        }

        return response()->json([
            'status'    => $exception ? 'unhealthy' : 'ok',
            'timestamp' => now()->toIso8601String(),
            'error'     => $exception,
            'hostname'  => gethostname(),
        ], status: $exception ? 500 : 200);
    });

    Route::any('{any}', function () {
        return response('', 204);
    })->where('any', '.*');
});
