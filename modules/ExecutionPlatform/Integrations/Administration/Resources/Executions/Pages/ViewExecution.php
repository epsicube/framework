<?php

declare(strict_types=1);

namespace UniGaleModules\ExecutionPlatform\Integrations\Administration\Resources\Executions\Pages;

use Filament\Resources\Pages\ViewRecord;
use UniGaleModules\ExecutionPlatform\Integrations\Administration\Actions\CancelExecutionAction;
use UniGaleModules\ExecutionPlatform\Integrations\Administration\Actions\ForkExecutionAction;
use UniGaleModules\ExecutionPlatform\Integrations\Administration\Actions\RunExecutionAction;
use UniGaleModules\ExecutionPlatform\Integrations\Administration\Resources\Executions\ExecutionResource;

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
