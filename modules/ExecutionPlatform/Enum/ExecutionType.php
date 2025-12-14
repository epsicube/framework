<?php

declare(strict_types=1);

namespace EpsicubeModules\ExecutionPlatform\Enum;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Contracts\Support\Htmlable;

enum ExecutionType: string implements HasColor, HasLabel
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
