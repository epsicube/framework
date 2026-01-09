<?php

declare(strict_types=1);

namespace EpsicubeModules\Administration\Contracts;

use BackedEnum;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

interface ApplicationGroup
{
    public function getApplicationLabel(): string|HtmlString;

    public function getApplicationIcon(): string|BackedEnum|Htmlable|null;
}
