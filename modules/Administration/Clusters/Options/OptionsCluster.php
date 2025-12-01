<?php

declare(strict_types=1);

namespace UniGaleModules\Administration\Clusters\Options;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Support\Icons\Heroicon;

class OptionsCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
}
