<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Integrations\Administration\Resources\InboxAccounts\Pages;

use EpsicubeModules\MailingSystem\Integrations\Administration\Resources\InboxAccounts\InboxAccountResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInboxAccount extends CreateRecord
{
    protected static string $resource = InboxAccountResource::class;
}
