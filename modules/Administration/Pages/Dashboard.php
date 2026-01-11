<?php

declare(strict_types=1);

namespace EpsicubeModules\Administration\Pages;

use EpsicubeModules\Administration\Enums\ApplicationGroup;
use Filament\Pages\Page;
use UnitEnum;

class Dashboard extends Page
{
    protected string $view = 'epsicube-administration::pages.dashboard';

    protected static ?int $navigationSort = -1;

    protected static string|UnitEnum|null $navigationGroup = ApplicationGroup::DASHBOARDS;
}
