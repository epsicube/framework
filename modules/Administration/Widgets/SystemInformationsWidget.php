<?php

declare(strict_types=1);

namespace EpsicubeModules\Administration\Widgets;

use Exception;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

class SystemInformationsWidget extends BaseWidget
{
    protected static bool $isLazy = false;

    protected ?string $pollingInterval = '1s';

    protected function getHeading(): ?string
    {
        return __('Global System Overview');
    }

    protected function getDescription(): ?string
    {
        return __('Real-time monitoring of infrastructure, database, and environment.');
    }

    protected function getStats(): array
    {
        $cpuLoad = sys_getloadavg()[0];
        $coreCount = $this->getCpuCoreCount();
        $loadRatio = $cpuLoad / $coreCount;

        $memoryUsageBytes = memory_get_usage(true);
        $memoryInMb = $memoryUsageBytes / 1024 / 1024;
        $memoryLimit = ini_get('memory_limit');

        $driver = mb_strtolower(DB::getDriverName());
        $databaseName = DB::connection()->getDatabaseName();

        try {
            $connections = match ($driver) {
                'mysql' => DB::select('show status like "Threads_connected"')[0]->Value ?? '---',
                'pgsql' => DB::select('SELECT count(*) FROM pg_stat_activity')[0]->count ?? '---',
                default => 'N/A',
            };
        } catch (Exception $e) {
            $connections = '---';
        }

        return [
            Stat::make(__('Server Load'), round($cpuLoad, 2)." / {$coreCount}")
                ->description(__('Average load across available CPU cores'))
                ->icon('heroicon-m-cpu-chip')
                ->color(match (true) {
                    $loadRatio > 0.9 => 'danger',
                    $loadRatio > 0.7 => 'warning',
                    default          => 'success',
                }),

            Stat::make(__('PHP Memory'), Number::fileSize($memoryUsageBytes, precision: 1).' / '.($memoryLimit === '-1' ? '∞' : $memoryLimit))
                ->description(__('Current memory allocation vs script limit'))
                ->icon('heroicon-m-bolt')
                ->color(match (true) {
                    $memoryInMb > 512 => 'danger',
                    $memoryInMb > 256 => 'warning',
                    default           => 'info',
                }),

            Stat::make(__('Database'), "{$databaseName} (".Str::title($driver).')')
                ->icon('heroicon-m-circle-stack')
                ->description(__('Active Connections: ').$connections)
                ->color($connections !== '---' ? 'info' : 'gray'),

            Stat::make(__('Upload Limits'), ini_get('upload_max_filesize'))
                ->description(__('Post Max: ').ini_get('post_max_size'))
                ->icon('heroicon-m-arrow-up-tray')
                ->color('gray'),

            Stat::make(__('PHP Runtime'), 'v'.PHP_VERSION)
                ->description(__('Max Execution: ').ini_get('max_execution_time').'s')
                ->icon('heroicon-m-clock')
                ->color('gray'),

            Stat::make(__('Environment'), ucfirst(App::environment()))
                ->description(App::isProduction() ? __('Live Server') : __('Development Mode'))
                ->color(App::isProduction() ? 'success' : 'warning')
                ->icon('heroicon-m-server'),
        ];
    }

    protected function getCpuCoreCount(): int
    {
        return once(function () {
            if (is_file('/proc/cpuinfo')) {
                $cpuinfo = file_get_contents('/proc/cpuinfo');
                preg_match_all('/^processor/m', $cpuinfo, $matches);

                return count($matches[0]) ?: 1;
            }

            try {
                $process = shell_exec('sysctl -n hw.ncpu');
                if ($process) {
                    return (int) $process;
                }
            } catch (Exception $e) {
            }

            return 1;
        });

    }
}
