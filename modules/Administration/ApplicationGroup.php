<?php

declare(strict_types=1);

namespace EpsicubeModules\Administration;

use BackedEnum;
use EpsicubeModules\Administration\Contracts\ApplicationGroup as ApplicationGroupContract;
use EpsicubeModules\Administration\Enums\Icons;
use Illuminate\Contracts\Support\Htmlable;

enum ApplicationGroup: string implements ApplicationGroupContract
{
    case SYSTEM = 'System';

    public function getApplicationLabel(): string
    {
        return match ($this) {
            self::SYSTEM => __('System'),
        };
    }

    public function getApplicationIcon(): string|Htmlable|null|BackedEnum
    {
        return match ($this) {
            self::SYSTEM => Icons::SYSTEM,
        };
    }
}
