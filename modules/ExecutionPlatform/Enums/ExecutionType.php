<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Enums;

use Filament\Support\Facades\FilamentColor;
use Illuminate\Contracts\Support\Htmlable;

enum ExecutionType: string
{
    case WORKFLOW = 'WORKFLOW';
    case ACTIVITY = 'ACTIVITY';

    public function getColor(): string|array|null
    {
        return FilamentColor::getColor($this->getColorName());
    }

    public function getColorName(): string|array|null
    {
        return match ($this) {
            self::WORKFLOW => 'primary',
            self::ACTIVITY => 'info',
        };
    }

    public function getLabel(): string|Htmlable|null
    {
        return __(ucfirst(mb_strtolower($this->name)));
    }
}
