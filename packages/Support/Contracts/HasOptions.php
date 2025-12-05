<?php

declare(strict_types=1);

namespace Epsicube\Support\Contracts;

use Epsicube\Support\OptionsDefinition;

interface HasOptions
{
    public function options(): OptionsDefinition;
}
