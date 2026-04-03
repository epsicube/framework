<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Http\Controllers;

use EpsicubeModules\MailingSystem\Contracts\HasWebhooks;
use EpsicubeModules\MailingSystem\Facades\Drivers;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class WebhookController
{
    public function handle(string $driver, Request $request): Response
    {
        $driver = Drivers::safeGet($driver);
        if (! ($driver instanceof HasWebhooks)) {
            return response()->noContent();
        }

        Log::debug(sprintf("[MAILING SYSTEM] Webhook event for driver '%s' received", $driver->identifier()));

        $events = $driver->parseWebhookEvent($request);
        if (empty($events)) {
            return response(['status' => 'unprocessable']);
        }
        Log::debug(sprintf("[MAILING SYSTEM] %d Webhook event for driver '%s' processed successfully", count($events), $driver->identifier()));

        foreach ($events as $event) {
            event($event);
        }

        return response(['status' => 'accepted']);
    }
}
