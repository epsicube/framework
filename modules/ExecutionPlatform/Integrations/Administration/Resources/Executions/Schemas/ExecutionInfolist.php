<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Integrations\Administration\Resources\Executions\Schemas;

use Carbon\CarbonInterval;
use EpsicubeModules\ExecutionPlatform\Enum\ExecutionStatus;
use EpsicubeModules\ExecutionPlatform\Models\Execution;
use Filament\Infolists\Components\CodeEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;
use Phiki\Grammar\Grammar;

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
                TextEntry::make('execution_type')->label(__('Execution Type'))->badge(),
                TextEntry::make('target')->label(__('Target'))->copyable(),
                TextEntry::make('created_at')->label(__('Created at'))->dateTime(),
                TextEntry::make('updated_at')->label(__('Updated at'))->dateTime(),
            ])->columns(4)->columnSpanFull(),

            Section::make(__('Context'))->schema([
                TextEntry::make('_idempotency_key')->label(__('Idempotency Key'))->inlineLabel()->copyable(),
                TextEntry::make('_run_id')->label(__('Run ID'))->inlineLabel()->copyable(),
                TextEntry::make('note')->label(__('Note'))->inlineLabel(),
            ]),

            Section::make(__('Timeline'))->afterHeader([
                TextEntry::make('execution_time')->hiddenLabel()->getStateUsing(
                    fn (Execution $record) => __('Execution time: :time', ['time' => CarbonInterval::microsecond($record->execution_time_ns / 1_000)->forHumans([
                        'minimumUnit' => 'microsecond',
                        'maximumUnit' => 'hour',
                        'short'       => true,
                    ])])
                )->badge()->color(fn (Execution $record) => match ($record->status) {
                    ExecutionStatus::FAILED    => 'danger',
                    ExecutionStatus::COMPLETED => 'success',
                    default                    => 'gray'
                }),

            ])->schema([
                TextEntry::make('started_at')->label(__('Started at'))->dateTime(),
                TextEntry::make('completed_at')->label(__('Completed at'))->dateTime(),
            ])->columns(2),

            Section::make(__('Input'))->schema([
                CodeEntry::make('input')
                    ->visible(fn (Execution $record) => ! empty($record->input))
                    ->grammar(Grammar::Json)
                    ->hiddenLabel()
                    ->columnSpanFull(),
                Text::make(__('No input provided.'))->visible(fn (Execution $record) => empty($record->input)),
            ]),

            Section::make(__('Output'))->schema([
                CodeEntry::make('output')
                    ->visible(fn (Execution $record) => ! empty($record->output))
                    ->hiddenLabel()
                    ->grammar(Grammar::Json)
                    ->columnSpanFull(),
                Text::make(__('No output provided.'))->visible(fn (Execution $record) => empty($record->output)),

                TextEntry::make('last_error')
                    ->hiddenLabel()
                    ->color('danger')
                    ->visible(fn (Execution $record) => $record->status === ExecutionStatus::FAILED)
                    ->columnSpanFull(),
            ])->visible(fn (Execution $record) => in_array($record->status, [ExecutionStatus::COMPLETED, ExecutionStatus::FAILED, ExecutionStatus::PROCESSING])),

        ]);
    }
}
