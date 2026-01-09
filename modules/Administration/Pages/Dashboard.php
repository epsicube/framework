<?php

declare(strict_types=1);

namespace EpsicubeModules\Administration\Pages;

use Filament\Pages\Page;

class Dashboard extends Page
{
    protected string $view = 'epsicube-administration::pages.dashboard';

    protected static ?int $navigationSort = -1;
}
