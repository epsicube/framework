<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Integrations\Administration\Resources\Outboxes\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OutboxesTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
                TextColumn::make('subject')
                    ->label(__('Subject'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('to_messages_count')
                    ->counts('toMessages')
                    ->label(__('To'))
                    ->badge()
                    ->color('info')
                    ->suffix(' ' . __('to')),

                TextColumn::make('cc_messages_count')
                    ->counts('ccMessages')
                    ->label(__('Cc'))
                    ->badge()
                    ->color('gray')
                    ->suffix(' ' . __('cc')),

                TextColumn::make('bcc_messages_count')
                    ->counts('bccMessages')
                    ->label(__('Bcc'))
                    ->badge()
                    ->color('warning')
                    ->suffix(' ' . __('bcc')),

                TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'gray',
                        'sent' => 'success',
                        'error' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label(__('Date'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordactions([
                ViewAction::make(),
            ]);
    }
}
