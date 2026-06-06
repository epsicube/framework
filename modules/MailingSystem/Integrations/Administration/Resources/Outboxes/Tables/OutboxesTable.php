<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Integrations\Administration\Resources\Outboxes\Tables;

use EpsicubeModules\MailingSystem\Enums\MessageType;
use EpsicubeModules\MailingSystem\Enums\OutboxStatus;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OutboxesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(function (Builder $query) {
                $query->withCount(collect(MessageType::cases())->mapWithKeys(
                    fn (MessageType $type) => ["messages as {$type->value}_messages_count" => fn (Builder $query) => $query->where('type', $type)]
                )->toArray());
            })
            ->columns([
                TextColumn::make('id')
                    ->label(__('ID'))
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('subject')
                    ->label(__('Subject'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('recipients')
                    ->label(__('Recipients'))
                    ->badge()
                    ->state(function ($record): array {
                        $stats = [];
                        foreach (MessageType::cases() as $type) {
                            $count = $record->{"{$type->value}_messages_count"} ?? 0;
                            if ($count > 0) {
                                $stats[$type->value] = "{$count} {$type->value}";
                            }
                        }

                        return $stats;
                    })
                    ->color(fn (string $state): string => match (true) {
                        str_contains($state, 'bcc') => 'warning',
                        str_contains($state, 'cc')  => 'gray',
                        str_contains($state, 'to')  => 'info',
                        default                     => 'gray',
                    }),

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
