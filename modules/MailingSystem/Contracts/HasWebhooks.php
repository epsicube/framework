<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Contracts;

use EpsicubeModules\MailingSystem\Events\MessageDeliveryEvent;
use EpsicubeModules\MailingSystem\Events\MessageEngagementEvent;
use Illuminate\Http\Request;

interface HasWebhooks
{
    /**
     * @return (MessageDeliveryEvent|MessageEngagementEvent)[]
     */
    public function parseWebhookEvent(Request $request): array;
}
