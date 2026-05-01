<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Integrations\Administration\Resources\Mailers\RelationManagers;

use EpsicubeModules\MailingSystem\Integrations\Administration\Resources\Outboxes\OutboxResource;
use Filament\Resources\RelationManagers\RelationManager;

class OutboxesRelationManager extends RelationManager
{
    protected static string $relationship = 'outboxes';

    protected static ?string $relatedResource = OutboxResource::class;

    public function isReadOnly(): bool
    {
        return true;
    }
}
