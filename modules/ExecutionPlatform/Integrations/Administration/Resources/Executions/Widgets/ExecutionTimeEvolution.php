<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Integrations\Administration\Resources\Executions\Widgets;

use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Illuminate\Support\Facades\DB;

class ExecutionTimeEvolution extends ChartWidget
{
    use InteractsWithPageTable;

    public string $tableClass;

    protected function getTablePage(): string
    {
        return $this->tableClass;
    }

    public function getHeading(): string
    {
        return __('Performance Distribution');
    }

    protected function getMaxHeight(): ?string
    {
        return '250px';
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'title'       => [
                        'display' => true,
                        'text'    => 'ms',
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display'  => true,
                    'position' => 'bottom',
                ],
            ],
        ];
    }

    public function getData(): array
    {
        // Extracts "YYYY-MM-DD HH" from created_at
        $hourFormatField = 'SUBSTR(CAST(created_at AS CHAR(19)), 1, 13)';

        $results = $this->getPageTableQuery()
            ->reorder()
            ->select([
                DB::raw("{$hourFormatField} as hour_bucket"),
                'target',
                'execution_type',
                DB::raw('AVG(execution_time_ns) / 1000000 as avg_ms'),
                DB::raw('MAX(execution_time_ns) / 1000000 as max_ms'),
            ])
            ->whereNotNull('execution_time_ns')
            ->groupBy(DB::raw($hourFormatField), 'target', 'execution_type')
            ->orderBy('hour_bucket', 'asc')
            ->get();

        $uniqueBuckets = $results->pluck('hour_bucket')->unique()->sort()->values();

        // Formatting labels for the X axis
        $labels = $uniqueBuckets->map(function ($h) {
            return Carbon::parse($h.':00:00')->format('M d, H:00');
        })->toArray();

        // Grouping data by activity name
        $series = $results->groupBy(function ($item) {
            $type = is_object($item->execution_type) ? $item->execution_type->value : $item->execution_type;

            return "{$item->target} ({$type})";
        });

        $datasets = [];
        $index = 0;

        foreach ($series as $name => $values) {
            $meanPoints = [];
            $maxPoints = [];
            $valuesByBucket = $values->keyBy('hour_bucket');

            foreach ($uniqueBuckets as $bucket) {
                $row = $valuesByBucket->get($bucket);
                $meanPoints[] = $row ? (float) $row->avg_ms : 0;
                $maxPoints[] = $row ? (float) $row->max_ms : 0;
            }

            // Distinct color for each activity
            $color = 'hsla('.($index * 137.5 % 360).', 70%, 50%, 1)';

            // Dataset: Mean Performance
            $datasets[] = [
                'label'           => "{$name} (Mean)",
                'data'            => $meanPoints,
                'borderColor'     => $color,
                'backgroundColor' => str_replace('1)', '0.1)', $color),
                'borderWidth'     => 2,
                'tension'         => 0.3,
                'fill'            => false,
            ];

            // Dataset: Max Performance (Outliers/Spikes)
            $datasets[] = [
                'label'           => "{$name} (Max)",
                'data'            => $maxPoints,
                'borderColor'     => $color,
                'backgroundColor' => 'transparent',
                'borderWidth'     => 1,
                'borderDash'      => [5, 5], // Dashed line for Max
                'pointRadius'     => 0,      // Hidden points for cleaner look
                'tension'         => 0.3,
                'fill'            => false,
            ];

            $index++;
        }

        return [
            'datasets' => $datasets,
            'labels'   => $labels,
        ];
    }
}
