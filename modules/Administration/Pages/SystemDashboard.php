<?php

declare(strict_types=1);

namespace EpsicubeModules\Administration\Pages;

use EpsicubeModules\Administration\Enums\ApplicationGroup;
use EpsicubeModules\Administration\Widgets\EpsicubeInformationsWidget;
use EpsicubeModules\Administration\Widgets\LaravelInformationsWidget;
use EpsicubeModules\Administration\Widgets\SystemInformationsWidget;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;

class SystemDashboard extends Page
{
    protected string $view = 'epsicube-administration::pages.system-dashboard';

    protected static ?string $slug = '/dashboards/system';

    public static function getNavigationLabel(): string
    {
        return __('System');
    }

    public function getTitle(): string|Htmlable
    {
        return __('System Dashboard');
    }

    protected static ?int $navigationSort = 100;

    protected static string|UnitEnum|null $navigationGroup = ApplicationGroup::DASHBOARDS;

    protected function getHeaderWidgets(): array
    {
        return [
            EpsicubeInformationsWidget::make(),
            SystemInformationsWidget::make(),
            LaravelInformationsWidget::make(),
        ];
    }
}
