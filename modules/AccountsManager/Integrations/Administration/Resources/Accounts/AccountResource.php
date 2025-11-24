<?php

declare(strict_types=1);

namespace UniGaleModules\AccountsManager\Integrations\Administration\Resources\Accounts;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UniGaleModules\AccountsManager\Integrations\Administration\Resources\Accounts\Pages\CreateAccount;
use UniGaleModules\AccountsManager\Integrations\Administration\Resources\Accounts\Pages\EditAccount;
use UniGaleModules\AccountsManager\Integrations\Administration\Resources\Accounts\Pages\ListAccounts;
use UniGaleModules\AccountsManager\Integrations\Administration\Resources\Accounts\Schemas\AccountForm;
use UniGaleModules\AccountsManager\Integrations\Administration\Resources\Accounts\Tables\AccountsTable;
use UniGaleModules\AccountsManager\Models\Account;

class AccountResource extends Resource
{
    protected static ?string $model = Account::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return AccountForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AccountsTable::configure($table);
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
            'index'  => ListAccounts::route('/'),
            'create' => CreateAccount::route('/create'),
            'edit'   => EditAccount::route('/{record}/edit'),
        ];
    }
}
