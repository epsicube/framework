<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Integrations\Administration\Resources\Outboxes;

use BackedEnum;
use EpsicubeModules\MailingSystem\Integrations\Administration\Enums\ApplicationGroup;
use EpsicubeModules\MailingSystem\Integrations\Administration\Resources\Outboxes\Pages\ListOutboxes;
use EpsicubeModules\MailingSystem\Integrations\Administration\Resources\Outboxes\Pages\ViewOutbox;
use EpsicubeModules\MailingSystem\Integrations\Administration\Resources\Outboxes\Schemas\OutboxInfolist;
use EpsicubeModules\MailingSystem\Integrations\Administration\Resources\Outboxes\Tables\OutboxesTable;
use EpsicubeModules\MailingSystem\Models\Outbox;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class OutboxResource extends Resource
{
    protected static ?string $model = Outbox::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxStack;

    protected static ?int $navigationSort = 1;

    protected static string|null|UnitEnum $navigationGroup = ApplicationGroup::MAILS;

    protected static ?string $slug = '/mails/outbox';

    public static function table(Table $table): Table
    {
        return OutboxesTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return OutboxInfolist::configure($schema);
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
            'index' => ListOutboxes::route('/'),
            'view'  => ViewOutbox::route('/{record}'),
        ];
    }
}
