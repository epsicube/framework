<?php

declare(strict_types=1);

namespace Epsicube\Support\Enums;

enum ModuleStatus: string
{
    case ENABLED = 'ENABLED';
    case DISABLED = 'DISABLED';
    case ERROR = 'ERROR';
}
