<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Integrations\Administration\Resources\Mailers\Tables;

use EpsicubeModules\MailingSystem\Facades\Drivers;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MailersTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')
                ->label(__('ID'))
                ->sortable()
                ->toggleable(),
            TextColumn::make('name')->label(__('Name'))->sortable(),

            TextColumn::make('driver')->label(__('Driver'))
                ->formatStateUsing(fn (string $state) => Drivers::safeGet($state)?->label() ?? $state)
                ->badge()
                ->sortable(),

            TextColumn::make('from_email')->label(__('From Email'))->sortable(),
            TextColumn::make('from_name')->label(__('From Name'))->toggleable()->sortable(),

        ])->filters([
            //
        ])->recordActions([
            ViewAction::make(),
            EditAction::make(),
            DeleteAction::make(),
        ])->toolbarActions([
            BulkActionGroup::make([
                DeleteBulkAction::make(),
            ]),
        ]);
    }
}
