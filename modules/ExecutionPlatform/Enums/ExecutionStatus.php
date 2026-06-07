<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Enums;

use Illuminate\Contracts\Support\Htmlable;

enum ExecutionStatus: string
{
    case QUEUED = 'QUEUED';
    case SCHEDULED = 'SCHEDULED';
    case PROCESSING = 'PROCESSING';
    case CANCELED = 'CANCELED';
    case FAILED = 'FAILED';
    case COMPLETED = 'COMPLETED';

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::QUEUED     => 'neutral',
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
