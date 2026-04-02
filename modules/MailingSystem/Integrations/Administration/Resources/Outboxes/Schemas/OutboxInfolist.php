<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Integrations\Administration\Resources\Outboxes\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OutboxInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('General Information'))
                    ->schema([
                        TextEntry::make('subject')
                            ->label(__('Subject')),
                        TextEntry::make('status')
                            ->label(__('Status'))
                            ->badge()
                            ->color(fn(string $state): string => match ($state) {
                                'pending' => 'gray',
                                'sent' => 'success',
                                'error' => 'danger',
                                default => 'gray',
                            }),
                        TextEntry::make('created_at')
                            ->label(__('Sent at'))
                            ->dateTime(),
                        TextEntry::make('message_id')
                            ->label(__('External ID')),
                    ])->columns(2),

                Section::make(__('Recipients'))
                    ->schema([
                        RepeatableEntry::make('messages')
                            ->label('')
                            ->schema([
                                TextEntry::make('recipient')
                                    ->label(__('Recipient')),
                                TextEntry::make('type')
                                    ->label(__('Type'))
                                    ->badge()
                                    ->color(fn(string $state): string => match ($state) {
                                        'to' => 'info',
                                        'cc' => 'gray',
                                        'bcc' => 'warning',
                                        default => 'gray',
                                    }),
                                TextEntry::make('status')
                                    ->label(__('Status'))
                                    ->badge()
                                    ->color(fn(string $state): string => match ($state) {
                                        'pending' => 'gray',
                                        'sent' => 'success',
                                        'error' => 'danger',
                                        default => 'gray',
                                    }),
                                TextEntry::make('message_id')
                                    ->label(__('ID')),
                            ])
                            ->columns(4),
                    ]),
            ]);
    }
}
