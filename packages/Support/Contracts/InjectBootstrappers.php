<?php

declare(strict_types=1);

namespace Epsicube\Support\Contracts;

interface InjectBootstrappers
{
    public function bootstrappers(): array;
}
