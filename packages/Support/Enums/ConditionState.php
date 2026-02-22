<?php

declare(strict_types=1);

namespace Epsicube\Support\Enums;

enum ConditionState: string
{
    case VALID = 'VALID';
    case INVALID = 'INVALID';
    case SKIPPED = 'SKIPPED';
}
