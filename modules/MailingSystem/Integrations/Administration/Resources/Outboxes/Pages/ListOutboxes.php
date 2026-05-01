<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Integrations\Administration\Resources\Outboxes\Pages;

use EpsicubeModules\MailingSystem\Integrations\Administration\Enums\Icons;
use EpsicubeModules\MailingSystem\Integrations\Administration\Resources\Mailers\MailerResource;
use EpsicubeModules\MailingSystem\Integrations\Administration\Resources\Outboxes\OutboxResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListOutboxes extends ListRecords
{
    protected static string $resource = OutboxResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('manage')
                ->label(__('Manage mailers'))
                ->icon(Icons::SETTINGS)
                ->url(MailerResource::getUrl()),
        ];
    }
}
