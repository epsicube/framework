<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Integrations\Administration\Resources\Executions\Pages;

use EpsicubeModules\ExecutionPlatform\Integrations\Administration\Actions\CancelExecutionAction;
use EpsicubeModules\ExecutionPlatform\Integrations\Administration\Actions\ForkExecutionAction;
use EpsicubeModules\ExecutionPlatform\Integrations\Administration\Actions\RunExecutionAction;
use EpsicubeModules\ExecutionPlatform\Integrations\Administration\Resources\Executions\ExecutionResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewExecution extends ViewRecord
{
    protected static string $resource = ExecutionResource::class;

    public bool $showAsJson = false;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('toggleJsonView')
                ->label(fn () => $this->showAsJson ? __('Show Form') : __('Show JSON'))
                ->icon(fn () => $this->showAsJson ? 'heroicon-m-document-text' : 'heroicon-m-code-bracket')
                ->color('gray')
                ->action(fn () => $this->showAsJson = ! $this->showAsJson),

            RunExecutionAction::make(),
            CancelExecutionAction::make(),
            ForkExecutionAction::make(),
        ];
    }

    public function showAsJson(): bool
    {
        return $this->showAsJson;
    }
}
