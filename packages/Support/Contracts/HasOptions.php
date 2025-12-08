<?php

declare(strict_types=1);

namespace Epsicube\Support\Contracts;

use Epsicube\Schemas\Schema;

interface HasOptions
{
    public function options(Schema $schema): void;
}
