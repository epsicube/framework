<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Integrations\Administration\Resources\Executions\Pages;

use EpsicubeModules\ExecutionPlatform\Integrations\Administration\Actions\CancelExecutionAction;
use EpsicubeModules\ExecutionPlatform\Integrations\Administration\Actions\ForkExecutionAction;
use EpsicubeModules\ExecutionPlatform\Integrations\Administration\Actions\RunExecutionAction;
use EpsicubeModules\ExecutionPlatform\Integrations\Administration\Resources\Executions\ExecutionResource;
use Filament\Resources\Pages\ViewRecord;

class ViewExecution extends ViewRecord
{
    protected static string $resource = ExecutionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            RunExecutionAction::make(),
            CancelExecutionAction::make(),
            ForkExecutionAction::make(),
        ];
    }
}
