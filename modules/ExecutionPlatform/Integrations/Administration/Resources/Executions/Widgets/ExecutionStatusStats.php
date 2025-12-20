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
        return __('Status Distribution per Hour');
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
            'interaction' => [
                'mode'      => 'index', // Show all items at the same index in the tooltip
                'intersect' => false, // Hovering anywhere in the vertical column triggers it
            ],
            'plugins' => [
                'legend' => [
                    'display'  => true,
                    'position' => 'bottom',
                ],
                'tooltip' => [
                    'enabled' => true,
                ],
            ],
            'scales' => [
                'x' => [
                    'stacked' => true,
                ],
                'y' => [
                    'stacked'     => true,
                    'beginAtZero' => true,
                ],
            ],
        ];
    }

    protected function getData(): array
    {
        $lookbackHours = 24;
        $startPeriod = now()->subHours($lookbackHours)->startOfHour();

        // 1. Fetch grouped results
        $results = $this->getPageTableQuery()
            ->reorder()
            ->selectRaw('
                SUBSTR(CAST(created_at AS CHAR(19)), 1, 13) as hour_key,
                status,
                COUNT(*) as total
            ')
            ->where('created_at', '>=', $startPeriod)
            ->groupBy('hour_key', 'status')
            ->get();

        // 2. Generate time slots for X-Axis
        $labels = [];
        $hourKeys = [];
        for ($i = 0; $i <= $lookbackHours; $i++) {
            $dt = $startPeriod->copy()->addHours($i);
            $hourKeys[] = $dt->format('Y-m-d H');
            $labels[] = $dt->format('H:00');
        }

        // 3. Only iterate over statuses present in the current results
        // We cast the raw status back to Enum to access labels/colors
        $foundStatuses = $results->pluck('status')->unique()->map(
            fn ($s) => $s instanceof ExecutionStatus ? $s : ExecutionStatus::tryFrom($s)
        )->filter();

        $datasets = [];
        /** @var ExecutionStatus $status */
        foreach ($foundStatuses as $status) {
            $datasetData = [];

            // Filter results for this specific status
            $statusResults = $results->where('status', $status->value)->keyBy('hour_key');
            foreach ($hourKeys as $hourKey) {
                $value = $statusResults->get($hourKey);
                $datasetData[] = $value ? (int) $value->total : null;
            }

            $datasets[] = [
                'label'           => $status->getLabel(),
                'data'            => $datasetData,
                'backgroundColor' => $status->getColor()['500'],

                'borderColor'  => 'transparent', // Eliminate the border stroke
                'borderWidth'  => 0,            // Force zero width
                'minBarLength' => 5,
            ];
        }

        return [
            'labels'   => $labels,
            'datasets' => $datasets,
        ];
    }
}
