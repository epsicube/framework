<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Integrations\Administration\Resources\Executions\Widgets;

use EpsicubeModules\ExecutionPlatform\Enum\ExecutionStatus;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Illuminate\Contracts\Support\Htmlable;

class ExecutionStatusStats extends ChartWidget
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
        return __('Status Distribution');
    }

    protected function getMaxHeight(): ?string
    {
        return '250px';
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                ],
            ],
            'elements' => [
                'bar' => [
                    'borderWidth' => 0, // removes border around each bar
                ],
            ],
        ];
    }

    protected function getData(): array
    {
        // Single query to get counts by status
        $results = $this->getPageTableQuery()
            ->reorder() // remove ORDER BY to avoid 'only_full_group_by'
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        // Readable labels
        $labels = $results->keys()->map(
            fn (string $status) => ExecutionStatus::tryFrom($status)?->getLabel() ?? $status
        );

        // Dynamic colors
        $colors = $results->keys()->map(
            fn (string $status) => data_get(ExecutionStatus::tryFrom($status)?->getColor() ?? [], '300', '#6b727f')
        );

        return [
            'labels'   => $labels,
            'datasets' => [
                [
                    'label'           => 'Number of Executions',
                    'data'            => $results->values()->all(),
                    'backgroundColor' => $colors,
                    'borderColor'     => $colors,
                ],
            ],
        ];
    }
}
