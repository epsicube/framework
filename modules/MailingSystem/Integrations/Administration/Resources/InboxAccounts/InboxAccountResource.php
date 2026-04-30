<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Integrations\Administration\Resources\InboxAccounts;

use BackedEnum;
use EpsicubeModules\MailingSystem\Integrations\Administration\Enums\ApplicationGroup;
use EpsicubeModules\MailingSystem\Integrations\Administration\Enums\Icons;
use EpsicubeModules\MailingSystem\Integrations\Administration\Resources\InboxAccounts\Pages\CreateInboxAccount;
use EpsicubeModules\MailingSystem\Integrations\Administration\Resources\InboxAccounts\Pages\EditInboxAccount;
use EpsicubeModules\MailingSystem\Integrations\Administration\Resources\InboxAccounts\Pages\ListInboxAccounts;
use EpsicubeModules\MailingSystem\Integrations\Administration\Resources\InboxAccounts\Pages\ViewInboxAccount;
use EpsicubeModules\MailingSystem\Integrations\Administration\Resources\InboxAccounts\Schemas\InboxAccountForm;
use EpsicubeModules\MailingSystem\Integrations\Administration\Resources\InboxAccounts\Schemas\InboxAccountInfolist;
use EpsicubeModules\MailingSystem\Integrations\Administration\Resources\InboxAccounts\Tables\InboxAccountsTable;
use EpsicubeModules\MailingSystem\Models\InboxAccount;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class InboxAccountResource extends Resource
{
    protected static ?string $model = InboxAccount::class;

    protected static string|BackedEnum|null $navigationIcon = Icons::INBOX_ACCOUNT;

    protected static ?int $navigationSort = 110;

    protected static string|null|UnitEnum $navigationGroup = ApplicationGroup::MAILS;

    protected static ?string $slug = '/mails/inbox-accounts';

    public static function form(Schema $schema): Schema
    {
        return InboxAccountForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return InboxAccountInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InboxAccountsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListInboxAccounts::route('/'),
            'create' => CreateInboxAccount::route('/create'),
            'view'   => ViewInboxAccount::route('/{record}'),
            'edit'   => EditInboxAccount::route('/{record}/edit'),
        ];
    }
}
