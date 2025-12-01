<?php

declare(strict_types=1);

namespace UniGale\Support\Contracts;

use UniGale\Support\OptionsDefinition;

interface HasOptions
{
    public function options(): OptionsDefinition;
}
