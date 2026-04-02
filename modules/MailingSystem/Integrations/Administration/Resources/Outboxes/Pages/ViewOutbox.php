<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Integrations\Administration\Resources\Outboxes\Pages;

use EpsicubeModules\MailingSystem\Integrations\Administration\Resources\Outboxes\OutboxResource;
use Filament\Resources\Pages\ViewRecord;

class ViewOutbox extends ViewRecord
{
    protected static string $resource = OutboxResource::class;
}
