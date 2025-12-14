<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Integrations\Administration\Resources\Events\Schemas;

use EpsicubeModules\ExecutionPlatform\Enum\EventStatus;
use EpsicubeModules\ExecutionPlatform\Models\ExecutionEvent;
use Filament\Infolists\Components\CodeEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;
use Phiki\Grammar\Grammar;

class EventInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('General Info'))->afterHeader([
                TextEntry::make('status')
                    ->hiddenLabel()
                    ->size(TextSize::Large)
                    ->badge(),
            ])->schema([
                TextEntry::make('event_type')
                    ->label(__('Event Type')),

                TextEntry::make('created_at')
                    ->label(__('Created at'))
                    ->dateTime(),

                TextEntry::make('updated_at')
                    ->label(__('Updated at'))
                    ->dateTime(),
            ])->columns(4)->columnSpanFull(),

            Section::make(__('Input Configuration'))->schema([
                TextEntry::make('started_at')
                    ->label(__('Started at'))
                    ->inlineLabel()
                    ->dateTime(),

                TextEntry::make('activity_type')
                    ->label(__('Activity Identifier'))
                    ->inlineLabel()
                    ->copyable(),

                TextEntry::make('tries')
                    ->label(__('Tries'))
                    ->inlineLabel(),

                CodeEntry::make('input')
                    ->grammar(Grammar::Json)
                    ->hiddenLabel()
                    ->columnSpanFull(),
            ]),

            Section::make(__('Result'))->schema([
                TextEntry::make('completed_at')
                    ->label(__('Completed at'))
                    ->inlineLabel()
                    ->dateTime(),

                CodeEntry::make('output')
                    ->grammar(Grammar::Json)
                    ->hiddenLabel()
                    ->columnSpanFull(),

                TextEntry::make('output_error')
                    ->hiddenLabel()
                    ->color('danger')
                    ->visible(fn (ExecutionEvent $record) => $record->status === EventStatus::FAILED)
                    ->columnSpanFull(),
            ]),
        ]);
    }
}
