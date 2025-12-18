<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Integrations\Administration\Resources\Executions\Pages;

use EpsicubeModules\ExecutionPlatform\Integrations\Administration\Resources\Executions\ExecutionResource;
use EpsicubeModules\ExecutionPlatform\Integrations\Administration\Resources\Executions\Widgets\ExecutionsOverTime;
use EpsicubeModules\ExecutionPlatform\Integrations\Administration\Resources\Executions\Widgets\ExecutionStatusStats;
use EpsicubeModules\ExecutionPlatform\Integrations\Administration\Resources\Executions\Widgets\ExecutionTimeEvolution;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;

class ListExecutions extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = ExecutionResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 4;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ExecutionStatusStats::make([
                'tableClass' => static::class,
            ]),
            ExecutionsOverTime::make([
                'tableClass' => static::class,
            ]),
            ExecutionTimeEvolution::make([
                'tableClass' => static::class,
            ]),
        ];
    }
}
