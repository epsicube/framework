<?php

declare(strict_types=1);

namespace EpsicubeModules\Administration\Widgets;

use Exception;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

class SystemInformationsWidget extends BaseWidget implements HasForms
{
    use InteractsWithForms;

    protected static bool $isLazy = false;

    protected ?string $pollingInterval = '10s';

    protected function getHeading(): ?string
    {
        return __('Global System Overview');
    }

    protected function getStats(): array
    {
        $coreCount = $this->getCpuCoreCount();

        $loadAvg = sys_getloadavg();
        $cpuLoad = $loadAvg[0] ?? 0;
        $loadPercentage = ($coreCount > 0) ? ($cpuLoad / $coreCount) * 100 : 0;

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
            Stat::make(__('Server Load'), number_format($loadPercentage, 1).'%')
                ->description(__('Global average load (1 min)'))
                ->icon('heroicon-m-chart-bar')
                ->color(match (true) {
                    $loadPercentage > 100 => 'danger',
                    $loadPercentage > 80  => 'warning',
                    default               => 'success',
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
                ->color($connections !== '---' ? 'info' : null),

            Stat::make(__('Environment'), ucfirst(App::environment()))
                ->description(App::isProduction() ? __('Live Server') : __('Development Mode'))
                ->color(App::isProduction() ? 'success' : 'warning')
                ->icon('heroicon-m-server'),
        ];
    }

    protected function getCpuCoreCount(): float
    {
        return once(function () {
            try {
                if (@is_file('/sys/fs/cgroup/cpu.max')) {
                    $content = @file_get_contents('/sys/fs/cgroup/cpu.max');
                    if ($content) {
                        $parts = explode(' ', trim($content));
                        if (isset($parts[0], $parts[1]) && $parts[0] !== 'max') {
                            return (float) $parts[0] / (float) $parts[1];
                        }
                    }
                }

                if (@is_file('/sys/fs/cgroup/cpu/cpu.cfs_quota_us')) {
                    $quota = (int) @file_get_contents('/sys/fs/cgroup/cpu/cpu.cfs_quota_us');
                    $period = (int) @file_get_contents('/sys/fs/cgroup/cpu/cpu.cfs_period_us');
                    if ($quota > 0 && $period > 0) {
                        return (float) $quota / $period;
                    }
                }

                if (@is_file('/proc/cpuinfo')) {
                    $cpuinfo = @file_get_contents('/proc/cpuinfo');
                    if ($cpuinfo) {
                        preg_match_all('/^processor/m', $cpuinfo, $matches);

                        return (float) (count($matches[0]) ?: 1);
                    }
                }

                $sysctl = @shell_exec('sysctl -n hw.ncpu');
                if ($sysctl) {
                    return (float) trim($sysctl);
                }

                if (str_contains(PHP_OS, 'WIN')) {
                    $winCores = @shell_exec('echo %NUMBER_OF_PROCESSORS%');
                    if ($winCores) {
                        return (float) trim($winCores);
                    }
                }
            } catch (Exception $e) {
            }

            return 1.0;
        });
    }
}
