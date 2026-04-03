<?php

declare(strict_types=1);

use EpsicubeModules\MailingSystem\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Log;

Route::post('/mailing-system/_webhook/{driver}', [WebhookController::class, 'handle'])->name('mailing-system.webhook');
Route::fallback(function () {
    Log::info('test', request()->all());
    abort(404);
});
