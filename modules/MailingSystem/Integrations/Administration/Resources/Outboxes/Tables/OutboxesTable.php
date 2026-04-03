<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Integrations\Administration\Resources\Outboxes\Tables;

use EpsicubeModules\MailingSystem\Enums\OutboxStatus;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OutboxesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label(__('ID'))
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('subject')
                    ->label(__('Subject'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('to_messages_count')
                    ->counts('toMessages')
                    ->label(__('To'))
                    ->badge()
                    ->color('info')
                    ->suffix(' '.__('to')),

                TextColumn::make('cc_messages_count')
                    ->counts('ccMessages')
                    ->label(__('Cc'))
                    ->badge()
                    ->color('gray')
                    ->suffix(' '.__('cc')),

                TextColumn::make('bcc_messages_count')
                    ->counts('bccMessages')
                    ->label(__('Bcc'))
                    ->badge()
                    ->color('warning')
                    ->suffix(' '.__('bcc')),

                TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->formatStateUsing(fn (OutboxStatus $state) => $state->label())
                    ->tooltip(fn (OutboxStatus $state) => $state->description())
                    ->color(fn (OutboxStatus $state): string => match ($state) {
                        OutboxStatus::PENDING => 'info',
                        OutboxStatus::SENT    => 'success',
                        OutboxStatus::ERROR   => 'danger',
                    })
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label(__('Date'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordactions([
                ViewAction::make(),
            ]);
    }
}
