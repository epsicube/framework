<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Integrations\Administration\Resources\Mailers;

use BackedEnum;
use EpsicubeModules\MailingSystem\Integrations\Administration\Enums\ApplicationGroup;
use EpsicubeModules\MailingSystem\Integrations\Administration\Resources\Mailers\Pages\CreateMailer;
use EpsicubeModules\MailingSystem\Integrations\Administration\Resources\Mailers\Pages\EditMailer;
use EpsicubeModules\MailingSystem\Integrations\Administration\Resources\Mailers\Pages\ListMailers;
use EpsicubeModules\MailingSystem\Integrations\Administration\Resources\Mailers\Pages\ViewMailer;
use EpsicubeModules\MailingSystem\Integrations\Administration\Resources\Mailers\Schemas\MailerForm;
use EpsicubeModules\MailingSystem\Integrations\Administration\Resources\Mailers\Schemas\MailerInfolist;
use EpsicubeModules\MailingSystem\Integrations\Administration\Resources\Mailers\Tables\MailersTable;
use EpsicubeModules\MailingSystem\Models\Mailer;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class MailerResource extends Resource
{
    protected static ?string $model = Mailer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?int $navigationSort = 100;

    protected static string|null|UnitEnum $navigationGroup = ApplicationGroup::MAILS;

    protected static ?string $slug = '/mails/mailers';

    public static function form(Schema $schema): Schema
    {
        return MailerForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MailerInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MailersTable::configure($table);
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
            'index'  => ListMailers::route('/'),
            'create' => CreateMailer::route('/create'),
            'view'   => ViewMailer::route('/{record}'),
            'edit'   => EditMailer::route('/{record}/edit'),
        ];
    }
}
