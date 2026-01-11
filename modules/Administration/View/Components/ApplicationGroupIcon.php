<?php

declare(strict_types=1);

namespace EpsicubeModules\Administration\View\Components;

use BackedEnum;
use Closure;
use EpsicubeModules\Administration\Contracts\ApplicationGroup;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Enums\IconSize;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ApplicationGroupIcon extends Component
{
    public string|array|null $color = null;

    public string|BackedEnum|Htmlable|null $icon = null;

    public function __construct(public ApplicationGroup $applicationGroup, public bool $active = false, public IconSize $size = IconSize::Medium, string|BackedEnum|Htmlable|null $fallback = null)
    {
        if ($this->applicationGroup instanceof HasColor) {
            $this->color = $this->applicationGroup->getColor();
        }
        $this->icon = $this->applicationGroup->getApplicationIcon() ?? $fallback;
    }

    public function shouldRender(): bool
    {
        return $this->icon !== null;
    }

    public function render(): View|Closure|string
    {
        return view('epsicube-administration::components.application-group-icon');
    }
}
