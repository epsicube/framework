<?php

declare(strict_types=1);

namespace EpsicubeModules\Administration\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;

class LaravelInformationsWidget extends BaseWidget
{
    protected static bool $isLazy = false;

    protected ?string $pollingInterval = null;

    protected function getHeading(): ?string
    {
        return __('Laravel Framework');
    }

    protected function getDescription(): ?string
    {
        return __('Core configuration, drivers, and application lifecycle status.');
    }

    protected function getStats(): array
    {

        // Drivers
        $cacheDriver = config('cache.default');
        $queueDriver = config('queue.default');
        $sessionDriver = config('session.driver');
        $logChannel = config('logging.default');

        return [
            Stat::make(__('Framework'), 'v'.App::version())
                ->description(__('Locale: ').Str::upper(App::getLocale()))
                ->icon('heroicon-m-code-bracket'),

            Stat::make(__('Cache'), Str::upper($cacheDriver))
                ->description($cacheDriver === 'file' ? __('File storage') : __('Distributed cache'))
                ->icon('heroicon-m-bolt')
                ->color($cacheDriver === 'file' && App::isProduction() ? 'warning' : 'success'),

            Stat::make(__('Queue'), Str::upper($queueDriver))
                ->description(__('Job processor'))
                ->icon('heroicon-m-queue-list')
                ->color($queueDriver === 'sync' ? 'warning' : 'success'),

            Stat::make(__('Sessions'), Str::upper($sessionDriver))
                ->description(__('User persistence'))
                ->icon('heroicon-m-users')
                ->color($sessionDriver === 'file' && App::isProduction() ? 'warning' : 'info'),

            Stat::make(__('Logging'), Str::title($logChannel))
                ->description(__('Default channel'))
                ->icon('heroicon-m-document-text'),
        ];
    }
}
