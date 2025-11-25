<?php

declare(strict_types=1);

namespace UniGale\Foundation\Contracts;

use UniGale\Foundation\Options\OptionsDefinition;

interface HasOptions
{
    public function options(OptionsDefinition $options): void;
}
