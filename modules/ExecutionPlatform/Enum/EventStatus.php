<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Enum;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Contracts\Support\Htmlable;

enum EventStatus: string implements HasColor, HasLabel
{
    case QUEUED = 'QUEUED';
    case SCHEDULED = 'SCHEDULED';
    case PROCESSING = 'PROCESSING';
    case CANCELED = 'CANCELED';
    case FAILED = 'FAILED';
    case COMPLETED = 'COMPLETED';

    public function getColor(): string|array|null
    {
        return FilamentColor::getColor($this->getColorName());
    }

    public function getColorName(): string|array|null
    {
        return match ($this) {
            self::QUEUED     => 'gray',
            self::SCHEDULED  => 'warning',
            self::PROCESSING => 'info',
            self::CANCELED   => 'gray',
            self::FAILED     => 'danger',
            self::COMPLETED  => 'success',
        };
    }

    public function getLabel(): string|Htmlable|null
    {
        return __(ucfirst(mb_strtolower($this->name)));
    }
}
