<?php

declare(strict_types=1);

namespace EpsicubeModules\Administration\Enums;

use BackedEnum;
use EpsicubeModules\Administration\Contracts\ApplicationGroup as ApplicationGroupContract;
use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Illuminate\Contracts\Support\Htmlable;

enum ApplicationGroup implements ApplicationGroupContract, HasColor
{
    case DASHBOARDS;
    case EXTRAS;
    case SYSTEM;

    public function getLabel(): string
    {
        return match ($this) {
            self::DASHBOARDS => __('Dashboards'),
            self::EXTRAS     => __('Extras'),
            self::SYSTEM     => __('System'),
        };
    }

    public function getApplicationIcon(): string|Htmlable|null|BackedEnum
    {
        return match ($this) {
            self::DASHBOARDS => Icons::APP_DASHBOARDS,
            self::EXTRAS     => Icons::APP_EXTRAS,
            self::SYSTEM     => Icons::APP_SYSTEM,
        };
    }

    public function getApplicationSort(): ?int
    {
        return match ($this) {
            self::DASHBOARDS => -1,
            self::EXTRAS     => 999,
            self::SYSTEM     => 1_000,
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::DASHBOARDS => Color::Emerald,
            self::EXTRAS, self::SYSTEM => Color::Neutral,
        };
    }
}
