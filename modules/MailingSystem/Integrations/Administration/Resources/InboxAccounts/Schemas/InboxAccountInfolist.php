<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Integrations\Administration\Resources\InboxAccounts\Schemas;

use EpsicubeModules\MailingSystem\Integrations\Administration\Filament\Components\InboxMessagePreview;
use EpsicubeModules\MailingSystem\Models\InboxAccount;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InboxAccountInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make(fn (InboxAccount $record) => $record->name)->description(function (InboxAccount $record): string {
                $encryption = $record->encryption ? " ({$record->encryption})" : '';

                return "imap://{$record->username}@{$record->host}:{$record->port}/{$record->folder}{$encryption}";
            })->columns(2),

            InboxMessagePreview::make('id')->visible(fn (InboxAccount $record): bool => $record->is_active),
        ]);
    }
}
