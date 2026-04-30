<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Integrations\Administration\Resources\InboxAccounts\Schemas;

use EpsicubeModules\MailingSystem\Models\InboxAccount;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InboxAccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('General'))->schema([
                TextInput::make('name')->label(__('Name'))->required()->maxLength(255)->unique(ignoreRecord: true),
                Toggle::make('is_active')->label(__('Active'))->default(true),
            ])->columns(2),

            Section::make(__('IMAP connection'))->schema([
                TextInput::make('host')->label(__('Host'))->required()->maxLength(255),
                TextInput::make('port')->label(__('Port'))->numeric()->required()->default(993)->minValue(1)->maxValue(65535),
                Select::make('encryption')->label(__('Encryption'))->options([
                    'ssl' => 'SSL',
                    'tls' => 'TLS',
                ])->default('ssl')->nullable(),
                TextInput::make('username')->label(__('Username'))->required()->maxLength(255),
                TextInput::make('password')->label(__('Password'))
                    ->password()
                    ->revealable()
                    ->required(fn (?InboxAccount $record): bool => ! $record?->exists)
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->afterStateHydrated(function (TextInput $component): void {
                        $component->state(null);
                    }),
                TextInput::make('folder')->label(__('Folder'))->required()->default('INBOX')->maxLength(255),
            ])->columns(2),
        ]);
    }
}
