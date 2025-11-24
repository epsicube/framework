<?php

declare(strict_types=1);

namespace UniGaleModules\Hypercore\Integrations\Administration\Resources\Tenants;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UniGaleModules\Hypercore\Integrations\Administration\Resources\Tenants\Pages\CreateTenant;
use UniGaleModules\Hypercore\Integrations\Administration\Resources\Tenants\Pages\EditTenant;
use UniGaleModules\Hypercore\Integrations\Administration\Resources\Tenants\Pages\ListTenants;
use UniGaleModules\Hypercore\Integrations\Administration\Resources\Tenants\Pages\ViewTenant;
use UniGaleModules\Hypercore\Integrations\Administration\Resources\Tenants\Schemas\TenantForm;
use UniGaleModules\Hypercore\Integrations\Administration\Resources\Tenants\Schemas\TenantInfolist;
use UniGaleModules\Hypercore\Integrations\Administration\Resources\Tenants\Tables\TenantsTable;
use UniGaleModules\Hypercore\Models\Tenant;
use UnitEnum;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return __('Site');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Network');
    }

    public static function form(Schema $schema): Schema
    {
        return TenantForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TenantInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TenantsTable::configure($table);
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
            'index'  => ListTenants::route('/'),
            'create' => CreateTenant::route('/create'),
            'view'   => ViewTenant::route('/{record}'),
            'edit'   => EditTenant::route('/{record}/edit'),
        ];
    }
}
