<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Integrations\Administration\Resources\Events\Tables;

use EpsicubeModules\ExecutionPlatform\Enum\EventStatus;
use EpsicubeModules\ExecutionPlatform\Enum\EventType;
use EpsicubeModules\ExecutionPlatform\Facades\Activities;
use EpsicubeModules\ExecutionPlatform\Integrations\Administration\Actions\ReRunEventAction;
use EpsicubeModules\ExecutionPlatform\Integrations\Administration\Actions\RunEventAction;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\QueryBuilder;
use Filament\Tables\Filters\QueryBuilder\Constraints\DateConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\SelectConstraint;
use Filament\Tables\Table;

class EventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->extraAttributes(['class' => 'compact'])
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

                TextColumn::make('event_type')
                    ->label(__('Event Type'))
                    ->badge()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('activity_type')
                    ->label(__('Activity Identifier'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('tries')
                    ->label(__('Tries'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('_run_id')
                    ->label(__('Run ID'))
                    ->copyable()
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
                            ->options(EventStatus::class)
                            ->multiple(),

                        SelectConstraint::make('event_type')
                            ->label(__('Event Type'))
                            ->options(EventType::class)
                            ->multiple(),

                        SelectConstraint::make('activity_type')
                            ->label(__('Activity Identifier'))
                            ->options(Activities::toIdentifierLabelMap())
                            ->multiple(),

                        DateConstraint::make('created_at')
                            ->label(__('Created at')),
                    ]),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->deferFilters(false)
            ->recordActions([
                ActionGroup::make([
                    RunEventAction::make(),
                    ReRunEventAction::make(),
                ])->buttonGroup(),
                ViewAction::make(),
            ]);
    }
}
