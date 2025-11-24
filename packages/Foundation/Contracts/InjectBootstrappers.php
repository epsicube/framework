<?php

declare(strict_types=1);

namespace UniGale\Foundation\Contracts;

interface InjectBootstrappers
{
    public function bootstrappers(): array;
}
