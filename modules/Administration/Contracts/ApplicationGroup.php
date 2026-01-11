<?php

declare(strict_types=1);

namespace EpsicubeModules\Administration\Contracts;

use BackedEnum;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

interface ApplicationGroup extends HasLabel
{
    public function getApplicationIcon(): string|BackedEnum|Htmlable|null;

    public function getApplicationSort(): ?int;
}
