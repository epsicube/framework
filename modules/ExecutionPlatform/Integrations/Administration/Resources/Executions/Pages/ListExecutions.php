<?php

declare(strict_types=1);

namespace UniGaleModules\ExecutionPlatform\Integrations\Administration\Resources\Executions\Pages;

use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;
use UniGaleModules\ExecutionPlatform\Integrations\Administration\Resources\Executions\ExecutionResource;
use UniGaleModules\ExecutionPlatform\Integrations\Administration\Resources\Executions\Widgets\ExecutionsOverTime;
use UniGaleModules\ExecutionPlatform\Integrations\Administration\Resources\Executions\Widgets\ExecutionStatusStats;

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
        ];
    }
}
