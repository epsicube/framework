<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Integrations\Administration\Resources\Mailers\Pages;

use EpsicubeModules\MailingSystem\Integrations\Administration\Resources\Mailers\MailerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMailers extends ListRecords
{
    protected static string $resource = MailerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
