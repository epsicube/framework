<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Integrations\Administration\Resources\Events\Pages;

use EpsicubeModules\ExecutionPlatform\Integrations\Administration\Resources\Events\EventResource;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;

class ListEvents extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = EventResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
