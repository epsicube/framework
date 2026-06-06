<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Integrations\Administration\Resources\InboxAccounts\Pages;

use EpsicubeModules\MailingSystem\Integrations\Administration\Resources\InboxAccounts\InboxAccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInboxAccounts extends ListRecords
{
    protected static string $resource = InboxAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
