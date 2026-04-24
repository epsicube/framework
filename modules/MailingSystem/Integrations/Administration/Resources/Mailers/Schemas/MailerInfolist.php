<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Integrations\Administration\Resources\Mailers\Schemas;

use EpsicubeModules\MailingSystem\Facades\Drivers;
use EpsicubeModules\MailingSystem\Integrations\Administration\Contracts\HasMailerAdministrationPanel;
use EpsicubeModules\MailingSystem\Models\Mailer;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Operation;

class MailerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('General'))->schema([
                TextEntry::make('name')->label(__('Name')),
                TextEntry::make('driver')
                    ->label(__('Driver'))
                    ->badge()
                    ->formatStateUsing(fn (string $state) => Drivers::safeGet($state)?->label() ?? $state),
            ])->columns(2),

            Section::make(__('Sender'))->schema([
                TextEntry::make('from_email')->label(__('Email')),
                TextEntry::make('from_name')->label(__('Name'))->placeholder('—'),
            ])->columns(2),

            Section::make(__('Configuration'))->columnSpanFull()
                ->statePath('configuration')
                ->schema(function (Mailer $record) {
                    $driverInstance = Drivers::safeGet($record->driver);
                    if (! $driverInstance) {
                        return [];
                    }

                    $schema = \Epsicube\Schemas\Schema::create('config');
                    $driverInstance->inputSchema($schema);

                    return $schema->toFilamentComponents(Operation::View);
                })
                ->hiddenWhenAllChildComponentsHidden(),

            Section::make(__('Provider integration'))
                ->description(__('Inspect and manage provider-side resources linked to this mailer.'))
                ->columnSpanFull()
                ->schema(function (Schema $schema, Mailer $record) {
                    $driverInstance = Drivers::safeGet($record->driver);
                    if (! ($driverInstance instanceof HasMailerAdministrationPanel)) {
                        return [];
                    }

                    return $driverInstance::configureDriverPanel($schema, $record->configuration ?? []);
                })
                ->hiddenWhenAllChildComponentsHidden(),
        ]);
    }
}
