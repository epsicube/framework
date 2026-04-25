<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Integrations\Administration\Resources\Outboxes\Schemas;

use EpsicubeModules\MailingSystem\Enums\MessageEngagement;
use EpsicubeModules\MailingSystem\Enums\MessageStatus;
use EpsicubeModules\MailingSystem\Enums\MessageType;
use EpsicubeModules\MailingSystem\Enums\OutboxStatus;
use EpsicubeModules\MailingSystem\Models\Outbox;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OutboxInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('General Information'))->schema([
                TextEntry::make('subject')->label(__('Subject')),
                TextEntry::make('message_id')->label(__('Message ID')),
                TextEntry::make('status')->label(__('Status'))
                    ->inlineLabel()->badge()
                    ->formatStateUsing(fn (OutboxStatus $state) => $state->label())
                    ->tooltip(fn (OutboxStatus $state) => $state->description())
                    ->color(fn (OutboxStatus $state): string => match ($state) {
                        OutboxStatus::PENDING => 'info',
                        OutboxStatus::SENT    => 'success',
                        OutboxStatus::ERROR   => 'danger',
                    }),
                TextEntry::make('created_at')
                    ->label(__('Date'))->inlineLabel()
                    ->dateTime()->sinceTooltip(),
            ])->columns(2),

            // TODO RELATION
            RepeatableEntry::make('messages')->label(__('Messages'))
                ->columnSpanFull()
                ->table([
                    RepeatableEntry\TableColumn::make(__('Type')),
                    RepeatableEntry\TableColumn::make(__('Recipient')),
                    RepeatableEntry\TableColumn::make(__('Status')),
                    RepeatableEntry\TableColumn::make(__('Engagement')),
                    RepeatableEntry\TableColumn::make(__('Opened count')),
                    RepeatableEntry\TableColumn::make(__('Clicked count')),
                ])
                ->schema([
                    TextEntry::make('type')->label(__('Type'))
                        ->badge()
                        ->formatStateUsing(fn (MessageType $state) => $state->label())
                        ->tooltip(fn (MessageType $state) => $state->description())
                        ->color(fn (MessageType $state): string => match ($state) {
                            MessageType::TO  => 'info',
                            MessageType::CC  => 'gray',
                            MessageType::BCC => 'warning',
                        }),
                    TextEntry::make('recipient')->label(__('Recipient')),

                    TextEntry::make('status')
                        ->label(__('Status'))
                        ->badge()
                        ->formatStateUsing(fn (MessageStatus $state) => $state->label())
                        ->tooltip(fn (MessageStatus $state) => $state->description())
                        ->color(fn (MessageStatus $state): string => match ($state) {
                            MessageStatus::RECEIVED                       => 'gray',
                            MessageStatus::DEFERRED                       => 'info',
                            MessageStatus::DELIVERED                      => 'success',
                            MessageStatus::DROPPED,MessageStatus::BOUNCED => 'danger',
                        }),

                    TextEntry::make('engagement')
                        ->label(__('Engagement'))
                        ->badge()
                        ->formatStateUsing(fn (MessageEngagement $state) => $state->label())
                        ->tooltip(fn (MessageEngagement $state) => $state->description())
                        ->color(fn (MessageEngagement $state): string => match ($state) {
                            MessageEngagement::OPENED       => 'info',
                            MessageEngagement::CLICKED      => 'success',
                            MessageEngagement::SPAM         => 'danger',
                            MessageEngagement::UNSUBSCRIBED => 'warning',
                        }),

                    TextEntry::make('opened_count')
                        ->label(__('Opened count'))
                        ->numeric(0),

                    TextEntry::make('clicked_count')
                        ->label(__('Clicked count'))
                        ->numeric(0),
                ]),

            ViewEntry::make('raw_message')
                ->label('')
                ->view('epsicube-mail::components.email-preview')
                ->columnSpanFull()
                ->visible(fn (Outbox $record) => ! empty($record->raw_message)),

        ]);
    }
}
