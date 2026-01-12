<?php

declare(strict_types=1);

namespace EpsicubeModules\Administration\Widgets;

use Composer\InstalledVersions;
use Epsicube\Support\Facades\Modules;
use EpsicubeModules\Administration\Enums\Icons;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;

class EpsicubeInformationsWidget extends BaseWidget
{
    protected static bool $isLazy = false;

    protected ?string $pollingInterval = null;

    protected function getHeading(): ?string
    {
        return __('Epsicube & Framework');
    }

    protected function getDescription(): ?string
    {
        return __('Software versions, module deployment, and framework status.');
    }

    protected function getStats(): array
    {
        $version = InstalledVersions::getPrettyVersion('epsicube/foundation')
            ?? InstalledVersions::getPrettyVersion('epsicube/framework')
            ?? '---';

        $isStable = ! Str::contains(mb_strtolower($version), ['dev', 'alpha', 'beta', 'rc', 'patch']);

        $installedCount = count(Modules::all());
        $activatedCount = count(Modules::enabled());

        $isDownForMaintenance = App::isDownForMaintenance();
        $env = App::environment();

        return [
            Stat::make(__('Epsicube'), $version)
                ->description($isStable ? __('Stable release') : __('Development branch'))
                ->descriptionIcon($isStable ? 'heroicon-m-check-badge' : 'heroicon-m-beaker')
                ->color($isStable ? 'success' : 'warning'),

            Stat::make(__('App Status'), $isDownForMaintenance ? __('Maintenance') : __('Live'))
                ->description(ucfirst($env).' mode')
                ->icon($isDownForMaintenance ? 'heroicon-m-pause-circle' : 'heroicon-m-play-circle')
                ->color($isDownForMaintenance ? 'danger' : (App::isProduction() ? 'success' : 'warning')),

            Stat::make(__('Modules'), "{$activatedCount} / {$installedCount}")
                ->description(__('Active vs. Installed'))
                ->icon(Icons::MODULE)
                ->color($activatedCount === $installedCount ? 'success' : 'info'),

        ];
    }
}
