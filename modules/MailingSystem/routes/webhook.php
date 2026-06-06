<?php

declare(strict_types=1);

use EpsicubeModules\MailingSystem\Http\Controllers\WebhookController;

Route::post('/mailing-system/_webhook/{driver}', [WebhookController::class, 'handle'])->name('mailing-system.webhook');
Route::fallback(function () {
    abort(404);
});
