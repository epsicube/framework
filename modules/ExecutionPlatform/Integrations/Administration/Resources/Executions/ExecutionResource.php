<?php

declare(strict_types=1);

namespace UniGaleModules\ExecutionPlatform\Integrations\Administration\Resources\Executions;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UniGaleModules\ExecutionPlatform\Enum\Icons;
use UniGaleModules\ExecutionPlatform\Integrations\Administration\Resources\Executions\Pages\ListExecutions;
use UniGaleModules\ExecutionPlatform\Integrations\Administration\Resources\Executions\Pages\ViewExecution;
use UniGaleModules\ExecutionPlatform\Integrations\Administration\Resources\Executions\Schemas\ExecutionInfolist;
use UniGaleModules\ExecutionPlatform\Integrations\Administration\Resources\Executions\Tables\ExecutionsTable;
use UniGaleModules\ExecutionPlatform\Models\Execution;
use UnitEnum;

class ExecutionResource extends Resource
{
    protected static ?string $model = Execution::class;

    protected static string|BackedEnum|null $navigationIcon = Icons::EXECUTION;

    protected static ?int $navigationSort = 999;

    protected static string|null|UnitEnum $navigationGroup = 'Execution Engine';

    public static function infolist(Schema $schema): Schema
    {
        return ExecutionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ExecutionsTable::configure($table);
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
            'index' => ListExecutions::route('/'),
            'view'  => ViewExecution::route('/{record}'),
        ];
    }
}
