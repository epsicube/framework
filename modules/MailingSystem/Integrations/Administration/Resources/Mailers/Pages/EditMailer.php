<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Integrations\Administration\Resources\Mailers\Pages;

use EpsicubeModules\MailingSystem\Integrations\Administration\Resources\Mailers\MailerResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMailer extends EditRecord
{
    protected static string $resource = MailerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
