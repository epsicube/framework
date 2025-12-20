<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Integrations\Administration\Resources\Executions\Schemas;

use Carbon\CarbonInterval;
use EpsicubeModules\ExecutionPlatform\Enum\ExecutionStatus;
use EpsicubeModules\ExecutionPlatform\Enum\ExecutionType;
use EpsicubeModules\ExecutionPlatform\Facades\Activities;
use EpsicubeModules\ExecutionPlatform\Integrations\Administration\Resources\Executions\Pages\ViewExecution;
use EpsicubeModules\ExecutionPlatform\Models\Execution;
use Filament\Infolists\Components\CodeEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Operation;
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
                    fn (Execution $record) => __('Execution time: :time', ['time' => CarbonInterval::microsecond($record->execution_time_ns / 1_000)->cascade()->forHumans([
                        'minimumUnit' => 'microsecond',
                        'maximumUnit' => 'hour',
                        'short'       => true,
                        'parts'       => 2,
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

            // Input sections
            Section::make(__('Input'))->key('input_json')->schema([
                Text::make(__('No input provided.'))->visible(fn (Execution $record) => empty($record->input)),
                CodeEntry::make('input')
                    ->visible(fn (Execution $record) => ! empty($record->input))
                    ->grammar(Grammar::Json)
                    ->hiddenLabel()
                    ->columnSpanFull(),
            ])->visible(fn ($livewire) => is_a($livewire, ViewExecution::class) && $livewire->showAsJson()),

            Section::make(__('Input'))->key('input')->statePath('input')->schema(function (Execution $record) {
                if (empty($record->input)) {
                    return [Text::make(__('No input provided.'))];
                }

                if ($record->execution_type === ExecutionType::ACTIVITY && Activities::safeGet($record->target)) {
                    return Activities::inputSchema($record->target)->toFilamentComponents(Operation::View);
                }

                return [
                    CodeEntry::make('input')->statePath('')
                        ->visible(fn (Execution $record) => ! empty($record->input))
                        ->grammar(Grammar::Json)
                        ->hiddenLabel()
                        ->columnSpanFull(),
                ];
            })->visible(fn ($livewire) => is_a($livewire, ViewExecution::class) && ! $livewire->showAsJson()),

            Section::make(__('Output'))->key('output_json')->schema([
                Text::make(__('No output provided.'))->visible(fn (Execution $record) => empty($record->output)),
                CodeEntry::make('output')
                    ->visible(fn (Execution $record) => ! empty($record->output))
                    ->grammar(Grammar::Json)
                    ->hiddenLabel()
                    ->columnSpanFull(),
            ])->visible(fn ($livewire) => is_a($livewire, ViewExecution::class) && $livewire->showAsJson()),

            Section::make(__('Output'))->key('output')->statePath('output')
                ->visible(fn (Execution $record) => in_array($record->status, [ExecutionStatus::COMPLETED, ExecutionStatus::FAILED, ExecutionStatus::PROCESSING]))
                ->schema(function (Execution $record) {
                    if (empty($record->output)) {
                        return [Text::make(__('No output provided.'))];
                    }
                    if ($record->execution_type === ExecutionType::ACTIVITY && Activities::safeGet($record->target)) {
                        return Activities::outputSchema($record->target)->toFilamentComponents(Operation::View);
                    }

                    return [
                        CodeEntry::make('output')->statePath('')
                            ->visible(fn (Execution $record) => ! empty($record->output))
                            ->hiddenLabel()
                            ->grammar(Grammar::Json)
                            ->columnSpanFull(),
                    ];
                })->visible(fn ($livewire) => is_a($livewire, ViewExecution::class) && ! $livewire->showAsJson()),
        ]);
    }
}
