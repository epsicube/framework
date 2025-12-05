<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Integrations\Administration\Resources\Executions\Tables;

use EpsicubeModules\ExecutionPlatform\Enum\ExecutionStatus;
use EpsicubeModules\ExecutionPlatform\Facades\Workflows;
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

                TextColumn::make('workflow_type')
                    ->label(__('Workflow Type'))
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

                        SelectConstraint::make('workflow_type')
                            ->label(__('Workflow Type'))
                            ->options(Workflows::toIdentifierLabelMap())
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
