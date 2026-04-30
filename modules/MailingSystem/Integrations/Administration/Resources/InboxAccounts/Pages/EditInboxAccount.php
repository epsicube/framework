<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Integrations\Administration\Resources\InboxAccounts\Pages;

use EpsicubeModules\MailingSystem\Integrations\Administration\Resources\InboxAccounts\InboxAccountResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditInboxAccount extends EditRecord
{
    protected static string $resource = InboxAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
