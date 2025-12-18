<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Integrations\Administration\Resources\Executions\Tables;

use Carbon\CarbonInterval;
use EpsicubeModules\ExecutionPlatform\Enum\ExecutionStatus;
use EpsicubeModules\ExecutionPlatform\Enum\ExecutionType;
use EpsicubeModules\ExecutionPlatform\Integrations\Administration\Actions\CancelExecutionAction;
use EpsicubeModules\ExecutionPlatform\Integrations\Administration\Actions\ForkExecutionAction;
use EpsicubeModules\ExecutionPlatform\Integrations\Administration\Actions\RunExecutionAction;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\Size;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\QueryBuilder;
use Filament\Tables\Filters\QueryBuilder\Constraints\DateConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\SelectConstraint;
use Filament\Tables\Table;

class ExecutionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->extraAttributes(['class' => 'compact']) // TODO
            ->deferLoading(true)
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(25)
            ->columns([
                TextColumn::make('id')
                    ->label(__('ID'))
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->sortable(),

                TextColumn::make('execution_type')
                    ->label(__('Execution Type'))
                    ->badge()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('target')
                    ->label(__('Target'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('note')
                    ->label(__('Note'))
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label(__('Created at'))
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('execution_time_ns')
                    ->label(__('Execution Time'))
                    ->formatStateUsing(fn (?int $state) => $state ? CarbonInterval::microsecond($state / 1_000)->forHumans([
                        'minimumUnit' => 'millisecond',
                        'maximumUnit' => 'hour',
                        'short'       => true,
                    ]) : null)
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('started_at')
                    ->label(__('Started at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('completed_at')
                    ->label(__('Completed at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                QueryBuilder::make()
                    ->constraints([
                        SelectConstraint::make('status')
                            ->label(__('Status'))
                            ->options(ExecutionStatus::class)
                            ->multiple(),

                        SelectConstraint::make('execution_type')
                            ->label(__('Execution Type'))
                            ->options(ExecutionType::class)
                            ->multiple(),

                        DateConstraint::make('created_at')
                            ->label(__('Created at')),
                    ]),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->deferFilters(false)
            ->recordActions([

                ActionGroup::make([
                    RunExecutionAction::make()->outlined()->size(Size::Small),
                    CancelExecutionAction::make()->outlined()->size(Size::Small),
                    ForkExecutionAction::make()->outlined()->size(Size::Small),
                ])->buttonGroup(),

                ViewAction::make(),

            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
