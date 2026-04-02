<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Integrations\Administration\Resources\Outboxes\Pages;

use EpsicubeModules\MailingSystem\Integrations\Administration\Resources\Outboxes\OutboxResource;
use Filament\Resources\Pages\ListRecords;

class ListOutboxes extends ListRecords
{
    protected static string $resource = OutboxResource::class;
}
