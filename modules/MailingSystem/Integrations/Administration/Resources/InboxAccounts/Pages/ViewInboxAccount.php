<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Integrations\Administration\Resources\InboxAccounts\Pages;

use EpsicubeModules\MailingSystem\Integrations\Administration\Resources\InboxAccounts\InboxAccountResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewInboxAccount extends ViewRecord
{
    protected static string $resource = InboxAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
