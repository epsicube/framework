<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Integrations\Administration\Resources\InboxAccounts\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InboxAccountInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('General'))->schema([
                TextEntry::make('name')->label(__('Name')),
                IconEntry::make('is_active')->label(__('Active'))->boolean(),
            ])->columns(2),

            Section::make(__('IMAP connection'))->schema([
                TextEntry::make('host')->label(__('Host'))->copyable(),
                TextEntry::make('port')->label(__('Port')),
                TextEntry::make('encryption')->label(__('Encryption'))->placeholder(__('None'))->badge(),
                TextEntry::make('username')->label(__('Username'))->copyable(),
                TextEntry::make('folder')->label(__('Folder')),
            ])->columns(2),
        ]);
    }
}
