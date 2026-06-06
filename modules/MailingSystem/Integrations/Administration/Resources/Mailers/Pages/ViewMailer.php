<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Integrations\Administration\Resources\Mailers\Pages;

use EpsicubeModules\MailingSystem\Facades\Drivers;
use EpsicubeModules\MailingSystem\Integrations\Administration\Contracts\HasMailerAdministrationPanel;
use EpsicubeModules\MailingSystem\Integrations\Administration\Enums\Icons;
use EpsicubeModules\MailingSystem\Integrations\Administration\Resources\Mailers\MailerResource;
use EpsicubeModules\MailingSystem\Integrations\Administration\Resources\Mailers\RelationManagers\OutboxesRelationManager;
use EpsicubeModules\MailingSystem\Models\Mailer;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewMailer extends ViewRecord
{
    protected static string $resource = MailerResource::class;

    public function getRelationManagers(): array
    {
        return [
            'outboxes' => OutboxesRelationManager::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('provider_infolist')
                ->label(__('Advanced'))
                ->outlined()->color('gray')->icon(Icons::SETTINGS)
                ->visible(fn (Mailer $record) => Drivers::safeGet($record->driver) instanceof HasMailerAdministrationPanel)
                ->modalFooterActions([])
                ->schema(function (Schema $schema, Mailer $record) {
                    $driverInstance = Drivers::safeGet($record->driver);
                    if (! ($driverInstance instanceof HasMailerAdministrationPanel)) {
                        return [];
                    }

                    return $driverInstance::configureDriverPanel($schema, $record->configuration ?? []);
                }),

            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
