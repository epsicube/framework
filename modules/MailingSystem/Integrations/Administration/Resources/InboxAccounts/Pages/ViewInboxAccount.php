<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Integrations\Administration\Resources\InboxAccounts\Pages;

use EpsicubeModules\MailingSystem\Integrations\Administration\Resources\InboxAccounts\InboxAccountResource;
use EpsicubeModules\MailingSystem\Models\InboxAccount;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewInboxAccount extends ViewRecord
{
    protected static string $resource = InboxAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('toggle_active')->outlined()
                ->label(fn (InboxAccount $record) => $record->is_active ? __('Deactivate') : __('Activate'))
                ->icon(fn (InboxAccount $record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                ->action(fn (InboxAccount $record) => $record->update(['is_active' => ! $record->is_active]))
                ->color(fn (InboxAccount $record) => $record->is_active ? 'warning' : 'success'),

            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
