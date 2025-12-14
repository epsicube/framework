<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Integrations\Administration\Resources\Events;

use BackedEnum;
use EpsicubeModules\ExecutionPlatform\Enum\Icons;
use EpsicubeModules\ExecutionPlatform\Integrations\Administration\Resources\Events\Pages\ListEvents;
use EpsicubeModules\ExecutionPlatform\Integrations\Administration\Resources\Events\Pages\ViewEvent;
use EpsicubeModules\ExecutionPlatform\Integrations\Administration\Resources\Events\Schemas\EventInfolist;
use EpsicubeModules\ExecutionPlatform\Integrations\Administration\Resources\Events\Tables\EventsTable;
use EpsicubeModules\ExecutionPlatform\Models\ExecutionEvent;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class EventResource extends Resource
{
    protected static ?string $model = ExecutionEvent::class;

    protected static string|BackedEnum|null $navigationIcon = Icons::EVENT;

    protected static ?int $navigationSort = 1000;

    protected static string|null|UnitEnum $navigationGroup = 'Execution Engine';

    public static function infolist(Schema $schema): Schema
    {
        return EventInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EventsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEvents::route('/'),
            'view'  => ViewEvent::route('/{record}'),
        ];
    }
}
