<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Integrations\Administration\Resources\Executions\Schemas;

use EpsicubeModules\ExecutionPlatform\Enum\ExecutionStatus;
use EpsicubeModules\ExecutionPlatform\Models\Execution;
use Filament\Infolists\Components\CodeEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;

class ExecutionInfolist
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
                TextEntry::make('note')
                    ->label(__('Note')),

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

                TextEntry::make('workflow_type')
                    ->label(__('Workflow Type'))
                    ->inlineLabel()
                    ->copyable(),

                CodeEntry::make('input')
                    ->hiddenLabel()
                    ->columnSpanFull(),
            ]),

            Section::make(__('Result'))->schema([
                TextEntry::make('completed_at')
                    ->label(__('Completed at'))
                    ->inlineLabel()
                    ->dateTime(),

                CodeEntry::make('output')
                    ->hiddenLabel()
                    ->columnSpanFull(),

                TextEntry::make('last_error')
                    ->hiddenLabel()
                    ->color('danger')
                    ->visible(fn (Execution $record) => $record->status === ExecutionStatus::FAILED)
                    ->columnSpanFull(),
            ]),

        ]);
    }
}
