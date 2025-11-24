<?php

declare(strict_types=1);

namespace UniGaleModules\ExecutionPlatform\Integrations\Administration\Resources\Executions\Widgets;

use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Illuminate\Contracts\Support\Htmlable;
use UniGaleModules\ExecutionPlatform\Enum\ExecutionStatus;

class ExecutionsOverTime extends ChartWidget
{
    use InteractsWithPageTable;

    public string $tableClass;

    protected function getTablePage(): string
    {
        return $this->tableClass;
    }

    protected bool $isCollapsible = true;

    public function getHeading(): string|Htmlable|null
    {
        return __('Executions Over Time');
    }

    protected function getMaxHeight(): ?string
    {
        return '250px';
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $rows = $this->getPageTableQuery()
            ->reorder()
            ->selectRaw('
                DATE(completed_at) as day,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed_count,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as failed_count
            ', [ExecutionStatus::COMPLETED->value, ExecutionStatus::FAILED->value])
            ->whereNotNull('completed_at')
            ->groupBy('day')
            ->get();

        $datasets = [
            [
                'label'                => ExecutionStatus::COMPLETED->getLabel(),
                'data'                 => $rows->pluck('completed_count'),
                'borderColor'          => ExecutionStatus::COMPLETED->getColor()['300'],
                'pointBackgroundColor' => ExecutionStatus::COMPLETED->getColor()['300'],
                'backgroundColor'      => ExecutionStatus::COMPLETED->getColor()['300'],
                'pointRadius'          => 3,
                'borderWidth'          => 2,
                'tension'              => 0.2,
                'fill'                 => false,
            ],
            [
                'label'                => ExecutionStatus::FAILED->getLabel(),
                'data'                 => $rows->pluck('failed_count'),
                'borderColor'          => ExecutionStatus::FAILED->getColor()['300'],
                'pointBackgroundColor' => ExecutionStatus::FAILED->getColor()['300'],
                'backgroundColor'      => ExecutionStatus::FAILED->getColor()['300'],
                'pointRadius'          => 3,
                'borderWidth'          => 2,
                'tension'              => 0.2,
                'fill'                 => false,
            ],
        ];

        return [
            'labels'   => $rows->pluck('day'),
            'datasets' => $datasets,
        ];
    }
}
