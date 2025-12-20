<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Integrations\Administration\Resources\Executions\Widgets;

use EpsicubeModules\ExecutionPlatform\Enum\ExecutionStatus;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Illuminate\Contracts\Support\Htmlable;

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
        $lookbackMinutes = 120;
        $now = now()->toImmutable();
        $startPeriod = $now->subMinutes($lookbackMinutes)->startOfMinute();

        // 1. Récupération des données groupées par minute (Universal SQL)
        $rows = $this->getPageTableQuery()
            ->reorder()
            ->selectRaw('
            SUBSTR(CAST(created_at AS CHAR(19)), 1, 16) as minute_key,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed_count,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as failed_count
        ', [ExecutionStatus::COMPLETED->value, ExecutionStatus::FAILED->value])
            ->where('created_at', '>=', $startPeriod)
            ->groupBy('minute_key')
            ->orderBy('minute_key', 'asc')
            ->get()
            ->keyBy('minute_key');

        $labels = [];
        $completedData = [];
        $failedData = [];

        // 2. Remplissage des minutes vides en PHP pour un graphique continu
        for ($i = 0; $i <= $lookbackMinutes; $i++) {
            $dateTime = $startPeriod->addMinutes($i);
            $key = $dateTime->format('Y-m-d H:i'); // Clé de correspondance SQL
            $label = $dateTime->format('H:i');     // Label d'affichage

            $labels[] = $label;
            $completedData[] = $rows->has($key) ? (int) $rows[$key]->completed_count : 0;
            $failedData[] = $rows->has($key) ? (int) $rows[$key]->failed_count : 0;
        }

        return [
            'labels'   => $labels,
            'datasets' => [
                [
                    'label'           => ExecutionStatus::COMPLETED->getLabel(),
                    'data'            => $completedData,
                    'borderColor'     => ExecutionStatus::COMPLETED->getColor()['300'],
                    'backgroundColor' => ExecutionStatus::COMPLETED->getColor()['300'],
                    'pointRadius'     => 0, // Optionnel: retire les points pour une ligne plus fluide
                    'borderWidth'     => 2,
                    'tension'         => 0.3,
                    'fill'            => false,
                ],
                [
                    'label'           => ExecutionStatus::FAILED->getLabel(),
                    'data'            => $failedData,
                    'borderColor'     => ExecutionStatus::FAILED->getColor()['300'],
                    'backgroundColor' => ExecutionStatus::FAILED->getColor()['300'],
                    'pointRadius'     => 0,
                    'borderWidth'     => 2,
                    'tension'         => 0.3,
                    'fill'            => false,
                ],
            ],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'type'     => 'linear',
                    'display'  => true,
                    'position' => 'left',
                    'title'    => ['display' => true, 'text' => __('Count')],
                ],
            ],
        ];
    }
}
