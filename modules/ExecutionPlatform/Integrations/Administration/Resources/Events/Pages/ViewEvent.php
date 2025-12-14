<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Integrations\Administration\Resources\Events\Pages;

use EpsicubeModules\ExecutionPlatform\Integrations\Administration\Actions\ReRunEventAction;
use EpsicubeModules\ExecutionPlatform\Integrations\Administration\Actions\RunEventAction;
use EpsicubeModules\ExecutionPlatform\Integrations\Administration\Resources\Events\EventResource;
use Filament\Resources\Pages\ViewRecord;

class ViewEvent extends ViewRecord
{
    protected static string $resource = EventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            RunEventAction::make(),
            ReRunEventAction::make(),
        ];
    }
}
