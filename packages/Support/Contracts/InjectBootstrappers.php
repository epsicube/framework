<?php

declare(strict_types=1);

namespace UniGale\Support\Contracts;

interface InjectBootstrappers
{
    public function bootstrappers(): array;
}
