<?php

declare(strict_types=1);

namespace EpsicubeModules\Administration\Clusters\System;

use BackedEnum;
use EpsicubeModules\Administration\ApplicationGroup;
use EpsicubeModules\Administration\Enums\Icons;
use Filament\Clusters\Cluster;
use UnitEnum;

class OptionsCluster extends Cluster
{
    protected static string|UnitEnum|null $navigationGroup = ApplicationGroup::SYSTEM;

    protected static string|BackedEnum|null $navigationIcon = Icons::OPTION;

    protected static ?string $slug = '/system/options';

    protected static ?int $navigationSort = 20;
}
