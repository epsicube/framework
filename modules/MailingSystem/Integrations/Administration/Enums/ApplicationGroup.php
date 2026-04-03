<?php

declare(strict_types=1);

namespace EpsicubeModules\MailingSystem\Integrations\Administration\Enums;

use BackedEnum;
use EpsicubeModules\Administration\Contracts\ApplicationGroup as ApplicationGroupContract;
use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Illuminate\Contracts\Support\Htmlable;

enum ApplicationGroup implements ApplicationGroupContract, HasColor
{
    case MAILS;

    public function getApplicationIcon(): string|BackedEnum|Htmlable|null
    {
        return match ($this) {
            self::MAILS => 'phosphor-envelope-duotone',
        };
    }

    public function getApplicationSort(): ?int
    {
        return match ($this) {
            self::MAILS => 50,
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::MAILS => Color::Violet,
        };
    }

    public function getLabel(): string|Htmlable|null
    {

        return match ($this) {
            self::MAILS => __('Mails'),
        };
    }
}
