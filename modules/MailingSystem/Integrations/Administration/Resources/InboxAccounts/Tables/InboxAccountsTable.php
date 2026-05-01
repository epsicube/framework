<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Integrations\Administration\Resources\InboxAccounts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InboxAccountsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')
                ->label(__('ID'))
                ->sortable()
                ->toggleable(),
            TextColumn::make('name')->label(__('Name'))->sortable()->searchable(),
            IconColumn::make('is_active')->label(__('Active'))->boolean()->sortable(),
            TextColumn::make('host')->label(__('Host'))->sortable()->searchable(),
            TextColumn::make('username')->label(__('Username'))->sortable()->searchable(),
            TextColumn::make('folder')->label(__('Folder'))->sortable()->toggleable(),
            TextColumn::make('encryption')->label(__('Encryption'))->badge()->placeholder(__('None'))->sortable(),
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
